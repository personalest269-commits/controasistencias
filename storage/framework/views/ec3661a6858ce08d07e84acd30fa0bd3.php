<?php $__env->startSection('head'); ?>
    <?php echo \Illuminate\View\Factory::parentPlaceholder('head'); ?>
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/flatpickr/flatpickr.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/select2/css/select2.min.css')); ?>">
    <style>
        .select2-container{ width:100% !important; }
        .select2-container .select2-selection--single{ height:38px; padding:4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:28px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow{ height:38px; }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Reportes de asistencia</h4>
            <small class="text-muted">Asistió = asistencia registrada. Justificó = justificación aprobada (y sin asistencia registrada para el mismo evento/día).</small>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <form method="GET" action="<?php echo e(route('PgAsistenciasReportes')); ?>" class="mb-3">
        <div class="row">
            <div class="col-md-2">
                <label>Desde</label>
                <input type="text" id="desde" name="desde" value="<?php echo e($desde); ?>" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-2">
                <label>Hasta</label>
                <input type="text" id="hasta" name="hasta" value="<?php echo e($hasta); ?>" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-3">
                <label>Empresa - Departamento (opcional)</label>
                <select id="departamento_id" name="departamento_id" class="form-control">
                    <option value="" <?php echo e(!$departamentoId ? 'selected' : ''); ?>>-- Todos --</option>
                    <?php $__currentLoopData = $departamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $empresaNombre = trim((string) optional($d->empresa)->nombre);
                            $depNombre = trim((string) $d->descripcion);
                            $combo = $empresaNombre !== '' ? ($empresaNombre.' - '.$depNombre) : $depNombre;
                        ?>
                        <option value="<?php echo e($d->id); ?>" <?php echo e(($departamentoId==$d->id)?'selected':''); ?>><?php echo e($combo); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Persona (cédula / apellidos y nombres)</label>
                <select id="persona_id" name="persona_id" class="form-control">
                    <option value="" <?php echo e(!$personaId ? 'selected' : ''); ?>>-- Todas --</option>
                    <?php $__currentLoopData = ($personasSelect ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $nombre = trim(implode(' ', array_filter([
                                trim((string) $p->apellido1),
                                trim((string) $p->apellido2),
                                trim((string) $p->nombres),
                            ])));
                            $identificacion = trim((string) $p->identificacion);
                            $label = $identificacion !== '' ? ($identificacion.' - '.$nombre) : $nombre;
                        ?>
                        <option value="<?php echo e($p->id); ?>" <?php echo e(($personaId==$p->id)?'selected':''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="mb-3">
        <?php
            $isDual = \App\Services\AttendanceModeService::usesDualCheck();
        ?>
        <?php if($isDual): ?>
            <a class="btn btn-outline-warning btn-sm" href="<?php echo e(route('PgAsistenciasReportesDual', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Resumen Dual (A/AI/F)</a>
            <a class="btn btn-outline-warning btn-sm" href="<?php echo e(route('PgAsistenciasReporteDiaEventoDual', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Día y Evento Dual</a>
            <a class="btn btn-outline-warning btn-sm" href="<?php echo e(route('PgAsistenciasReporteMesDual', ['anio'=>\Carbon\Carbon::parse($hasta)->year, 'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Mes Dual</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="<?php echo e(route('PgAsistenciasReporteDiaEvento', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Asistencia por Día y Evento</a>
            <a class="btn btn-outline-primary btn-sm" href="<?php echo e(route('PgAsistenciasReporteMes', ['anio'=>\Carbon\Carbon::parse($hasta)->year, 'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Asistencia por Mes</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasReporteExportXlsResumen', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Exportar XLS (Resumen)</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasReporteExportXlsDetalle', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">Exportar XLS (Detallado)</a>
        
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasReporteExportPdfResumen', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">PDF (Resumen)</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasReporteExportPdfDetalle', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">PDF (Detallado)</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasReporteExport', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId])); ?>">CSV</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('PgAsistenciasIndex')); ?>">Volver a asistencia</a>
    </div>

    <?php
        $tot = ['convocados'=>0,'asistidos'=>0,'justificados'=>0,'no_asistio'=>0];
        foreach(($resumenDept ?? []) as $g){
            $tot['convocados'] += (int)($g['totales']['convocados'] ?? 0);
            $tot['asistidos'] += (int)($g['totales']['asistidos'] ?? 0);
            $tot['justificados'] += (int)($g['totales']['justificados'] ?? 0);
            $tot['no_asistio'] += (int)($g['totales']['no_asistio'] ?? 0);
        }
    ?>

    <div class="alert alert-light" style="border-radius:12px; border:1px solid #eee;">
        <div class="d-flex flex-wrap" style="gap:14px;">
            <div><strong>Total convocados:</strong> <?php echo e($tot['convocados']); ?></div>
            <div><strong>Total asistidos:</strong> <?php echo e($tot['asistidos']); ?></div>
            <div><strong>Total justificados:</strong> <?php echo e($tot['justificados']); ?></div>
            <div><strong>Total no asistió:</strong> <?php echo e($tot['no_asistio']); ?></div>
        </div>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $resumenDept; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="card mb-3" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
            <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
                <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
                    <strong><?php echo e($g['departamento']); ?></strong>
                    <div class="text-muted" style="font-size:12px;">
                        Convocados: <strong><?php echo e($g['totales']['convocados']); ?></strong> | Asistidos: <strong><?php echo e($g['totales']['asistidos']); ?></strong> | Justificados: <strong><?php echo e($g['totales']['justificados']); ?></strong> | No asistió: <strong><?php echo e($g['totales']['no_asistio']); ?></strong>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th class="text-center">Convocados</th>
                                <th class="text-center">Asistidos</th>
                                <th class="text-center">Justificados</th>
                                <th class="text-center">No asistió</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_2 = true; $__currentLoopData = $g['personas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <tr>
                                    <td><?php echo e($r['nombre']); ?></td>
                                    <td class="text-center"><?php echo e($r['convocados']); ?></td>
                                    <td class="text-center"><?php echo e($r['asistidos']); ?></td>
                                    <td class="text-center"><?php echo e($r['justificados']); ?></td>
                                    <td class="text-center"><?php echo e($r['no_asistio']); ?></td>
                                    <td class="text-right">
                                        <a class="btn btn-info btn-sm" href="<?php echo e(route('PgAsistenciasReportePersona', ['personaId'=>$r['persona_id'], 'desde'=>$desde, 'hasta'=>$hasta])); ?>">Detalle</a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <tr><td colspan="6" class="text-muted">Sin datos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="alert alert-info" style="border-radius:12px;">Sin datos para el rango seleccionado.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
    <?php echo \Illuminate\View\Factory::parentPlaceholder('footer'); ?>
    <script src="<?php echo e(asset('vendor/flatpickr/flatpickr.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/flatpickr/l10n/es.js')); ?>"></script>
    <script src="<?php echo e(asset('admin_lte/plugins/select2/js/select2.min.js')); ?>"></script>
    <script>
        (function () {
            // Flatpickr: mostrar d/m/Y, enviar Y-m-d
            if (window.flatpickr) {
                const common = {
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                };
                flatpickr('#desde', common);
                flatpickr('#hasta', common);
            }

            // "Bambox" (Select2): escribible/buscable
            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery('#departamento_id').select2({
                    placeholder: '-- Todos --',
                    allowClear: true,
                    width: '100%'
                });
                jQuery('#persona_id').select2({
                    placeholder: '-- Todas --',
                    allowClear: true,
                    width: '100%'
                });
            }
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/PgAsistencias/reportes.blade.php ENDPATH**/ ?>