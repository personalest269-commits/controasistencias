<?php $__env->startSection('head'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/select2/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/flatpickr/flatpickr.min.css')); ?>">
    <style>
        .pg-card{
            border:0;
            border-radius:12px;
            box-shadow: 0 10px 25px rgba(0,0,0,.06);
        }
        .pg-card .card-header{
            background:#fff;
            border-bottom:1px solid #e9ecef;
            border-top-left-radius:12px;
            border-top-right-radius:12px;
        }
        .pg-filter{
            background:#fff;
            border:1px solid #e9ecef;
            border-radius:12px;
            padding:16px;
        }
        .select2-container .select2-selection--multiple{
            min-height:38px;
            border-radius:10px;
            border:1px solid #ced4da;
        }
        .badge-status{
            font-size:11px;
            padding:4px 8px;
            border-radius:999px;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Administrar asistencia masiva</h4>
            <small class="text-muted">Marca asistencia por evento. Si seleccionas un departamento, se pre-seleccionan todos los eventos aplicables por persona.</small>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <form method="GET" action="<?php echo e(route('PgAsistenciasIndex')); ?>">
        <div class="pg-filter mb-3">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1">Fecha</label>
                    <input type="text" id="fecha" name="fecha" value="<?php echo e($fecha); ?>" class="form-control" autocomplete="off" />
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Departamento</label>
                    <select id="departamento_id" name="departamento_id" class="form-control">
                        <option value="">-- General (todos) --</option>
                        <?php $__currentLoopData = $departamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($d->id); ?>" <?php echo e(($departamentoId==$d->id)?'selected':''); ?>><?php echo e($d->descripcion); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Persona</label>
                    <select id="persona_id" name="persona_id" class="form-control" style="width:100%">
                        <?php if(!empty($personaId) && $personas->count()===1): ?>
                            <option value="<?php echo e($personas->first()->id); ?>" selected>
                                <?php echo e($personas->first()->identificacion ? ($personas->first()->identificacion.' — ') : ''); ?><?php echo e($personas->first()->nombre_completo); ?>

                            </option>
                        <?php endif; ?>
                    </select>
                 
                </div>
                <div class="col-md-2 text-right">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Si eliges un departamento, las evidencias se cargan 1 vez por evento (máx 4 fotos). En modo general, se puede cargar 1 foto por persona.
                </small>
				  
            </div>
        </div>
    </form>

    <form method="POST" action="<?php echo e(route('PgAsistenciasActualizar')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="fecha" value="<?php echo e($fecha); ?>" />
        <input type="hidden" name="departamento_id" value="<?php echo e($departamentoId); ?>" />

        <?php if($departamentoId): ?>
            <div class="card pg-card mb-3">
                <div class="card-header">
                    <strong>Evidencias por evento (Departamento)</strong>
                    <span class="text-muted">&nbsp;— máximo 4 fotos por evento (se cargan una sola vez)</span>
                </div>
                <div class="card-body">
                    <?php if(empty($deptEventRows) || count($deptEventRows)==0): ?>
                        <div class="text-muted">No hay eventos para la fecha seleccionada.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php $__currentLoopData = $deptEventRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div style="font-weight:600;"><?php echo e($row['evento']->titulo); ?></div>
                                                <div class="text-muted" style="font-size:12px;">Evidencias actuales: <?php echo e($row['archivos_count']); ?></div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="file" name="dept_event_files[<?php echo e($row['evento']->id); ?>][]" class="form-control" multiple accept="image/*" />
                                            <small class="text-muted">Sube 1 a 4 fotos (jpg/png/webp). El sistema valida el máximo total.</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card pg-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <strong>Listado de empleados</strong>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkGeneral">
                            <label class="custom-control-label" for="chkGeneral">Marcar general</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkAutoSave">
                            <label class="custom-control-label" for="chkAutoSave">Auto-actualizar</label>
                        </div>
                        <input type="hidden" name="auto_close" id="auto_close" value="0" />
                        <a href="<?php echo e(route('PgAsistenciasReportes')); ?>" class="btn btn-warning btn-sm" id="btnReportes">Reportes</a>
                        <button class="btn btn-primary btn-sm" type="submit" id="btnCerrarDia"
                                name="cerrar_dia" value="1"
                                formaction="<?php echo e(route('PgAsistenciasCerrarDia')); ?>">
                            Cerrar asistencia del día
                        </button>
                        <button class="btn btn-success btn-sm" type="submit" id="btnActualizar">Actualizar</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if(($events ?? collect())->count() === 0): ?>
                    <div class="alert alert-warning mb-3">
                        No existen eventos creados para la fecha seleccionada. Debe crear eventos para poder <strong>Cerrar asistencia del día</strong> y/o <strong>Actualizar</strong>.
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:170px;">IDENTIFICACIÓN</th>
                                <th>EMPLEADO</th>
                                <th>DEPARTAMENTO</th>
                                <th style="width:120px;" class="text-center">ASISTENCIA</th>
                                <th style="min-width:280px;">EVENTOS</th>
                                <?php if(!$departamentoId): ?>
                                    <th style="width:220px;">EVIDENCIA (1 foto)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $personas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $sel = $selectedByPerson[$p->id] ?? [];
                                    $asist = $asistenciaMap[$p->id] ?? [];
                                    $just = $justMap[$p->id] ?? [];
                                    $deptName = $p->departamento ? $p->departamento->descripcion : '';
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-primary" style="border-radius:10px; padding:8px 10px;"><?php echo e($p->identificacion ?: ('#EMP'.$p->id)); ?></span>
                                    </td>
                                    <td><?php echo e($p->nombre_completo); ?></td>
                                    <td><?php echo e($deptName); ?></td>
                                    <td class="text-center">
                                        <input type="checkbox" class="js-tgl" data-persona="<?php echo e($p->id); ?>" <?php echo e(!empty($sel) ? 'checked' : ''); ?> />
                                    </td>
                                    <td>
                                        <select name="person_events[<?php echo e($p->id); ?>][]" class="form-control js-eventos" multiple data-persona="<?php echo e($p->id); ?>">
                                            <?php $__currentLoopData = ($eventsByPerson[$p->id] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $isSel = in_array($e->id, $sel, true);
                                                    $badge = '';
                                                    if (!empty($asist[$e->id]) && ($asist[$e->id]->estado_asistencia ?? null) === 'A') $badge = ' (A)';
                                                    elseif (!empty($asist[$e->id]) && ($asist[$e->id]->estado_asistencia ?? null) === 'F') $badge = ' (F)';
                                                    elseif (!empty($just[$e->id])) $badge = ' (JUSTIFICÓ)';
                                                ?>
                                                <option value="<?php echo e($e->id); ?>" <?php echo e($isSel ? 'selected' : ''); ?>><?php echo e($e->titulo); ?><?php echo e($badge); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php if(empty($eventsByPerson[$p->id] ?? [])): ?>
                                            <small class="text-muted">Sin eventos para esta fecha.</small>
                                        <?php endif; ?>
                                    </td>
                                    <?php if(!$departamentoId): ?>
                                        <td>
                                            <input type="file" name="person_file[<?php echo e($p->id); ?>]" class="form-control" accept="image/*,.pdf,.doc,.docx" />
                                            <small class="text-muted">Se aplica a los eventos seleccionados.</small>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="text-muted">No hay empleados para el filtro.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
    <script src="<?php echo e(asset('vendor/flatpickr/flatpickr.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/flatpickr/l10n/es.js')); ?>"></script>
    <script src="<?php echo e(asset('admin_lte/plugins/select2/js/select2.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin_lte/plugins/select2/js/i18n/es.js')); ?>"></script>
    <script>
        $(function(){
            var hasEventos = <?php echo e((($events ?? collect())->count() > 0) ? 'true' : 'false'); ?>;

            // Fecha en formato visual dd/mm/aaaa, valor real yyyy-mm-dd
            if (window.flatpickr) {
                flatpickr('#fecha', {
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true
                });
            }

            // Departamentos con búsqueda
            $('#departamento_id').select2({ width:'100%', placeholder:'-- General (todos) --', allowClear:true, language:'es' });

            // Persona combobox (ajax)
            $('#persona_id').select2({
                width: '100%',
                placeholder: 'Buscar por nombre o identificación',
                allowClear: true,
                minimumInputLength: 2,
                language: 'es',
                ajax: {
                    url: '<?php echo e(route('PgAsistenciasPersonasSearch')); ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            departamento_id: $('#departamento_id').val() || ''
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $('.js-eventos').select2({ width:'100%', language:'es' });

            function noEventosMsg(){
                alert('No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.');
            }

            // Bloquea acciones si no hay eventos para la fecha
            $('#btnCerrarDia, #btnActualizar').on('click', function(e){
                if (!hasEventos) {
                    e.preventDefault();
                    e.stopPropagation();
                    noEventosMsg();
                    return false;
                }
            });

            $('#chkAutoSave').on('change.noEventos', function(){
                if ($(this).is(':checked') && !hasEventos) {
                    $(this).prop('checked', false);
                    $('#auto_close').val('0');
                    noEventosMsg();
                }
            });

            $('.js-tgl').on('change', function(){
                var pid = $(this).data('persona');
                var $sel = $('.js-eventos[data-persona="'+pid+'"]');
                if ($(this).is(':checked')) {
                    // seleccionar todo
                    $sel.find('option').prop('selected', true);
                } else {
                    $sel.val(null);
                }
                $sel.trigger('change');
            });

            // Marcar general (selecciona todo/limpia en todas las filas)
            $('#chkGeneral').on('change', function(){
                var mark = $(this).is(':checked');
                $('.js-tgl').each(function(){
                    $(this).prop('checked', mark).trigger('change');
                });
            });

            // Auto-actualizar: guarda por persona cuando cambia selección (sin evidencias)
            function savePersona(pid){
                var $sel = $('.js-eventos[data-persona="'+pid+'"]').first();
                var eventos = $sel.val() || [];
                return $.ajax({
                    method: 'POST',
                    url: '<?php echo e(route('PgAsistenciasActualizarItem')); ?>',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        fecha: '<?php echo e($fecha); ?>',
                        departamento_id: '<?php echo e($departamentoId); ?>',
                        persona_id: pid,
                        eventos: eventos,
                        auto_close: $('#chkAutoSave').is(':checked') ? '1' : '0'
                    }
                });
            }

            var saveTimer = {};
            function debounceSave(pid){
                if (!$('#chkAutoSave').is(':checked')) return;
                if (saveTimer[pid]) clearTimeout(saveTimer[pid]);
                saveTimer[pid] = setTimeout(function(){
                    savePersona(pid).fail(function(xhr){
                        console.error('Auto-actualizar falló', xhr);
                    });
                }, 400);
            }

            // Mantener hidden auto_close para el submit normal
            $('#chkAutoSave').on('change', function(){
                $('#auto_close').val($(this).is(':checked') ? '1' : '0');
            }).trigger('change');

            $('.js-eventos').on('change', function(){
                debounceSave($(this).data('persona'));
            });
            $('.js-tgl').on('change', function(){
                debounceSave($(this).data('persona'));
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/PgAsistencias/index.blade.php ENDPATH**/ ?>