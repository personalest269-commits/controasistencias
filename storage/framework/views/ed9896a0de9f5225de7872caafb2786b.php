<?php $__env->startSection('content'); ?>
    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">Preview de Importación</h3>
            <p class="text-muted" style="margin-bottom:0;">Lote: <code><?php echo e($batch); ?></code>. Revisa los mapeos antes de aplicar.</p>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <?php if($hasErrors): ?>
        <div class="alert alert-danger">
            Hay registros con <strong>empresa no válida</strong>. Corrige el origen o crea la empresa faltante antes de aplicar.
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            Todo listo: no se detectaron problemas de mapeo en este lote.
        </div>
    <?php endif; ?>

    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12" style="display:flex;gap:8px;">
            <a class="btn btn-default" href="<?php echo e(route('personas.import.index')); ?>"><i class="fa fa-arrow-left"></i> Volver</a>

            <a class="btn btn-warning" href="<?php echo e(route('personas.import.report', $batch)); ?>">
                <i class="fa fa-file-excel-o"></i> Reporte de cambios
            </a>

            <form method="POST" action="<?php echo e(route('personas.import.clear', $batch)); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button class="btn btn-warning" type="submit" onclick="return confirm('¿Eliminar este lote temporal?')"><i class="fa fa-trash"></i> Limpiar lote</button>
            </form>

            <form method="POST" action="<?php echo e(route('personas.import.truncate_stg')); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button class="btn btn-danger" type="submit" onclick="return confirm('⚠️ Esto hará TRUNCATE a pg_persona_stg (se borrará TODO lo temporal). ¿Continuar?')">
                    <i class="fa fa-eraser"></i> Truncar tabla temporal
                </button>
            </form>

            <form method="POST" action="<?php echo e(route('personas.import.apply', $batch)); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button class="btn btn-primary" type="submit" <?php echo e($hasErrors ? 'disabled' : ''); ?> onclick="return confirm('¿Aplicar importación a pg_persona?')">
                    <i class="fa fa-check"></i> Aplicar
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <div>
                        <strong>Registros vigentes (VIGENTE='S')</strong>
                        <span class="text-muted" style="margin-left:8px;">Total: <strong><?php echo e($rows->total()); ?></strong></span>
                    </div>
                    <form method="GET" action="<?php echo e(url()->current()); ?>" style="display:flex;align-items:center;gap:6px;margin:0;">
                        <span class="text-muted small">Ver:</span>
                        <?php $pp = request('per_page', 50); ?>
                        <select name="per_page" class="form-control input-sm" style="width:110px;">
                            <?php $__currentLoopData = [50,100,200,500,1000,2000]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($n); ?>" <?php echo e((int)$pp === (int)$n ? 'selected' : ''); ?>><?php echo e($n); ?> / pág.</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <input type="hidden" name="all" value="0">
                        <button class="btn btn-default btn-sm" type="submit">OK</button>

                        <a class="btn btn-primary btn-sm" href="<?php echo e(request()->fullUrlWithQuery(['all'=>1,'page'=>1])); ?>">
                            Ver todos
                        </a>
                    </form>
                </div>
                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" style="margin-bottom:0;">
                            <thead>
                            <tr>
                                <th>Acción</th>
                                <th>Identificación</th>
                                <th>Tipo</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Empresa</th>
                                <th>Empresa ID</th>
                                <th>Dirección</th>
                                <th>Vigencia desde</th>
                                <th>Vigencia hasta</th>
                                <th>Depto (desc)</th>
                                <th>Estado civil (desc)</th>
                                <th>Cod estado civil</th>
                                <th>Depto (código)</th>
                                <th>Depto ID</th>
                                <th>Email</th>
                                <th>Email laboral</th>
                                <th>F. nacimiento</th>
                                <th>Tipo ID</th>
                                <th>Sexo</th>
                                <th>Celular</th>
                                <th>F. ingreso</th>
                                <th>Check EC</th>
                                <th>Check Depto</th>
                                <th>Check Empresa</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // Solo EMPRESA bloquea. Estado civil/departamento pueden quedar NULL.
                                    $bad = ($r->empresa_check !== 'OK');
                                ?>
                                <tr style="<?php echo e($bad ? 'background:#fff1f2;' : ''); ?>">
                                    <td><span class="label label-<?php echo e($r->accion==='INSERT' ? 'success' : 'info'); ?>"><?php echo e($r->accion); ?></span></td>
                                    <td><?php echo e($r->identificacion); ?></td>
                                    <td><?php echo e($r->tipo); ?></td>
                                    <td><?php echo e($r->nombres); ?></td>
                                    <td><?php echo e(trim(($r->apellido1 ?? '').' '.($r->apellido2 ?? ''))); ?></td>
                                    <td><?php echo e($r->empresa_nombre); ?></td>
                                    <td><?php echo e($r->empresa_id); ?></td>
                                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($r->direccion); ?>"><?php echo e($r->direccion); ?></td>
                                    <td><?php echo e($r->vigencia_desde); ?></td>
                                    <td><?php echo e($r->vigencia_hasta); ?></td>
                                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($r->departamento); ?>"><?php echo e($r->departamento); ?></td>
                                    <td><?php echo e($r->descripcion_estado_civil); ?></td>
                                    <td><?php echo e($r->cod_estado_civil_resuelto); ?></td>
                                    <td><?php echo e($r->cod_departamento); ?></td>
                                    <td><?php echo e($r->departamento_id_resuelto); ?></td>
                                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($r->email); ?>"><?php echo e($r->email); ?></td>
                                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($r->email_laboral); ?>"><?php echo e($r->email_laboral); ?></td>
                                    <td><?php echo e($r->fecha_nacimiento); ?></td>
                                    <td><?php echo e($r->tipo_identificacion); ?></td>
                                    <td><?php echo e($r->sexo); ?></td>
                                    <td><?php echo e($r->celular); ?></td>
                                    <td><?php echo e($r->fecha_ingreso); ?></td>
                                    <td><span class="label label-<?php echo e($r->estado_civil_check==='OK' ? 'success' : 'warning'); ?>"><?php echo e($r->estado_civil_check); ?></span></td>
                                    <td><span class="label label-<?php echo e($r->departamento_check==='OK' ? 'success' : 'warning'); ?>"><?php echo e($r->departamento_check); ?></span></td>
                                    <td><span class="label label-<?php echo e($r->empresa_check==='OK' ? 'success' : 'danger'); ?>"><?php echo e($r->empresa_check); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="panel-footer">
                    <?php echo e($rows->links()); ?>

                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/personas/import/preview.blade.php ENDPATH**/ ?>