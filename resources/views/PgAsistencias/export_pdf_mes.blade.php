<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PDF - Asistencia por Mes</title>
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
        $payload = [
            'anio' => $anio ?? null,
            'mes' => $mes ?? null,
            'todosMeses' => $todosMeses ?? true,
            'departamentoId' => $departamentoId ?? null,
            'personaId' => $personaId ?? null,
            'months' => $months ?? [],
            'nombreSistema' => $nombreSistema ?? null,
        ];
    @endphp

    <script>
        const { jsPDF } = window.jspdf;
        const payload = @json($payload);

        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const title = 'Asistencia por Mes';
        const subtitle = (payload.todosMeses ? ('Año: ' + payload.anio) : ('Mes: ' + (payload.mes || '') + ' / Año: ' + payload.anio));

        function addHeader(pageTitle){
            doc.setFontSize(14);
            doc.text(title, 14, 12);
            doc.setFontSize(9);
            doc.text(subtitle, 14, 17);
            if (pageTitle) {
                doc.setFontSize(11);
                doc.text(pageTitle, 14, 23);
            }
        }

        const months = payload.months || [];

        // Normalizar marcas para PDF
        function normMark(mark){
            if (!mark) return '';
            const m = String(mark).trim();
            if (m === '✓' || m === '✔') return 'A';
            if (m === '✗' || m === '×') return 'F';
            return m;
        }

        months.forEach((m, idx) => {
            if (idx > 0) doc.addPage();
            addHeader((m.titulo || '').toString());

            // =====================================================
            // Encabezado tipo calendario (igual a /reportes/mes)
            //  - Fila 1: Semana 1..N (colSpan=7)
            //  - Fila 2: L M M J V S D
            //  - Fila 3: 01 02 03 ...
            // =====================================================
            const weeks = (m.weeks || []);
            const numWeeks = weeks.length;
            const totalCols = 1 + (numWeeks * 7) + 1; // Persona + (7*días) + Totales

            // Mapa de celdas (rowIndex|colIndex) para colorear V/X
            const cellMap = {}; // { mark: 'A'|'F'|'J'|... }

            // Lista lineal de fechas (para iterar el body)
            const dateCols = [];
            weeks.forEach((w) => {
                (w || []).forEach((dateStr) => {
                    dateCols.push(dateStr || null);
                });
            });

            // headRow1: Persona | Semana 1..N | Totales
            const headRow1 = [{ content: 'Persona' }];
            for (let wi = 0; wi < numWeeks; wi++) {
                headRow1.push({ content: 'Semana ' + (wi + 1), colSpan: 7, styles: { halign: 'center' } });
            }
            headRow1.push({ content: 'Totales' });

            // headRow2: (vacío) | L M M J V S D * N | (vacío)
            const headRow2 = [''];
            const dows = ['L','M','M','J','V','S','D'];
            for (let wi = 0; wi < numWeeks; wi++) {
                dows.forEach(d => headRow2.push(d));
            }
            headRow2.push('');

            // headRow3: (vacío) | 01 02 ... | (vacío)
            const headRow3 = [''];
            dateCols.forEach((dateStr) => {
                if (!dateStr) { headRow3.push(''); return; }
                const d = new Date(dateStr + 'T00:00:00');
                headRow3.push(String(d.getDate()).padStart(2,'0'));
            });
            headRow3.push('');

            const rows = (m.rows || []).map((r) => {
                const row = [];
                row.push((r.nombre || '') + (r.departamento ? ('\n' + r.departamento) : ''));
                dateCols.forEach((dateStr) => {
                    if (!dateStr) { row.push(''); return; }
                    const c = (r.cells || {})[dateStr] || null;
                    const mk = normMark(c && c.mark ? c.mark : '');
                    row.push(mk);
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

            // Poblamos cellMap para colorear marks en didDrawCell
            rows.forEach((row, ri) => {
                // columnas: 0 Persona, 1..(7*numWeeks) días, last Totales
                for (let ci = 1; ci < (totalCols - 1); ci++) {
                    const mk = (row[ci] || '').toString().trim();
                    if (!mk) continue;
                    cellMap[ri + '|' + ci] = { mark: mk };
                    // vaciamos texto para dibujarlo nosotros
                    row[ci] = '';
                }
            });

            doc.autoTable({
                head: [headRow1, headRow2, headRow3],
                body: rows,
                startY: 27,
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
                    [totalCols - 1]: { cellWidth: 45 },
                },
                margin: { left: 8, right: 8 },
                didParseCell: function (data) {
                    // Ajuste: centrado de las marcas
                    if (data.section !== 'body') return;
                    if (data.column.index === 0 || data.column.index === (totalCols - 1)) return;
                    const key = data.row.index + '|' + data.column.index;
                    if (!cellMap[key]) return;
                    data.cell.text = [''];
                    data.cell.styles.halign = 'center';
                    data.cell.styles.valign = 'middle';
                },
                didDrawCell: function (data) {
                    if (data.section !== 'body') return;
                    const key = data.row.index + '|' + data.column.index;
                    const info = cellMap[key];
                    if (!info) return;
                    const mk = info.mark;
                    const cx = data.cell.x + (data.cell.width / 2);
                    const cy = data.cell.y + (data.cell.height / 2);

                    // Colores: A verde, F rojo, J azul
                    if (mk === 'A') {
                        doc.setTextColor(40, 167, 69);
                        doc.setFontSize(8);
                        doc.text('A', cx, cy + 2, { align: 'center' });
                        doc.setTextColor(0, 0, 0);
                    } else if (mk === 'F') {
                        doc.setTextColor(220, 53, 69);
                        doc.setFontSize(8);
                        doc.text('F', cx, cy + 2, { align: 'center' });
                        doc.setTextColor(0, 0, 0);
                    } else if (mk === 'J') {
                        doc.setTextColor(0, 123, 255);
                        doc.setFontSize(8);
                        doc.text('J', cx, cy + 2, { align: 'center' });
                        doc.setTextColor(0, 0, 0);
                    } else {
                        doc.setTextColor(0, 0, 0);
                        doc.setFontSize(8);
                        doc.text(String(mk), cx, cy + 2, { align: 'center' });
                    }
                }
            });
        });

        const fileName = payload.todosMeses
            ? ('asistencia_mes_' + payload.anio + '.pdf')
            : ('asistencia_mes_' + payload.anio + '_' + String(payload.mes || '').padStart(2,'0') + '.pdf');

        doc.save(fileName);

        // Volver automáticamente al reporte (evita quedarse en "Generando PDF...")
        setTimeout(() => {
            try {
                const back = document.referrer;
                if (back) window.location.href = back;
            } catch (e) {}
        }, 500);
    </script>
</body>
</html>
