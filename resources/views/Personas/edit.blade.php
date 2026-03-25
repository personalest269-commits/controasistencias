@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar persona: {{ $persona->id }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <a class="btn btn-secondary" href="{{ route('PersonasIndex') }}">Volver</a>
                    @if(is_null($persona->estado))
                        <form action="{{ route('PersonasDelete', $persona->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta persona?')">
                            @csrf
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    @else
                        <span class="badge badge-danger">X</span>
                    @endif
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <strong>Foto actual</strong><br>
                                @if(optional($persona->fotoActual)->id_archivo)
                                    <a href="{{ route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo) }}" target="_blank">
                                        <img src="{{ route('ArchivosDigitalesVer', $persona->fotoActual->id_archivo) }}" alt="" style="max-width:100%; border-radius:6px;">
                                    </a>
                                @else
                                    <div class="text-muted">Sin foto</div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-9">
                            <form action="{{ route('PersonasUpdate', $persona->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                                @csrf

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tipo</label>
                                            <select name="tipo" class="form-control">
                                                <option value="N" {{ old('tipo', $persona->tipo) == 'N' ? 'selected' : '' }}>Natural (N)</option>
                                                <option value="J" {{ old('tipo', $persona->tipo) == 'J' ? 'selected' : '' }}>Jurídico (J)</option>
                                            </select>
                                            @error('tipo_identificacion') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tipo identificación</label>
                                            <select name="tipo_identificacion" id="tipo_identificacion" class="form-control {{ $errors->has('tipo_identificacion') ? 'is-invalid' : '' }}" required>
                                                @foreach($tiposIdentificacion as $ti)
                                                    <option
                                                        value="{{ $ti->codigo }}"
                                                        data-validar="{{ (int) $ti->validar }}"
                                                        data-longitud="{{ $ti->longitud ?? '' }}"
                                                        data-longitud_fija="{{ (int) ($ti->longitud_fija ?? 0) }}"
                                                        data-descripcion="{{ $ti->descripcion }}"
                                                        {{ old('tipo_identificacion', $persona->tipo_identificacion_normalizado) == $ti->codigo ? 'selected' : '' }}
                                                    >
                                                        {{ $ti->descripcion }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identificación</label>
                                            <input type="text" name="identificacion" id="identificacion" class="form-control {{ $errors->has('identificacion') ? 'is-invalid' : '' }}" maxlength="15" value="{{ old('identificacion', $persona->identificacion) }}" required>
                                            <small id="identificacionHelp" class="text-muted"></small>
                                            <div class="invalid-feedback" id="identificacionFeedback"></div>
                                            @error('identificacion') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nombres</label>
                                            <input type="text" name="nombres" class="form-control {{ $errors->has('nombres') ? 'is-invalid' : '' }}" maxlength="255" value="{{ old('nombres', $persona->nombres) }}">
                                            @error('nombres') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Apellido 1</label>
                                            <input type="text" name="apellido1" class="form-control {{ $errors->has('apellido1') ? 'is-invalid' : '' }}" maxlength="20" value="{{ old('apellido1', $persona->apellido1) }}">
                                            @error('apellido1') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Apellido 2</label>
                                            <input type="text" name="apellido2" class="form-control" maxlength="20" value="{{ old('apellido2', $persona->apellido2) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Dirección</label>
                                            <input type="text" name="direccion" class="form-control" maxlength="255" value="{{ old('direccion', $persona->direccion) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha nacimiento</label>
                                            <input type="text" name="fecha_nacimiento" class="form-control js-date-dmy {{ $errors->has('fecha_nacimiento') ? 'is-invalid' : '' }}" placeholder="{{ $pg_date_placeholder_solo ?? 'dd/mm/aaaa' }}" maxlength="20" inputmode="numeric" value="{{ old('fecha_nacimiento', $persona->fecha_nacimiento ? \App\Models\PgConfiguracion::formatFechaSolo($persona->fecha_nacimiento) : '') }}">
                                            @error('fecha_nacimiento') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Estado civil</label>
                                            <select name="cod_estado_civil" class="form-control">
                                                <option value="">-- Seleccione --</option>
                                                @foreach($estadosCiviles as $ec)
                                                    <option value="{{ $ec->codigo }}" {{ old('cod_estado_civil', $persona->cod_estado_civil) == $ec->codigo ? 'selected' : '' }}>
                                                        {{ $ec->descripcion }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Celular</label>
                                            <input type="text" name="celular" class="form-control" maxlength="30" value="{{ old('celular', $persona->celular) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" maxlength="50" value="{{ old('email', $persona->email) }}">
                                            @error('email') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Sexo</label>
                                            <select name="sexo" class="form-control">
                                                <option value="">-- Seleccione --</option>
                                                <option value="M" {{ old('sexo', $persona->sexo) == 'M' ? 'selected' : '' }}>Masculino (M)</option>
                                                <option value="F" {{ old('sexo', $persona->sexo) == 'F' ? 'selected' : '' }}>Femenino (F)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Empresa <span class="text-danger">*</span></label>
                                            <select name="empresa_id" id="empresa_id_edit" required class="form-control {{ $errors->has('empresa_id') ? 'is-invalid' : '' }}">
                                                <option value="">-- Seleccione --</option>
                                                @foreach(($empresas ?? []) as $e)
                                                    <option value="{{ $e->id }}" {{ old('empresa_id', $persona->empresa_id) == $e->id ? 'selected' : '' }}>
                                                        {{ $e->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('empresa_id') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Departamento</label>
                                            <select name="departamento_id" id="departamento_id_edit" class="form-control {{ $errors->has('departamento_id') ? 'is-invalid' : '' }}">
                                                <option value="">-- Seleccione --</option>
                                                @foreach(($departamentos ?? []) as $d)
                                                    <option value="{{ $d->id }}" data-empresa-id="{{ $d->empresa_id }}" {{ old('departamento_id', $persona->departamento_id) == $d->id ? 'selected' : '' }}>
                                                        {{ $d->descripcion }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('departamento_id') <label class="text-danger">{{ $message }}</label> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Cambiar/Agregar foto</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*">
                                    <small class="text-muted">Se guarda en ad_archivo_digital.digital (cifrado) y se relaciona en pg_persona_foto.</small>
                                </div>

                                <hr>

                                @php
                                    $tieneUsuario = $persona->usuarios && $persona->usuarios->count() > 0;
                                @endphp

                                @php($theme = config('sysconfig.theme'))
                                <div class="form-group">
                                    @if($theme === 'gentelella')
                                        <label style="font-weight:normal;">
                                            <input
                                                type="checkbox"
                                                id="crearUsuarioEditCheck"
                                                name="crear_usuario"
                                                value="1"
                                                {{ old('crear_usuario') == '1' ? 'checked' : '' }}
                                                {{ $tieneUsuario ? 'disabled' : '' }}
                                                onchange="toggleUsuarioEdit(this);"
                                            >
                                            Agregar usuario
                                        </label>
                                    @else
                                        <div class="custom-control custom-checkbox">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="crearUsuarioEditCheck"
                                                name="crear_usuario"
                                                value="1"
                                                {{ old('crear_usuario') == '1' ? 'checked' : '' }}
                                                {{ $tieneUsuario ? 'disabled' : '' }}
                                                onchange="toggleUsuarioEdit(this);"
                                            >
                                            <label class="custom-control-label" for="crearUsuarioEditCheck">Agregar usuario</label>
                                        </div>
                                    @endif
                                    
                                    @error('crear_usuario') <label class="text-danger d-block mt-1">{{ $message }}</label> @enderror
@if($tieneUsuario)
                                        <small class="text-muted d-block mt-1">Esta persona ya tiene un usuario asociado. (Si necesitas otro, créalo desde Gestión de usuarios.)</small>
                                    @endif
                                </div>

                                <div id="bloqueUsuarioEdit" style="display:{{ old('crear_usuario') == '1' ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Usuario <span class="text-danger">*</span></label>
                                                <input type="text" id="usuario_edit" name="usuario" class="form-control {{ $errors->has('usuario') ? 'is-invalid' : '' }}" placeholder="Se llena con la identificación" value="{{ old('usuario', (string) ($persona->identificacion ?? '')) }}" readonly {{ $tieneUsuario ? 'disabled' : '' }}>
                                                @error('usuario') <label class="text-danger">{{ $message }}</label> @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Rol del usuario <span class="text-danger">*</span></label>
                                                <select id="usuario_role_edit" name="usuario_role_id" class="form-control {{ $errors->has('usuario_role_id') ? 'is-invalid' : '' }}" {{ $tieneUsuario ? 'disabled' : '' }}>
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
                                                <input type="password" id="usuario_password_edit" name="usuario_password" class="form-control {{ $errors->has('usuario_password') ? 'is-invalid' : '' }}" placeholder="******" {{ $tieneUsuario ? 'disabled' : '' }}>
                                                @error('usuario_password') <label class="text-danger">{{ $message }}</label> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">Se crea en pg_usuario con id_persona = {{ $persona->id }} y se asigna el rol seleccionado.</small>
                                </div>
<button class="btn btn-primary" type="submit">Guardar cambios</button>
                            </form>

                            <hr>

                            <div>
                                <strong>Usuarios asociados</strong>
                                <div class="mt-2">
                                    @forelse($persona->usuarios as $u)
                                        <div class="badge badge-info" style="margin-right:6px;">{{ $u->email }}</div>
                                    @empty
                                        <div class="text-muted">No tiene usuarios asociados.</div>
                                    @endforelse
                                </div>
                                <small class="text-muted d-block mt-2">(pg_usuario.id_persona = {{ $persona->id }})</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('footer')
@parent
<script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>
<script>
// Validación en frontend (cédula) según combo pg_tipo_identificacion
(function () {
    // Select2: permite escribir y buscar departamentos (editar)
    try {
        if (window.jQuery && $.fn.select2) {
            $('#empresa_id_edit').select2({
                width: '100%',
                theme: 'bootstrap4',
                placeholder: '-- Seleccione --',
                allowClear: false
            });
            $('#departamento_id_edit').select2({
                width: '100%',
                theme: 'bootstrap4',
                placeholder: '-- Seleccione --',
                allowClear: true
            });
        }
    } catch (e) {}

    function syncDepartamentosByEmpresa(forceClearSelection) {
        var empresa = document.getElementById('empresa_id_edit');
        var dept = document.getElementById('departamento_id_edit');
        if (!empresa || !dept) return;

        var empresaId = (empresa.value || '').trim();
        var hasSelectedVisible = false;
        var selectedValue = (dept.value || '').trim();

        if (forceClearSelection) {
            selectedValue = '';
            dept.value = '';
            if (window.jQuery && $.fn.select2) {
                $('#departamento_id_edit').val(null).trigger('change');
            }
        }

        for (var i = 0; i < dept.options.length; i++) {
            var opt = dept.options[i];
            if (!opt.value) {
                opt.hidden = false;
                continue;
            }
            var optEmpresaId = (opt.getAttribute('data-empresa-id') || '').trim();
            var visible = !empresaId || optEmpresaId === empresaId;
            opt.hidden = !visible;

            if (visible && selectedValue && opt.value === selectedValue) {
                hasSelectedVisible = true;
            }
        }

        if (selectedValue && !hasSelectedVisible) {
            dept.value = '';
            if (window.jQuery && $.fn.select2) {
                $('#departamento_id_edit').val('').trigger('change.select2');
            }
        } else if (window.jQuery && $.fn.select2) {
            $('#departamento_id_edit').trigger('change.select2');
        }
    }

    function getSelectedCfg() {
        var sel = document.getElementById('tipo_identificacion');
        if (!sel) return null;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return null;
        return {
            codigo: opt.value,
            descripcion: (opt.dataset.descripcion || '').toString(),
            validar: parseInt(opt.dataset.validar || '0', 10) || 0,
            longitud: opt.dataset.longitud ? parseInt(opt.dataset.longitud, 10) : null,
            longitud_fija: parseInt(opt.dataset.longitud_fija || '0', 10) || 0,
        };
    }

    function isValidCedulaEc(cedula) {
        cedula = (cedula || '').trim();
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
        var verificador = (10 - (suma % 10)) % 10;
        return verificador === parseInt(cedula.charAt(9), 10);
    }

    function applyMask() {
        var cfg = getSelectedCfg();
        var input = document.getElementById('identificacion');
        var help = document.getElementById('identificacionHelp');
        if (!cfg || !input) return;

        // Maxlength por longitud
        if (cfg.longitud && cfg.longitud > 0) {
            input.maxLength = cfg.longitud;
        } else {
            input.maxLength = 15;
        }

        // Hint + pattern
        if (cfg.validar === 1) {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '^\\d{10}$');
            if (help) help.textContent = 'Debe ser una cédula válida (10 dígitos).';
        } else if (cfg.descripcion.toUpperCase().indexOf('R.U.C') !== -1 || cfg.descripcion.toUpperCase().indexOf('RUC') !== -1) {
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '^\\d{13}$');
            if (help) help.textContent = 'RUC: normalmente 13 dígitos.';
        } else {
            input.removeAttribute('pattern');
            input.removeAttribute('inputmode');
            if (help) help.textContent = '';
        }
    }

    function setInvalid(msg) {
        var input = document.getElementById('identificacion');
        var fb = document.getElementById('identificacionFeedback');
        if (input) input.classList.add('is-invalid');
        if (fb) fb.textContent = msg || 'Identificación inválida.';
        return false;
    }

    function clearInvalid() {
        var input = document.getElementById('identificacion');
        var fb = document.getElementById('identificacionFeedback');
        if (input) input.classList.remove('is-invalid');
        if (fb) fb.textContent = '';
        return true;
    }

    function validateIdentificacion() {
        var cfg = getSelectedCfg();
        var input = document.getElementById('identificacion');
        if (!cfg || !input) return true;
        var v = (input.value || '').trim();

        if (v === '') {
            return setInvalid('La identificación es obligatoria.');
        }

        if (cfg.longitud && cfg.longitud > 0 && cfg.longitud_fija === 1 && v.length !== cfg.longitud) {
            return setInvalid('Debe tener exactamente ' + cfg.longitud + ' caracteres.');
        }
        if (cfg.longitud && cfg.longitud > 0 && cfg.longitud_fija !== 1 && v.length > cfg.longitud) {
            return setInvalid('No debe exceder ' + cfg.longitud + ' caracteres.');
        }

        if (cfg.validar === 1) {
            if (!isValidCedulaEc(v)) {
                return setInvalid('La cédula ingresada no es válida.');
            }
        }

        return clearInvalid();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('tipo_identificacion');
        var input = document.getElementById('identificacion');
        var form = document.querySelector('form[action*="PersonasUpdate"]');
        var empresaSel = document.getElementById('empresa_id_edit');
        if (!sel || !input) return;

        syncDepartamentosByEmpresa();
        if (empresaSel) {
            empresaSel.addEventListener('change', function () {
                syncDepartamentosByEmpresa(true);
            });
        }

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

// Toggle campos de usuario (Editar)
function toggleUsuarioEdit(chk) {
    var bloque = document.getElementById('bloqueUsuarioEdit');
    var usuario = document.getElementById('usuario_edit');
    var rolUser = document.getElementById('usuario_role_edit');
    var pass = document.getElementById('usuario_password_edit');
    var identificacion = document.getElementById('identificacion');

    var on = !!(chk && chk.checked);

    if (bloque) bloque.style.display = on ? 'block' : 'none';
    if (pass) pass.required = on;

    if (usuario) {
        usuario.required = on;
        usuario.value = computeUsuarioFromIdentEdit();
    }

    // Rol siempre requerido cuando se agrega usuario
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

function computeUsuarioFromIdentEdit() {
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

function syncUsuarioFromIdentEdit() {
    var chk = document.getElementById('crearUsuarioEditCheck');
    if (!chk || !chk.checked) return;
    var usuario = document.getElementById('usuario_edit');
    if (!usuario) return;
    usuario.value = computeUsuarioFromIdentEdit();
}

document.addEventListener('DOMContentLoaded', function () {
    var chk = document.getElementById('crearUsuarioEditCheck');
    if (chk && !chk.disabled) {
        toggleUsuarioEdit(chk);
    }

    var identificacion = document.getElementById('identificacion');
    if (identificacion) {
        identificacion.addEventListener('input', syncUsuarioFromIdentEdit);
        identificacion.addEventListener('blur', syncUsuarioFromIdentEdit);
    }

    var sel = document.getElementById('tipo_identificacion');
    if (sel) {
        sel.addEventListener('change', syncUsuarioFromIdentEdit);
    }
});

// Máscara simple para fecha dd/mm/aaaa
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
@endsection

@stop
