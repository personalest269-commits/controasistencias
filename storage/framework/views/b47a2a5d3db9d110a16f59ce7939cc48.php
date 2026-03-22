<?php $__env->startSection('content'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/select2/css/select2.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('vendor/flatpickr/flatpickr.min.css')); ?>">
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestión de personas</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if($errors->any() && !session('open_modal_nueva_persona')): ?>
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
                        <form method="GET" action="<?php echo e(route('PersonasIndex')); ?>" class="form-inline" style="gap:8px;">
                            <input type="text" name="q" value="<?php echo e($q); ?>" class="form-control" placeholder="Buscar por ID, cédula, nombres, departamento…">

                            <select name="departamento_id" id="filtro_departamento" class="form-control" style="min-width:320px;">
                                <option value="">-- Departamento (todos) --</option>
                                <?php $__currentLoopData = ($departamentos ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($d->id); ?>" <?php echo e(($departamentoId ?? null) == $d->id ? 'selected' : ''); ?>>
                                        <?php echo e($d->descripcion); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>

                            <select name="empresa_id" id="filtro_empresa" class="form-control" style="min-width:260px;">
                                <option value="">-- Empresa (todas) --</option>
                                <?php $__currentLoopData = ($empresas ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($e->id); ?>" <?php echo e(($empresaId ?? null) == $e->id ? 'selected' : ''); ?>>
                                        <?php echo e($e->nombre); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php if($soloEliminados): ?>
                                <input type="hidden" name="eliminados" value="1" />
                            <?php endif; ?>
                            <?php if(!empty($soloInactivos)): ?>
                                <input type="hidden" name="inactivos" value="1" />
                            <?php endif; ?>
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                            <?php if($q || ($departamentoId ?? null) || ($empresaId ?? null)): ?>
                                <a class="btn btn-light" href="<?php echo e(route('PersonasIndex', array_filter([
                                    'eliminados' => $soloEliminados ? 1 : null,
                                    'inactivos' => !empty($soloInactivos) ? 1 : null,
                                ]))); ?>">Limpiar</a>
                            <?php endif; ?>
                        </form>

                        <div>
                            <a class="btn btn-secondary" href="<?php echo e(route('PersonasIndex', $soloEliminados ? array_filter(['inactivos' => !empty($soloInactivos) ? 1 : null]) : array_filter(['eliminados' => 1, 'inactivos' => !empty($soloInactivos) ? 1 : null]))); ?>">
                                <?php echo e($soloEliminados ? 'Ver activos' : 'Ver eliminados'); ?>

                            </a>

                            <a class="btn btn-secondary" href="<?php echo e(route('PersonasIndex', !empty($soloInactivos) ? array_filter(['eliminados' => $soloEliminados ? 1 : null]) : array_filter(['inactivos' => 1, 'eliminados' => $soloEliminados ? 1 : null]))); ?>">
                                <?php echo e(!empty($soloInactivos) ? 'Ver activos' : 'Ver inactivos'); ?>

                            </a>

                            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaPersona">
                                Nuevo
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:90px;">Foto</th>
                                <th>ID</th>
                                <th>Identificación</th>
                                <th>Nombre</th>
                                <th>Departamento</th>
                                <th>Email</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $personas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $persona): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <?php if(optional($persona->fotoActual)->id_archivo): ?>
                                            <a href="<?php echo e(route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo)); ?>" target="_blank" title="Ver">
                                                <img src="<?php echo e(route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo)); ?>" alt="" style="max-width:70px; height:auto; border-radius:4px;">
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo e($persona->id); ?></strong></td>
                                    <td><?php echo e($persona->identificacion ?? '-'); ?></td>
                                    <td><?php echo e($persona->nombre_completo ?: '-'); ?></td>
                                    <td><?php echo e(optional($persona->departamento)->descripcion ?? '-'); ?></td>
                                    <td><?php echo e($persona->email ?? '-'); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="<?php echo e(route('PersonasEdit', $persona->id)); ?>">Editar</a>
                                        <?php if(is_null($persona->estado)): ?>
                                            <form action="<?php echo e(route('PersonasDelete', $persona->id)); ?>" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar esta persona? Se marcará como X (eliminación lógica).')">
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
                                    <td colspan="7" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <?php echo e($personas->appends(request()->query())->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Persona -->
<div class="modal fade" id="modalNuevaPersona" tabindex="-1" role="dialog" aria-labelledby="modalNuevaPersonaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="<?php echo e(route('PersonasStore')); ?>" method="POST" enctype="multipart/form-data" novalidate>
        <?php echo csrf_field(); ?>
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevaPersonaLabel">Nueva persona</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
            <?php if($errors->any() && session('open_modal_nueva_persona')): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" class="form-control <?php echo e($errors->has('tipo') ? 'is-invalid' : ''); ?>">
                            <option value="N" <?php echo e(old('tipo','N')=='N' ? 'selected' : ''); ?>>Natural (N)</option>
                            <option value="J" <?php echo e(old('tipo','N')=='J' ? 'selected' : ''); ?>>Jurídico (J)</option>
                        </select>
                        <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label>Tipo identificación <span class="text-danger">*</span></label>
                        <select name="tipo_identificacion" id="tipo_identificacion" class="form-control <?php echo e($errors->has('tipo_identificacion') ? 'is-invalid' : ''); ?>" required>
                            <?php $__currentLoopData = $tiposIdentificacion; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ti): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option
                                    value="<?php echo e($ti->codigo); ?>"
                                    data-validar="<?php echo e((int) $ti->validar); ?>"
                                    data-longitud="<?php echo e($ti->longitud ?? ''); ?>"
                                    data-longitud_fija="<?php echo e((int) ($ti->longitud_fija ?? 0)); ?>"
                                    data-descripcion="<?php echo e($ti->descripcion); ?>"
                                    <?php echo e(old('tipo_identificacion','2') == $ti->codigo ? 'selected' : ''); ?>

                                >
                                    <?php echo e($ti->descripcion); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['tipo_identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted d-block mt-1">El ID se genera automáticamente (no se muestra en el formulario).</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nombres <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" class="form-control <?php echo e($errors->has('nombres') ? 'is-invalid' : ''); ?>" maxlength="255" value="<?php echo e(old('nombres')); ?>">
                        <?php $__errorArgs = ['nombres'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Apellido 1 <span class="text-danger">*</span></label>
                        <input type="text" name="apellido1" class="form-control <?php echo e($errors->has('apellido1') ? 'is-invalid' : ''); ?>" maxlength="20" value="<?php echo e(old('apellido1')); ?>">
                        <?php $__errorArgs = ['apellido1'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Apellido 2</label>
                        <input type="text" name="apellido2" class="form-control <?php echo e($errors->has('apellido2') ? 'is-invalid' : ''); ?>" maxlength="20" value="<?php echo e(old('apellido2')); ?>">
                        <?php $__errorArgs = ['apellido2'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Identificación <span class="text-danger">*</span></label>
                        <input type="text" name="identificacion" id="identificacion" class="form-control <?php echo e($errors->has('identificacion') ? 'is-invalid' : ''); ?>" maxlength="15" required value="<?php echo e(old('identificacion')); ?>">
                        <small id="identificacionHelp" class="text-muted"></small>
                        <div class="invalid-feedback" id="identificacionFeedback"></div>
                        <?php $__errorArgs = ['identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Celular</label>
                        <input type="text" name="celular" class="form-control <?php echo e($errors->has('celular') ? 'is-invalid' : ''); ?>" maxlength="30" value="<?php echo e(old('celular')); ?>">
                        <?php $__errorArgs = ['celular'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo e($errors->has('email') ? 'is-invalid' : ''); ?>" maxlength="50" value="<?php echo e(old('email')); ?>">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Empresa <span class="text-danger">*</span></label>
                        <select name="empresa_id" id="empresa_id_create" required class="form-control <?php echo e($errors->has('empresa_id') ? 'is-invalid' : ''); ?>">
                            <option value="">-- Seleccione --</option>
                            <?php $__currentLoopData = ($empresas ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($e->id); ?>" <?php echo e(old('empresa_id') == $e->id ? 'selected' : ''); ?>>
                                    <?php echo e($e->nombre); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['empresa_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Departamento</label>
                        <select name="departamento_id" id="departamento_id_create" class="form-control <?php echo e($errors->has('departamento_id') ? 'is-invalid' : ''); ?>">
                            <option value="">-- Seleccione --</option>
                            <?php $__currentLoopData = ($departamentos ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($d->id); ?>" <?php echo e(old('departamento_id') == $d->id ? 'selected' : ''); ?>>
                                    <?php echo e($d->descripcion); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['departamento_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control <?php echo e($errors->has('direccion') ? 'is-invalid' : ''); ?>" maxlength="255" value="<?php echo e(old('direccion')); ?>">
                        <?php $__errorArgs = ['direccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha nacimiento</label>
                        <input type="text" name="fecha_nacimiento" id="fecha_nacimiento_new" class="form-control js-date-dmy <?php echo e($errors->has('fecha_nacimiento') ? 'is-invalid' : ''); ?>" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" value="<?php echo e(old('fecha_nacimiento')); ?>">
                        <?php $__errorArgs = ['fecha_nacimiento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado civil</label>
                        <select name="cod_estado_civil" class="form-control <?php echo e($errors->has('cod_estado_civil') ? 'is-invalid' : ''); ?>">
                            <option value="">-- Seleccione --</option>
                            <?php $__currentLoopData = $estadosCiviles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($ec->codigo); ?>" <?php echo e(old('cod_estado_civil') == $ec->codigo ? 'selected' : ''); ?>>
                                    <?php echo e($ec->descripcion); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['cod_estado_civil'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" class="form-control <?php echo e($errors->has('foto') ? 'is-invalid' : ''); ?>" accept="image/*">
                        <?php $__errorArgs = ['foto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted">Se guarda en ad_archivo_digital.digital (cifrado) y se relaciona en pg_persona_foto.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Sexo</label>
                        <select name="sexo" class="form-control <?php echo e($errors->has('sexo') ? 'is-invalid' : ''); ?>">
                            <option value="">-- Seleccione --</option>
                            <option value="M" <?php echo e(old('sexo') == 'M' ? 'selected' : ''); ?>>Masculino (M)</option>
                            <option value="F" <?php echo e(old('sexo') == 'F' ? 'selected' : ''); ?>>Femenino (F)</option>
                        </select>
                        <?php $__errorArgs = ['sexo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <hr>

            <?php ($theme = config('sysconfig.theme')); ?>
            <div class="form-group">
                <?php if($theme === 'gentelella'): ?>
                    <label style="font-weight:normal;">
                        <input type="checkbox" id="crearUsuarioCheck" name="crear_usuario" value="1" onchange="toggleUsuarioNuevo(this);" <?php echo e(old('crear_usuario') ? 'checked' : ''); ?>>
                        Crear usuario
                    </label>
                <?php else: ?>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="crearUsuarioCheck" name="crear_usuario" value="1" onchange="toggleUsuarioNuevo(this);" <?php echo e(old('crear_usuario') ? 'checked' : ''); ?>>
                        <label class="custom-control-label" for="crearUsuarioCheck">Crear usuario</label>
                    </div>
                <?php endif; ?>
                <?php $__errorArgs = ['crear_usuario'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger d-block mt-1"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div id="bloqueUsuario" style="display:none;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Usuario <span class="text-danger">*</span></label>
                            <input type="text" id="usuario_new" name="usuario" class="form-control <?php echo e($errors->has('usuario') ? 'is-invalid' : ''); ?>" placeholder="Se llena con la identificación" value="<?php echo e(old('usuario')); ?>" readonly>
                            <?php $__errorArgs = ['usuario'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Rol del usuario <span class="text-danger">*</span></label>
                            <select id="usuario_role_new" name="usuario_role_id" class="form-control <?php echo e($errors->has('usuario_role_id') ? 'is-invalid' : ''); ?>">
                                <option value="">-- Seleccione --</option>
                                <?php $__currentLoopData = ($roles ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php ($rName = trim((string) ($r->display_name ?? $r->name)) ?: ('Rol #' . $r->id)); ?>
                                    <option value="<?php echo e($r->id); ?>" <?php echo e((string) old('usuario_role_id', '2') === (string) $r->id ? 'selected' : ''); ?>>
                                        <?php echo e($rName); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['usuario_role_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="usuario_password_new" name="usuario_password" class="form-control <?php echo e($errors->has('usuario_password') ? 'is-invalid' : ''); ?>" placeholder="******">
                            <?php $__errorArgs = ['usuario_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <label class="text-danger"><?php echo e($message); ?></label> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                <small class="text-muted">Se crea en pg_usuario con usuario = identificación (según tipo seleccionado) y se asigna el rol seleccionado.</small>
            </div>
        </div><!-- /.modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* Asegura que el footer quede dentro del modal y no “se salga” del overlay */
#modalNuevaPersona .modal-dialog { margin-top: 1.75rem; }
#modalNuevaPersona .modal-content { border-radius: .3rem; }
</style>

<?php $__env->startSection('footer'); ?>
<?php echo \Illuminate\View\Factory::parentPlaceholder('footer'); ?>

<!-- Select2 (búsqueda en bandbox/combos) -->
<script src="<?php echo e(asset('admin_lte/plugins/select2/js/select2.full.min.js')); ?>"></script>

<!-- Flatpickr (fecha nacimiento dd/mm/aaaa) -->
<script src="<?php echo e(asset('vendor/flatpickr/flatpickr.min.js')); ?>"></script>
<script src="<?php echo e(asset('vendor/flatpickr/l10n/es.js')); ?>"></script>

<script>
(function () {
    // Select2: permite escribir y buscar departamentos (nuevo + filtro)
    function initSelect2() {
        if (!window.jQuery || !$.fn.select2) return;

        $('#filtro_empresa').select2({
            width: 'resolve',
            theme: 'bootstrap4',
            placeholder: '-- Empresa (todas) --',
            allowClear: true
        });

        $('#filtro_departamento').select2({
            width: 'resolve',
            theme: 'bootstrap4',
            placeholder: '-- Departamento (todos) --',
            allowClear: true
        });

        $('#filtro_departamento').select2({
            width: 'resolve',
            theme: 'bootstrap4',
            placeholder: '-- Departamento (todos) --',
            allowClear: true
        });

        // Dentro de modal: usar dropdownParent para que el desplegable no se oculte
        $('#departamento_id_create').select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: '-- Seleccione --',
            allowClear: true,
            dropdownParent: $('#modalNuevaPersona')
        });

        $('#empresa_id_create').select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: '-- Seleccione --',
            allowClear: false,
            dropdownParent: $('#modalNuevaPersona')
        });
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function () {
        initSelect2();
    });

    function getSelectedCfg() {
        var sel = document.getElementById('tipo_identificacion');
        if (!sel) return null;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return null;
        return {
            codigo: (opt.value || '').trim(),
            descripcion: (opt.dataset.descripcion || '').trim(),
            validar: parseInt(opt.dataset.validar || '0', 10) || 0,
            longitud: opt.dataset.longitud ? parseInt(opt.dataset.longitud, 10) : null,
            longitud_fija: parseInt(opt.dataset.longitud_fija || '0', 10) || 0,
        };
    }

    function isValidCedulaEc(cedula) {
        if (!/^\d{10}$/.test(cedula)) return false;
        var provincia = parseInt(cedula.substring(0, 2), 10);
        if (provincia < 1 || provincia > 24) return false;
        var tercer = parseInt(cedula.charAt(2), 10);
        if (tercer > 5) return false;
        var suma = 0;
        for (var i = 0; i < 9; i++) {
            var d = parseInt(cedula.charAt(i), 10);
            if (i % 2 === 0) {
                d = d * 2;
                if (d > 9) d = d - 9;
            }
            suma += d;
        }
        var ver = (10 - (suma % 10)) % 10;
        return ver === parseInt(cedula.charAt(9), 10);
    }

    function applyMask() {
        var cfg = getSelectedCfg();
        var input = document.getElementById('identificacion');
        var help = document.getElementById('identificacionHelp');
        if (!cfg || !input) return;

        // Maxlength según catálogo
        if (cfg.longitud && cfg.longitud > 0) {
            input.maxLength = cfg.longitud;
        } else {
            input.maxLength = 15;
        }

        // Hints / patrón
        input.removeAttribute('pattern');
        input.removeAttribute('inputmode');

        var desc = (cfg.descripcion || '').toUpperCase();
        if (cfg.validar === 1) {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '\\d*');
            help.textContent = 'Cédula: 10 dígitos (validación ecuatoriana).';
        } else if (desc.indexOf('R.U.C') >= 0 || desc.indexOf('RUC') >= 0) {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '\\d*');
            help.textContent = 'RUC: 13 dígitos.';
        } else {
            help.textContent = cfg.longitud ? ('Máximo ' + cfg.longitud + ' caracteres.') : '';
        }
    }

    function setInvalid(msg) {
        var input = document.getElementById('identificacion');
        var fb = document.getElementById('identificacionFeedback');
        if (!input || !fb) return false;
        input.classList.add('is-invalid');
        fb.textContent = msg || 'Identificación inválida.';
        return false;
    }

    function clearInvalid() {
        var input = document.getElementById('identificacion');
        var fb = document.getElementById('identificacionFeedback');
        if (!input || !fb) return true;
        input.classList.remove('is-invalid');
        fb.textContent = '';
        return true;
    }

    function validateIdentificacion() {
        var cfg = getSelectedCfg();
        var input = document.getElementById('identificacion');
        if (!cfg || !input) return true;
        var val = (input.value || '').trim();

        if (val === '') {
            return setInvalid('La identificación es obligatoria.');
        }

        if (cfg.longitud && cfg.longitud > 0) {
            if (cfg.longitud_fija === 1 && val.length !== cfg.longitud) {
                return setInvalid('Debe tener exactamente ' + cfg.longitud + ' caracteres.');
            }
            if (cfg.longitud_fija !== 1 && val.length > cfg.longitud) {
                return setInvalid('No debe exceder ' + cfg.longitud + ' caracteres.');
            }
        }

        var desc = (cfg.descripcion || '').toUpperCase();
        if (cfg.validar === 1) {
            if (!isValidCedulaEc(val)) {
                return setInvalid('La cédula ingresada no es válida.');
            }
        } else if (desc.indexOf('R.U.C') >= 0 || desc.indexOf('RUC') >= 0) {
            if (!/^\d{13}$/.test(val)) {
                return setInvalid('El RUC debe tener 13 dígitos numéricos.');
            }
        }

        return clearInvalid();
    }

    // Eventos
    document.addEventListener('DOMContentLoaded', function () {
    <?php if(session('open_modal_nueva_persona')): ?>
        try { $('#modalNuevaPersona').modal('show'); } catch (e) { /* ignore */ }
    <?php endif; ?>

        var sel = document.getElementById('tipo_identificacion');
        var input = document.getElementById('identificacion');
        var form = document.querySelector('#modalNuevaPersona form');
        if (!sel || !input) return;

        applyMask();

        sel.addEventListener('change', function () {
            applyMask();
            if ((input.value || '').trim() !== '') {
                validateIdentificacion();
            } else {
                clearInvalid();
            }
        });
        // Auto-detección: 10 dígitos -> CÉDULA, 13 dígitos -> RUC, alfanumérico -> PASAPORTE
        input.addEventListener('input', function () {
            try {
                var raw = (input.value || '').trim();
                if (!raw) return;
                var alnum = raw.replace(/[^a-zA-Z0-9]/g, '');

                function pickOption(predicate) {
                    for (var i = 0; i < sel.options.length; i++) {
                        var o = sel.options[i];
                        if (predicate(o)) {
                            sel.selectedIndex = i;
                            applyMask();
                            break;
                        }
                    }
                }

                if (/^\d+$/.test(alnum)) {
                    if (alnum.length === 10) {
                        pickOption(function (o) {
                            var v = parseInt(o.getAttribute('data-validar') || '0', 10) || 0;
                            var d = (o.getAttribute('data-descripcion') || o.textContent || '').toUpperCase();
                            return v === 1 || d.indexOf('CEDULA') >= 0;
                        });
                    } else if (alnum.length === 13) {
                        pickOption(function (o) {
                            var d = (o.getAttribute('data-descripcion') || o.textContent || '').toUpperCase();
                            return d.indexOf('RUC') >= 0 || d.indexOf('R.U.C') >= 0;
                        });
                    }
                } else {
                    pickOption(function (o) {
                        var d = (o.getAttribute('data-descripcion') || o.textContent || '').toUpperCase();
                        return d.indexOf('PASAPORTE') >= 0;
                    });
                }
            } catch (e) { /* ignore */ }
        });
        input.addEventListener('blur', validateIdentificacion);

        if (form) {
            form.addEventListener('submit', function (e) {
                if (!validateIdentificacion()) {
                    e.preventDefault();
                    e.stopPropagation();
                    input.focus();
                }
            });
        }
    });
})();

// Toggle campos de usuario (Nuevo)
function toggleUsuarioNuevo(chk) {
    var bloque = document.getElementById('bloqueUsuario');
    var usuario = document.getElementById('usuario_new');
    var rolUser = document.getElementById('usuario_role_new');
    var pass = document.getElementById('usuario_password_new');
    var identificacion = document.getElementById('identificacion');

    var on = !!(chk && chk.checked);

    if (bloque) bloque.style.display = on ? 'block' : 'none';

    // Usuario siempre requerido cuando se crea usuario (se llena desde identificación)
    if (usuario) {
        usuario.required = on;
        usuario.value = computeUsuarioFromIdentNuevo();
    }

    // Password siempre requerido cuando se crea usuario
    if (pass) pass.required = on;

    // Rol siempre requerido cuando se crea usuario
    if (rolUser) {
        rolUser.required = on;
        if (!on) {
            rolUser.value = '';
        } else if (!rolUser.value) {
            // Default: Admin (id=2) si existe en el combo
            rolUser.value = '2';
        }
    }

    if (!on) {
        if (usuario) usuario.value = '';
        if (pass) pass.value = '';
    }
}

function computeUsuarioFromIdentNuevo() {
    var identificacion = document.getElementById('identificacion');
    var sel = document.getElementById('tipo_identificacion');
    var val = (identificacion && identificacion.value ? identificacion.value : '').trim();
    if (!val) return '';

    var desc = '';
    var validar = 0;
    if (sel && sel.options && sel.selectedIndex >= 0) {
        var opt = sel.options[sel.selectedIndex];
        desc = (opt.getAttribute('data-descripcion') || opt.textContent || '').toUpperCase();
        validar = parseInt(opt.getAttribute('data-validar') || '0', 10) || 0;
    }
    var esNumerico = (validar === 1) || desc.indexOf('RUC') >= 0 || desc.indexOf('R.U.C') >= 0;
    if (esNumerico) {
        return val.replace(/\D+/g, '');
    }
    return val.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function syncUsuarioFromIdentNuevo() {
    var chk = document.getElementById('crearUsuarioCheck');
    if (!chk || !chk.checked) return;
    var usuario = document.getElementById('usuario_new');
    if (!usuario) return;
    usuario.value = computeUsuarioFromIdentNuevo();
}

document.addEventListener('DOMContentLoaded', function () {
    <?php if(session('open_modal_nueva_persona')): ?>
        try { $('#modalNuevaPersona').modal('show'); } catch (e) { /* ignore */ }
    <?php endif; ?>

    var chk = document.getElementById('crearUsuarioCheck');
    if (chk) toggleUsuarioNuevo(chk);

    var identificacion = document.getElementById('identificacion');
    if (identificacion) {
        identificacion.addEventListener('input', syncUsuarioFromIdentNuevo);
        identificacion.addEventListener('blur', syncUsuarioFromIdentNuevo);
    }

    var sel = document.getElementById('tipo_identificacion');
    if (sel) {
        sel.addEventListener('change', syncUsuarioFromIdentNuevo);
    }
});

// Máscara simple para fecha dd/mm/aaaa (compatible Bootstrap 3/4)
function attachDateMaskDMY(el) {
    if (!el) return;
    el.setAttribute('maxlength', '10');
    el.setAttribute('inputmode', 'numeric');

    el.addEventListener('input', function () {
        var v = (el.value || '').replace(/[^0-9]/g, '').slice(0, 8);
        var out = '';
        if (v.length <= 2) {
            out = v;
        } else if (v.length <= 4) {
            out = v.slice(0, 2) + '/' + v.slice(2);
        } else {
            out = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4);
        }
        el.value = out;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    try {
        if (window.flatpickr) {
            var common = {
                dateFormat: 'd/m/Y',
                allowInput: true,
                locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
            };
            document.querySelectorAll('.js-date-dmy').forEach(function (el) {
                try { flatpickr(el, common); } catch (e) { /* ignore */ }
            });
        } else {
            document.querySelectorAll('.js-date-dmy').forEach(attachDateMaskDMY);
        }
    } catch (e) { /* ignore */ }
});

</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make("templates.".config("sysconfig.theme").".master", array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/personas/index.blade.php ENDPATH**/ ?>