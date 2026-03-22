<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PDF - Asistencia por Día y Evento</title>
    <script src="{{ asset('vendor/jspdf/jspdf.umd.min.js') }}"></script>
    <script src="{{ asset('vendor/jspdf-autotable/jspdf.plugin.autotable.min.js') }}"></script>
    <style>
        body { font-family: Arial, sans-serif; }
        .muted { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="muted">Generando PDF…</div>

    @php
        /**
         * Compatibilidad:
         * - El controlador ExportPdfDiaEvento() envía variables sueltas (desde/hasta/dates/rows...).
         * - Esta vista usaba $payload. Lo construimos aquí para evitar errores.
         */
        $payload = $payload ?? [
            'desde' => $desde ?? null,
            'hasta' => $hasta ?? null,
            'empresaId' => $empresaId ?? null,
            'personaId' => $personaId ?? null,
            'dates' => $dates ?? [],
            'rows' => $rows ?? [],
            'logoDataUri' => $logoDataUri ?? null,
            'nombreSistema' => $nombreSistema ?? null,
        ];
    @endphp

    <script>
        const { jsPDF } = window.jspdf;

        const payload = @json($payload);

        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        const title = 'Asistencia por Día y Evento';
        const subtitle = 'Rango: ' + (payload.desde || '') + ' a ' + (payload.hasta || '');

        doc.setFontSize(14);
        doc.text(title, 14, 12);
        doc.setFontSize(9);
        doc.text(subtitle, 14, 17);

        const dates = payload.dates || [];
        const rows = payload.rows || [];

        const head = ['Persona'].concat(dates.map(d => d.label));
        head.push('Totales');

        // Mapa de datos por celda para dibujar colores (rowIndex/colIndex)
        const cellMap = {}; // key: `${ri}|${ci}` (ci relativo al head)

        const body = rows.map((r, ri) => {
            const row = [];
            row.push((r.nombre || '') + (r.departamento ? ('\n' + r.departamento) : ''));
            dates.forEach((d, di) => {
                const c = (r.cells || {})[d.date] || null;
                const ci = 1 + di; // columna real en tabla (0=Persona)
                if (c) {
                    cellMap[ri + '|' + ci] = c;
                }
                row.push('');
            });

            const t = r.totales || {};
            row.push(
                'Convocados: ' + (t.convocados || 0) +
                '\nAsistido ' + (t.asistio || 0) +
                ', Justificado ' + (t.justifico || 0) +
                ', Faltas ' + (t.no || 0)
            );
            return row;
        });

        function drawWrappedText(doc, text, x, y, maxWidth, lineHeight) {
            const lines = doc.splitTextToSize(text, maxWidth);
            lines.forEach((ln, idx) => {
                doc.text(ln, x, y + (idx * lineHeight));
            });
            return lines.length;
        }

        doc.autoTable({
            head: [head],
            body: body,
            startY: 22,
            styles: {
                fontSize: 7,
                cellPadding: 1.2,
                overflow: 'linebreak',
                valign: 'top'
            },
            headStyles: {
                fontStyle: 'bold'
            },
            columnStyles: {
                0: { cellWidth: 45 },
            },
            didParseCell: function (data) {
                // Ajustar alto mínimo de celdas según la cantidad de eventos
                if (data.section !== 'body') return;
                const key = data.row.index + '|' + data.column.index;
                const c = cellMap[key];
                if (!c) return;

                const nLines = (c.lines || []).length;
                const min = 8 + (nLines * 4); // mm aproximado
                data.cell.styles.minCellHeight = Math.max(data.cell.styles.minCellHeight || 0, min);
                // Quitar texto por defecto (dibujamos nosotros)
                data.cell.text = [''];
            },
            didDrawCell: function (data) {
                if (data.section !== 'body') return;
                const key = data.row.index + '|' + data.column.index;
                const c = cellMap[key];
                if (!c) return;

                const x = data.cell.x + 1.2;
                let y = data.cell.y + 3.2;
                const w = data.cell.width - 2.4;
                const lineH = 3.2;

                // Conteos: V (verde), J (azul), X (rojo)
                let cursorX = x;
                if ((c.a || 0) > 0) {
                    doc.setTextColor(40, 167, 69);
                    doc.text('V ' + c.a, cursorX, y);
                    cursorX += 10;
                }
                if ((c.j || 0) > 0) {
                    doc.setTextColor(0, 123, 255);
                    doc.text('J ' + c.j, cursorX, y);
                    cursorX += 8;
                }
                if ((c.n || 0) > 0) {
                    doc.setTextColor(220, 53, 69);
                    doc.text('X ' + c.n, cursorX, y);
                }
                doc.setTextColor(0, 0, 0);

                y += 3.5;

                // Eventos dentro de la celda
                (c.lines || []).forEach((ln) => {
                    const s = ln.s || 'N';
                    const t = ln.t || '';
                    if (s === 'A') doc.setTextColor(40, 167, 69);
                    else if (s === 'J') doc.setTextColor(0, 123, 255);
                    else doc.setTextColor(220, 53, 69);
                    doc.text((s === 'A' ? 'V' : (s === 'J' ? 'J' : 'X')), x, y);
                    doc.setTextColor(0, 0, 0);
                    const used = drawWrappedText(doc, t, x + 3, y, w - 3, lineH);
                    y += Math.max(1, used) * lineH;
                });
            }
        });

        // Descargar
        doc.save('asistencia_dia_evento_' + (payload.desde || '') + '_al_' + (payload.hasta || '') + '.pdf');

        // Volver automáticamente a la pantalla anterior
        setTimeout(() => {
            try {
                const back = document.referrer;
                if (back) {
                    window.location.href = back;
                }
            } catch (e) {}
        }, 600);
    </script>
</body>
</html>
