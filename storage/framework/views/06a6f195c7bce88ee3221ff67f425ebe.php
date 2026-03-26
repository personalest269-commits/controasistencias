<?php $__env->startSection('content'); ?>
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Catálogo: Estado civil</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                        <form method="GET" action="<?php echo e(route('EstadoCivilIndex')); ?>" class="form-inline" style="gap:8px;">
                            <input type="text" name="q" value="<?php echo e($q); ?>" class="form-control" placeholder="Buscar por código o descripción...">
                            <?php if($soloEliminados): ?>
                                <input type="hidden" name="eliminados" value="1" />
                            <?php endif; ?>
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                            <?php if($q): ?>
                                <a class="btn btn-light" href="<?php echo e(route('EstadoCivilIndex', $soloEliminados ? ['eliminados' => 1] : [])); ?>">Limpiar</a>
                            <?php endif; ?>
                        </form>

                        <div>
                            <a class="btn btn-secondary" href="<?php echo e(route('EstadoCivilIndex', $soloEliminados ? [] : ['eliminados' => 1])); ?>">
                                <?php echo e($soloEliminados ? 'Ver activos' : 'Ver eliminados'); ?>

                            </a>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevoEstadoCivil">Nuevo</button>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:90px;">Código</th>
                                <th>Descripción</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $registros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><strong><?php echo e($r->codigo); ?></strong></td>
                                    <td><?php echo e($r->descripcion); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="<?php echo e(route('EstadoCivilEdit', $r->id)); ?>">Editar</a>
                                        <?php if(is_null($r->estado)): ?>
                                            <form action="<?php echo e(route('EstadoCivilDelete', $r->id)); ?>" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar este registro? Se marcará como X (eliminación lógica).')">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-danger">X</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <?php echo e($registros->appends(request()->query())->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Estado Civil -->
<div class="modal fade" id="modalNuevoEstadoCivil" tabindex="-1" role="dialog" aria-labelledby="modalNuevoEstadoCivilLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="<?php echo e(route('EstadoCivilStore')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevoEstadoCivilLabel">Nuevo estado civil</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Código</label>
                <input type="text" name="codigo" class="form-control" maxlength="5" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="descripcion" class="form-control" maxlength="255" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/EstadoCivil/index.blade.php ENDPATH**/ ?>