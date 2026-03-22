<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Exportar PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:24px; }
        .card { border:1px solid #eee; border-radius:12px; padding:16px; }
        .muted { color:#666; }
        code { background:#f6f6f6; padding:2px 6px; border-radius:6px; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:16px; font-weight:bold;">Generando PDF…</div>
        <div class="muted" style="margin-top:8px;">Si no se descarga automáticamente, revisa que tu navegador permita <strong>descargas</strong> y <strong>pop-ups</strong> para este sitio.</div>
    </div>

    <!-- jsPDF + autoTable (CDN) -->
    <script src="{{ asset('vendor/jspdf/jspdf.umd.min.js') }}"></script>
    <script src="{{ asset('vendor/jspdf-autotable/jspdf.plugin.autotable.min.js') }}"></script>

    <script>
        (async function () {
            const payload = @json($payload ?? []);
            const logoDataUri = @json($logoDataUri ?? null);
            const nombreSistema = @json($nombreSistema ?? 'Control de Asistencia Municipal');

            const isDetalle = payload?.tipo === 'detalle';
            const orientation = isDetalle ? 'l' : 'p';

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation, unit: 'pt', format: 'a4' });

            const margin = 40;
            const headerH = 60;

            let logoPngDataUri = null;
            async function normalizeLogoToPng() {
                if (!logoDataUri) return null;
                try {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    const loaded = await new Promise((resolve) => {
                        img.onload = () => resolve(true);
                        img.onerror = () => resolve(false);
                        img.src = logoDataUri;
                    });
                    if (!loaded) return null;

                    const canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    return canvas.toDataURL('image/png');
                } catch (e) {
                    return null;
                }
            }

            logoPngDataUri = await normalizeLogoToPng();

            function header(pageData) {
                const w = doc.internal.pageSize.getWidth();
                const h = doc.internal.pageSize.getHeight();

                // Logo
                if (logoPngDataUri) {
                    try { doc.addImage(logoPngDataUri, 'PNG', margin, 15, 40, 40); } catch (e) {}
                }

                // Título
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(13);
                doc.text(nombreSistema, margin + 50, 30);

                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                const subt = isDetalle ? 'Reporte detallado de asistencia (por día)' : 'Reporte general de asistencia por empresa';
                doc.text(subt, margin + 50, 46);

                const rango = `Rango: ${payload.desde} a ${payload.hasta}`;
                doc.text(rango, w - margin, 30, { align: 'right' });

                // Línea
                doc.setDrawColor(220);
                doc.line(margin, headerH, w - margin, headerH);
            }

            function addFooterWithTotalPages() {
                const totalPages = doc.internal.getNumberOfPages();
                const w = doc.internal.pageSize.getWidth();
                const h = doc.internal.pageSize.getHeight();
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i);
                    doc.text(`Página ${i} de ${totalPages}`, w - margin, h - 18, { align: 'right' });
                }
            }

            function safeText(v) {
                return (v === null || v === undefined) ? '' : String(v);
            }

            try {
                // Tablas
                if (!isDetalle) {
                    const grupos = payload.resumenEmp || [];
                    let startY = headerH + 18;

                    grupos.forEach((g, idx) => {
                        const title = `${safeText(g.empresa)}  (Convocados: ${g.totales?.convocados ?? 0}, Asistidos: ${g.totales?.asistidos ?? 0}, Justificados: ${g.totales?.justificados ?? 0}, No asistió: ${g.totales?.no_asistio ?? 0})`;
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(11);
                        doc.text(title, margin, startY);
                        startY += 10;

                        const body = (g.personas || []).map(r => [
                            safeText(r.nombre),
                            r.convocados ?? 0,
                            r.asistidos ?? 0,
                            r.justificados ?? 0,
                            r.no_asistio ?? 0,
                        ]);

                        doc.autoTable({
                            startY: startY + 10,
                            margin: { left: margin, right: margin, top: headerH + 10 },
                            head: [[ 'Persona', 'Convocados', 'Asistidos', 'Justificados', 'No asistió' ]],
                            body,
                            styles: { fontSize: 9 },
                            headStyles: { fontStyle: 'bold' },
                            didDrawPage: function (data) {
                                header(data);
                            },
                        });

                        startY = doc.lastAutoTable.finalY + 18;
                        // si queda poco espacio, saltamos de página para el siguiente depto
                        const pageH = doc.internal.pageSize.getHeight();
                        if (startY > pageH - 120) {
                            doc.addPage();
                            startY = headerH + 18;
                        }
                    });

                } else {
                    const rows = payload.detalle || [];
                    const body = rows.map(r => [
                        safeText(r.empresa),
                        safeText(r.persona),
                        safeText(r.fecha),
                        safeText(r.evento),
                        safeText(r.estado),
                    ]);

                    doc.autoTable({
                        startY: headerH + 18,
                        margin: { left: margin, right: margin, top: headerH + 10 },
                        head: [[ 'Empresa', 'Persona', 'Fecha', 'Evento', 'Estado' ]],
                        body,
                        styles: { fontSize: 8, cellPadding: 3 },
                        headStyles: { fontStyle: 'bold' },
                        columnStyles: {
                            0: { cellWidth: 120 },
                            1: { cellWidth: 160 },
                            2: { cellWidth: 60 },
                            3: { cellWidth: 220 },
                            4: { cellWidth: 70 },
                        },
                        didDrawPage: function (data) {
                            header(data);
                        },
                    });
                }

                addFooterWithTotalPages();

                const fileName = isDetalle
                    ? `reporte_asistencia_detalle_${payload.desde}_a_${payload.hasta}.pdf`
                    : `reporte_asistencia_resumen_${payload.desde}_a_${payload.hasta}.pdf`;

                doc.save(fileName);

                // Volver automáticamente a la pantalla anterior (evita depender de window.close)
                setTimeout(() => {
                    try {
                        const back = document.referrer;
                        if (back) {
                            window.location.href = back;
                            return;
                        }
                    } catch (e) {}
                }, 600);
            } catch (e) {
                console.error(e);
                document.body.innerHTML = '<div class="card"><div style="font-weight:bold;">No se pudo generar el PDF.</div><div class="muted" style="margin-top:6px;">Detalle: <code>' + safeText(e?.message || e) + '</code></div></div>';
            }
        })();
    </script>
</body>
</html>
