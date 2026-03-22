@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Empleados Despedidos (VIGENTE='N')</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any() && !session('open_modal_nueva_persona'))
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                        <form method="GET" action="{{ route('PersonasIndex') }}" class="form-inline" style="gap:8px;">
                            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por ID, cédula, nombres, departamento…">

                            <select name="departamento_id" id="filtro_departamento" class="form-control" style="min-width:320px;">
                                <option value="">-- Departamento (todos) --</option>
                                @foreach(($departamentos ?? []) as $d)
                                    <option value="{{ $d->id }}" {{ ($departamentoId ?? null) == $d->id ? 'selected' : '' }}>
                                        {{ $d->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @if($soloEliminados)
                                <input type="hidden" name="eliminados" value="1" />
                            @endif
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                            @if($q || ($departamentoId ?? null))
                                <a class="btn btn-light" href="{{ route('PersonasIndex', $soloEliminados ? ['eliminados' => 1] : []) }}">Limpiar</a>
                            @endif
                        </form>

                        <div>
                            <a class="btn btn-secondary" href="{{ route('PersonasIndex', $soloEliminados ? [] : ['eliminados' => 1]) }}">
                                {{ $soloEliminados ? 'Ver activos' : 'Ver eliminados' }}
                            </a>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaPersona">
                                Volver a vigentes
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
                            @forelse($personas as $persona)
                                <tr>
                                    <td>
                                        @if(optional($persona->fotoActual)->id_archivo)
                                            <a href="{{ route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo) }}" target="_blank" title="Ver">
                                                <img src="{{ route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo) }}" alt="" style="max-width:70px; height:auto; border-radius:4px;">
                                            </a>
                                        @endif
                                    </td>
                                    <td><strong>{{ $persona->id }}</strong></td>
                                    <td>{{ $persona->identificacion ?? '-' }}</td>
                                    <td>{{ $persona->nombre_completo ?: '-' }}</td>
                                    <td>{{ optional($persona->departamento)->descripcion ?? '-' }}</td>
                                    <td>{{ $persona->email ?? '-' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="{{ route('PersonasEdit', $persona->id) }}">Editar</a>
                                        @if(is_null($persona->estado))
                                            <form action="{{ route('PersonasDelete', $persona->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar esta persona? Se marcará como X (eliminación lógica).')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        @else
                                            <span class="badge badge-danger">X</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $personas->appends(request()->query())->links() }}
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
      <form action="{{ route('PersonasStore') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevaPersonaLabel">Nueva persona</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
            @if ($errors->any() && session('open_modal_nueva_persona'))
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" class="form-control {{ $errors->has('tipo') ? 'is-invalid' : '' }}">
                            <option value="N" {{ old('tipo','N')=='N' ? 'selected' : '' }}>Natural (N)</option>
                            <option value="J" {{ old('tipo','N')=='J' ? 'selected' : '' }}>Jurídico (J)</option>
                        </select>
                        @error('tipo') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label>Tipo identificación <span class="text-danger">*</span></label>
                        <select name="tipo_identificacion" id="tipo_identificacion" class="form-control {{ $errors->has('tipo_identificacion') ? 'is-invalid' : '' }}" required>
                            @foreach($tiposIdentificacion as $ti)
                                <option
                                    value="{{ $ti->codigo }}"
                                    data-validar="{{ (int) $ti->validar }}"
                                    data-longitud="{{ $ti->longitud ?? '' }}"
                                    data-longitud_fija="{{ (int) ($ti->longitud_fija ?? 0) }}"
                                    data-descripcion="{{ $ti->descripcion }}"
                                    {{ old('tipo_identificacion','2') == $ti->codigo ? 'selected' : '' }}
                                >
                                    {{ $ti->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_identificacion') <label class="text-danger">{{ $message }}</label> @enderror
                        <small class="text-muted d-block mt-1">El ID se genera automáticamente (no se muestra en el formulario).</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nombres <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" class="form-control {{ $errors->has('nombres') ? 'is-invalid' : '' }}" maxlength="255" value="{{ old('nombres') }}">
                        @error('nombres') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Apellido 1 <span class="text-danger">*</span></label>
                        <input type="text" name="apellido1" class="form-control {{ $errors->has('apellido1') ? 'is-invalid' : '' }}" maxlength="20" value="{{ old('apellido1') }}">
                        @error('apellido1') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Apellido 2</label>
                        <input type="text" name="apellido2" class="form-control {{ $errors->has('apellido2') ? 'is-invalid' : '' }}" maxlength="20" value="{{ old('apellido2') }}">
                        @error('apellido2') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Identificación <span class="text-danger">*</span></label>
                        <input type="text" name="identificacion" id="identificacion" class="form-control {{ $errors->has('identificacion') ? 'is-invalid' : '' }}" maxlength="15" required value="{{ old('identificacion') }}">
                        <small id="identificacionHelp" class="text-muted"></small>
                        <div class="invalid-feedback" id="identificacionFeedback"></div>
                        @error('identificacion') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Celular</label>
                        <input type="text" name="celular" class="form-control {{ $errors->has('celular') ? 'is-invalid' : '' }}" maxlength="30" value="{{ old('celular') }}">
                        @error('celular') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" maxlength="50" value="{{ old('email') }}">
                        @error('email') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Departamento</label>
                        <select name="departamento_id" id="departamento_id_create" class="form-control {{ $errors->has('departamento_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Seleccione --</option>
                            @foreach(($departamentos ?? []) as $d)
                                <option value="{{ $d->id }}" {{ old('departamento_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('departamento_id') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control {{ $errors->has('direccion') ? 'is-invalid' : '' }}" maxlength="255" value="{{ old('direccion') }}">
                        @error('direccion') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fecha nacimiento</label>
                        <input type="text" name="fecha_nacimiento" id="fecha_nacimiento_new" class="form-control js-date-dmy {{ $errors->has('fecha_nacimiento') ? 'is-invalid' : '' }}" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" value="{{ old('fecha_nacimiento') }}">
                        @error('fecha_nacimiento') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado civil</label>
                        <select name="cod_estado_civil" class="form-control {{ $errors->has('cod_estado_civil') ? 'is-invalid' : '' }}">
                            <option value="">-- Seleccione --</option>
                            @foreach($estadosCiviles as $ec)
                                <option value="{{ $ec->codigo }}" {{ old('cod_estado_civil') == $ec->codigo ? 'selected' : '' }}>
                                    {{ $ec->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('cod_estado_civil') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" class="form-control {{ $errors->has('foto') ? 'is-invalid' : '' }}" accept="image/*">
                        @error('foto') <label class="text-danger">{{ $message }}</label> @enderror
                        <small class="text-muted">Se guarda en ad_archivo_digital.digital (cifrado) y se relaciona en pg_persona_foto.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Sexo</label>
                        <select name="sexo" class="form-control {{ $errors->has('sexo') ? 'is-invalid' : '' }}">
                            <option value="">-- Seleccione --</option>
                            <option value="M" {{ old('sexo') == 'M' ? 'selected' : '' }}>Masculino (M)</option>
                            <option value="F" {{ old('sexo') == 'F' ? 'selected' : '' }}>Femenino (F)</option>
                        </select>
                        @error('sexo') <label class="text-danger">{{ $message }}</label> @enderror
                    </div>
                </div>
            </div>

            <hr>

            @php($theme = config('sysconfig.theme'))
            <div class="form-group">
                @if($theme === 'gentelella')
                    <label style="font-weight:normal;">
                        <input type="checkbox" id="crearUsuarioCheck" name="crear_usuario" value="1" onchange="toggleUsuarioVolver a vigentes(this);" {{ old('crear_usuario') ? 'checked' : '' }}>
                        Crear usuario
                    </label>
                @else
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="crearUsuarioCheck" name="crear_usuario" value="1" onchange="toggleUsuarioVolver a vigentes(this);" {{ old('crear_usuario') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="crearUsuarioCheck">Crear usuario</label>
                    </div>
                @endif
                @error('crear_usuario') <label class="text-danger d-block mt-1">{{ $message }}</label> @enderror
            </div>

            <div id="bloqueUsuario" style="display:none;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email usuario <span class="text-danger">*</span></label>
                            <input type="email" id="usuario_email_new" name="usuario_email" class="form-control {{ $errors->has('usuario_email') ? 'is-invalid' : '' }}" placeholder="usuario@correo.com" value="{{ old('usuario_email') }}">
                            @error('usuario_email') <label class="text-danger">{{ $message }}</label> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Rol del usuario <span class="text-danger">*</span></label>
                            <select id="usuario_role_new" name="usuario_role_id" class="form-control {{ $errors->has('usuario_role_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Seleccione --</option>
                                @foreach(($roles ?? []) as $r)
                                    @php($rName = trim((string) ($r->display_name ?? $r->name)) ?: ('Rol #' . $r->id))
                                    <option value="{{ $r->id }}" {{ (string) old('usuario_role_id', '2') === (string) $r->id ? 'selected' : '' }}>
                                        {{ $rName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('usuario_role_id') <label class="text-danger">{{ $message }}</label> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="usuario_password_new" name="usuario_password" class="form-control {{ $errors->has('usuario_password') ? 'is-invalid' : '' }}" placeholder="******">
                            @error('usuario_password') <label class="text-danger">{{ $message }}</label> @enderror
                        </div>
                    </div>
                </div>
                <small class="text-muted">Se crea en pg_usuario con id_persona = ID de la persona y se asigna el rol seleccionado.</small>
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

@section('footer')
@parent

<!-- Select2 (búsqueda en bandbox/combos) -->
<script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
(function () {
    // Select2: permite escribir y buscar departamentos (nuevo + filtro)
    function initSelect2() {
        if (!window.jQuery || !$.fn.select2) return;

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
    @if(session('open_modal_nueva_persona'))
        try { $('#modalNuevaPersona').modal('show'); } catch (e) { /* ignore */ }
    @endif

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

// Toggle campos de usuario (Volver a vigentes)
function toggleUsuarioVolver a vigentes(chk) {
    var bloque = document.getElementById('bloqueUsuario');
    var emailUser = document.getElementById('usuario_email_new');
    var rolUser = document.getElementById('usuario_role_new');
    var pass = document.getElementById('usuario_password_new');
    var emailPersona = document.querySelector('#modalNuevaPersona input[name="email"]');

    var on = !!(chk && chk.checked);

    if (bloque) bloque.style.display = on ? 'block' : 'none';

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

    if (emailUser) {
        if (on) {
            var vPersona = (emailPersona && emailPersona.value ? emailPersona.value.trim() : '');
            if (vPersona !== '') {
                emailUser.value = vPersona;
                emailUser.readOnly = true;
                // si ya está el correo en persona, no exigir otro
                emailUser.required = false;
            } else {
                emailUser.readOnly = false;
                emailUser.required = true;
            }
        } else {
            emailUser.readOnly = false;
            emailUser.required = false;
            emailUser.value = '';
        }
    }

    if (!on) {
        if (pass) pass.value = '';
    }
}

function syncUsuarioEmailFromPersonaVolver a vigentes() {
    var chk = document.getElementById('crearUsuarioCheck');
    if (!chk || !chk.checked) return;
    var emailPersona = document.querySelector('#modalNuevaPersona input[name="email"]');
    var emailUser = document.getElementById('usuario_email_new');
    if (!emailPersona || !emailUser) return;
    var vPersona = (emailPersona.value || '').trim();
    if (vPersona !== '') {
        emailUser.value = vPersona;
        emailUser.readOnly = true;
        emailUser.required = false;
    } else {
        emailUser.readOnly = false;
        emailUser.required = true;
        // no borrar lo que el usuario haya escrito
    }
}

document.addEventListener('DOMContentLoaded', function () {
    @if(session('open_modal_nueva_persona'))
        try { $('#modalNuevaPersona').modal('show'); } catch (e) { /* ignore */ }
    @endif

    var chk = document.getElementById('crearUsuarioCheck');
    if (chk) toggleUsuarioVolver a vigentes(chk);

    var emailPersona = document.querySelector('#modalNuevaPersona input[name="email"]');
    if (emailPersona) {
        emailPersona.addEventListener('input', syncUsuarioEmailFromPersonaVolver a vigentes);
        emailPersona.addEventListener('blur', syncUsuarioEmailFromPersonaVolver a vigentes);
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
        document.querySelectorAll('.js-date-dmy').forEach(attachDateMaskDMY);
    } catch (e) { /* ignore */ }
});

</script>
@endsection

@stop
