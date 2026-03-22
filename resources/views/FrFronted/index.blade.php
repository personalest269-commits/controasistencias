@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Configuraciones fronted</h4>
            <small class="text-muted">Parametrización del template <b>creative</b> desde base de datos (ES/EN).</small>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <ul class="nav nav-tabs card-header-tabs" id="frTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-pagina" data-toggle="tab" href="#pane-pagina" role="tab">Página</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-menu" data-toggle="tab" href="#pane-menu" role="tab">Menú</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-secciones" data-toggle="tab" href="#pane-secciones" role="tab">Secciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-servicios" data-toggle="tab" href="#pane-servicios" role="tab">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-portafolio" data-toggle="tab" href="#pane-portafolio" role="tab">Portafolio</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                {{-- PAGINA --}}
                <div class="tab-pane fade show active" id="pane-pagina" role="tabpanel">
                    <form action="{{ route('FrFrontedUpdatePagina') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="{{ $pagina->id }}">

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Nombre del sitio (ES)</label>
                                    <input class="form-control" name="nombre_sitio_es" value="{{ old('nombre_sitio_es', $pagina->nombre_sitio_es) }}" maxlength="200">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Nombre del sitio (EN)</label>
                                    <input class="form-control" name="nombre_sitio_en" value="{{ old('nombre_sitio_en', $pagina->nombre_sitio_en) }}" maxlength="200">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Logo (jpg/png/webp)</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                    @if(!empty($pagina->logo_archivo_id))
                                        <div class="mt-2">
                                            <img src="{{ route('ArchivosDigitalesVer', ['id' => $pagina->logo_archivo_id]) }}" style="max-height:60px;max-width:220px;object-fit:contain;background:#fff;border:1px solid #eee;border-radius:6px;padding:6px;" alt="logo">
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Fondo hero (jpg/png/webp)</label>
                                    <input type="file" class="form-control" name="hero_fondo" accept="image/*">
                                    @if(!empty($pagina->hero_fondo_archivo_id))
                                        <div class="mt-2">
                                            <img src="{{ route('ArchivosDigitalesVer', ['id' => $pagina->hero_fondo_archivo_id]) }}" style="max-height:60px;max-width:220px;object-fit:cover;background:#fff;border:1px solid #eee;border-radius:6px;padding:6px;" alt="fondo">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Hero título (ES)</label>
                                    <input class="form-control" name="hero_titulo_es" value="{{ old('hero_titulo_es', $pagina->hero_titulo_es) }}" maxlength="255">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Hero título (EN)</label>
                                    <input class="form-control" name="hero_titulo_en" value="{{ old('hero_titulo_en', $pagina->hero_titulo_en) }}" maxlength="255">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Hero subtítulo (ES)</label>
                                    <textarea class="form-control" name="hero_subtitulo_es" rows="3">{{ old('hero_subtitulo_es', $pagina->hero_subtitulo_es) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Hero subtítulo (EN)</label>
                                    <textarea class="form-control" name="hero_subtitulo_en" rows="3">{{ old('hero_subtitulo_en', $pagina->hero_subtitulo_en) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Botón texto (ES)</label>
                                    <input class="form-control" name="hero_boton_texto_es" value="{{ old('hero_boton_texto_es', $pagina->hero_boton_texto_es) }}" maxlength="120">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Botón texto (EN)</label>
                                    <input class="form-control" name="hero_boton_texto_en" value="{{ old('hero_boton_texto_en', $pagina->hero_boton_texto_en) }}" maxlength="120">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Botón URL</label>
                                    <input class="form-control" name="hero_boton_url" value="{{ old('hero_boton_url', $pagina->hero_boton_url) }}" maxlength="600">
                                    <small class="text-muted">Ej: /admin/login o https://...</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input class="form-control" name="contacto_telefono" value="{{ old('contacto_telefono', $pagina->contacto_telefono) }}" maxlength="120">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" name="contacto_email" value="{{ old('contacto_email', $pagina->contacto_email) }}" maxlength="255">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Dirección (ES)</label>
                                    <input class="form-control" name="contacto_direccion_es" value="{{ old('contacto_direccion_es', $pagina->contacto_direccion_es) }}" maxlength="255">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Dirección (EN)</label>
                                    <input class="form-control" name="contacto_direccion_en" value="{{ old('contacto_direccion_en', $pagina->contacto_direccion_en) }}" maxlength="255">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cookies_activo" id="cookies_activo" {{ old('cookies_activo', $pagina->cookies_activo) === 'S' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cookies_activo">Mostrar banner de cookies</label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Cookies texto (ES)</label>
                                    <textarea class="form-control" name="cookies_texto_es" rows="2">{{ old('cookies_texto_es', $pagina->cookies_texto_es) }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Cookies texto (EN)</label>
                                    <textarea class="form-control" name="cookies_texto_en" rows="2">{{ old('cookies_texto_en', $pagina->cookies_texto_en) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Botón aceptar (ES)</label>
                                    <input class="form-control" name="cookies_btn_aceptar_es" value="{{ old('cookies_btn_aceptar_es', $pagina->cookies_btn_aceptar_es) }}" maxlength="80">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Botón aceptar (EN)</label>
                                    <input class="form-control" name="cookies_btn_aceptar_en" value="{{ old('cookies_btn_aceptar_en', $pagina->cookies_btn_aceptar_en) }}" maxlength="80">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Botón rechazar (ES)</label>
                                    <input class="form-control" name="cookies_btn_rechazar_es" value="{{ old('cookies_btn_rechazar_es', $pagina->cookies_btn_rechazar_es) }}" maxlength="80">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Botón rechazar (EN)</label>
                                    <input class="form-control" name="cookies_btn_rechazar_en" value="{{ old('cookies_btn_rechazar_en', $pagina->cookies_btn_rechazar_en) }}" maxlength="80">
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Guardar</button>
                    </form>
                </div>

                {{-- MENU --}}
                <div class="tab-pane fade" id="pane-menu" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card" style="border-radius:12px; border:1px solid #eee;">
                                <div class="card-header" style="background:#fff;"><b>Nuevo / Editar</b></div>
                                <div class="card-body">
                                    <form action="{{ route('FrFrontedSaveMenu') }}" method="POST" id="formMenu">
                                        @csrf
                                        <input type="hidden" name="id" id="menu_id" value="">
                                        <div class="form-group">
                                            <label>Orden</label>
                                            <input class="form-control" type="number" name="orden" id="menu_orden" value="1" min="1" max="9999">
                                        </div>
                                        <div class="form-group">
                                            <label>Texto (ES) *</label>
                                            <input class="form-control" name="texto_es" id="menu_texto_es" maxlength="120" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Texto (EN)</label>
                                            <input class="form-control" name="texto_en" id="menu_texto_en" maxlength="120">
                                        </div>
                                        <div class="form-group">
                                            <label>Tipo</label>
                                            <select class="form-control" name="tipo" id="menu_tipo">
                                                <option value="anchor">Anchor (#...)</option>
                                                <option value="route">Route (nombre ruta)</option>
                                                <option value="url">URL (http/https o /ruta)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Destino</label>
                                            <input class="form-control" name="destino" id="menu_destino" maxlength="600">
                                            <small class="text-muted">Ej: #about, blogs, /admin/login</small>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="nuevo_tab" id="menu_nuevo_tab">
                                            <label class="form-check-label" for="menu_nuevo_tab">Abrir en nueva pestaña</label>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" type="submit">Guardar</button>
                                            <button class="btn btn-light" type="button" onclick="frResetMenu()">Limpiar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Orden</th>
                                            <th>Texto</th>
                                            <th>Tipo</th>
                                            <th>Destino</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($menuItems as $m)
                                            <tr>
                                                <td>{{ $m->id }}</td>
                                                <td>{{ $m->orden }}</td>
                                                <td>{{ $m->texto_es }}</td>
                                                <td>{{ $m->tipo }}</td>
                                                <td style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $m->destino }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-info btn-sm" type="button" onclick='frEditMenu(@json($m))'>Editar</button>
                                                    <form action="{{ route('FrFrontedDeleteMenu', $m->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este item?');">
                                                        @csrf
                                                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-muted">Sin registros.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECCIONES --}}
                <div class="tab-pane fade" id="pane-secciones" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card" style="border-radius:12px; border:1px solid #eee;">
                                <div class="card-header" style="background:#fff;"><b>Nueva / Editar</b></div>
                                <div class="card-body">
                                    <form action="{{ route('FrFrontedSaveSeccion') }}" method="POST" id="formSeccion">
                                        @csrf
                                        <input type="hidden" name="id" id="sec_id" value="">
                                        <div class="form-group">
                                            <label>Código *</label>
                                            <input class="form-control" name="codigo" id="sec_codigo" maxlength="50" required>
                                            <small class="text-muted">Ej: about, services, portfolio, cta, contact</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Orden</label>
                                            <input class="form-control" type="number" name="orden" id="sec_orden" value="1" min="1" max="9999">
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="mostrar" id="sec_mostrar" checked>
                                            <label class="form-check-label" for="sec_mostrar">Mostrar</label>
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <label>Título (ES)</label>
                                            <input class="form-control" name="titulo_es" id="sec_titulo_es" maxlength="255">
                                        </div>
                                        <div class="form-group">
                                            <label>Título (EN)</label>
                                            <input class="form-control" name="titulo_en" id="sec_titulo_en" maxlength="255">
                                        </div>
                                        <div class="form-group">
                                            <label>Contenido (ES)</label>
                                            <textarea class="form-control" name="contenido_es" id="sec_contenido_es" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Contenido (EN)</label>
                                            <textarea class="form-control" name="contenido_en" id="sec_contenido_en" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Botón texto (ES)</label>
                                            <input class="form-control" name="boton_texto_es" id="sec_boton_texto_es" maxlength="120">
                                        </div>
                                        <div class="form-group">
                                            <label>Botón texto (EN)</label>
                                            <input class="form-control" name="boton_texto_en" id="sec_boton_texto_en" maxlength="120">
                                        </div>
                                        <div class="form-group">
                                            <label>Botón URL</label>
                                            <input class="form-control" name="boton_url" id="sec_boton_url" maxlength="600">
                                        </div>
                                        <div class="form-group">
                                            <label>Clase CSS (opcional)</label>
                                            <input class="form-control" name="clase_css" id="sec_clase_css" maxlength="255">
                                            <small class="text-muted">Ej: bg-primary, bg-dark text-white</small>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" type="submit">Guardar</button>
                                            <button class="btn btn-light" type="button" onclick="frResetSeccion()">Limpiar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Orden</th>
                                            <th>Código</th>
                                            <th>Título ES</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($secciones as $s)
                                            <tr>
                                                <td>{{ $s->id }}</td>
                                                <td>{{ $s->orden }}</td>
                                                <td>{{ $s->codigo }}</td>
                                                <td style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->titulo_es }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-info btn-sm" type="button" onclick='frEditSeccion(@json($s))'>Editar</button>
                                                    <form action="{{ route('FrFrontedDeleteSeccion', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta sección?');">
                                                        @csrf
                                                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-muted">Sin registros.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SERVICIOS --}}
                <div class="tab-pane fade" id="pane-servicios" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card" style="border-radius:12px; border:1px solid #eee;">
                                <div class="card-header" style="background:#fff;"><b>Nuevo / Editar</b></div>
                                <div class="card-body">
                                    <form action="{{ route('FrFrontedSaveServicio') }}" method="POST" id="formServicio">
                                        @csrf
                                        <input type="hidden" name="id" id="srv_id" value="">
                                        <div class="form-group">
                                            <label>Orden</label>
                                            <input class="form-control" type="number" name="orden" id="srv_orden" value="1" min="1" max="9999">
                                        </div>
                                        <div class="form-group">
                                            <label>Icono (FontAwesome)</label>
                                            <input class="form-control" name="icono" id="srv_icono" maxlength="80" placeholder="fa-gem">
                                            <small class="text-muted">Solo clase (sin "fas").</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Título (ES) *</label>
                                            <input class="form-control" name="titulo_es" id="srv_titulo_es" maxlength="200" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Título (EN)</label>
                                            <input class="form-control" name="titulo_en" id="srv_titulo_en" maxlength="200">
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción (ES)</label>
                                            <textarea class="form-control" name="descripcion_es" id="srv_desc_es" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción (EN)</label>
                                            <textarea class="form-control" name="descripcion_en" id="srv_desc_en" rows="3"></textarea>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" type="submit">Guardar</button>
                                            <button class="btn btn-light" type="button" onclick="frResetServicio()">Limpiar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Orden</th>
                                            <th>Icono</th>
                                            <th>Título ES</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($servicios as $s)
                                            <tr>
                                                <td>{{ $s->id }}</td>
                                                <td>{{ $s->orden }}</td>
                                                <td>
                                                    @if(!empty($s->icono))
                                                        <i class="fa {{ $s->icono }}"></i>
                                                    @endif
                                                    {{ $s->icono }}
                                                </td>
                                                <td style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->titulo_es }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-info btn-sm" type="button" onclick='frEditServicio(@json($s))'>Editar</button>
                                                    <form action="{{ route('FrFrontedDeleteServicio', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este servicio?');">
                                                        @csrf
                                                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-muted">Sin registros.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PORTAFOLIO --}}
                <div class="tab-pane fade" id="pane-portafolio" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card" style="border-radius:12px; border:1px solid #eee;">
                                <div class="card-header" style="background:#fff;"><b>Nuevo / Editar</b></div>
                                <div class="card-body">
                                    <form action="{{ route('FrFrontedSavePortafolio') }}" method="POST" enctype="multipart/form-data" id="formPort">
                                        @csrf
                                        <input type="hidden" name="id" id="port_id" value="">
                                        <div class="form-group">
                                            <label>Orden</label>
                                            <input class="form-control" type="number" name="orden" id="port_orden" value="1" min="1" max="9999">
                                        </div>
                                        <div class="form-group">
                                            <label>Categoría (ES)</label>
                                            <input class="form-control" name="categoria_es" id="port_cat_es" maxlength="200">
                                        </div>
                                        <div class="form-group">
                                            <label>Categoría (EN)</label>
                                            <input class="form-control" name="categoria_en" id="port_cat_en" maxlength="200">
                                        </div>
                                        <div class="form-group">
                                            <label>Título (ES)</label>
                                            <input class="form-control" name="titulo_es" id="port_tit_es" maxlength="200">
                                        </div>
                                        <div class="form-group">
                                            <label>Título (EN)</label>
                                            <input class="form-control" name="titulo_en" id="port_tit_en" maxlength="200">
                                        </div>
                                        <div class="form-group">
                                            <label>URL (opcional)</label>
                                            <input class="form-control" name="url" id="port_url" maxlength="600">
                                        </div>
                                        <div class="form-group">
                                            <label>Imagen (jpg/png/webp)</label>
                                            <input type="file" class="form-control" name="imagen" accept="image/*">
                                            <small class="text-muted">Si no subes imagen, el frontend usa assets por defecto.</small>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-primary" type="submit">Guardar</button>
                                            <button class="btn btn-light" type="button" onclick="frResetPort()">Limpiar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Orden</th>
                                            <th>Imagen</th>
                                            <th>Título ES</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($portafolio as $p)
                                            <tr>
                                                <td>{{ $p->id }}</td>
                                                <td>{{ $p->orden }}</td>
                                                <td>
                                                    @if(!empty($p->imagen_archivo_id))
                                                        <img src="{{ route('ArchivosDigitalesVer', ['id' => $p->imagen_archivo_id]) }}" style="width:54px;height:38px;object-fit:cover;border-radius:6px;border:1px solid #eee;" alt="img">
                                                    @else
                                                        <span class="text-muted">(assets)</span>
                                                    @endif
                                                </td>
                                                <td style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $p->titulo_es }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-info btn-sm" type="button" onclick='frEditPort(@json($p))'>Editar</button>
                                                    <form action="{{ route('FrFrontedDeletePortafolio', $p->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este item?');">
                                                        @csrf
                                                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-muted">Sin registros.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function frResetMenu(){
        $('#menu_id').val('');
        $('#menu_orden').val(1);
        $('#menu_texto_es').val('');
        $('#menu_texto_en').val('');
        $('#menu_tipo').val('anchor');
        $('#menu_destino').val('');
        $('#menu_nuevo_tab').prop('checked', false);
    }
    function frEditMenu(m){
        $('#menu_id').val(m.id);
        $('#menu_orden').val(m.orden);
        $('#menu_texto_es').val(m.texto_es);
        $('#menu_texto_en').val(m.texto_en || '');
        $('#menu_tipo').val(m.tipo);
        $('#menu_destino').val(m.destino || '');
        $('#menu_nuevo_tab').prop('checked', (m.nuevo_tab === 'S'));
    }

    function frResetSeccion(){
        $('#sec_id').val('');
        $('#sec_codigo').val('');
        $('#sec_orden').val(1);
        $('#sec_mostrar').prop('checked', true);
        $('#sec_titulo_es').val('');
        $('#sec_titulo_en').val('');
        $('#sec_contenido_es').val('');
        $('#sec_contenido_en').val('');
        $('#sec_boton_texto_es').val('');
        $('#sec_boton_texto_en').val('');
        $('#sec_boton_url').val('');
        $('#sec_clase_css').val('');
    }
    function frEditSeccion(s){
        $('#sec_id').val(s.id);
        $('#sec_codigo').val(s.codigo);
        $('#sec_orden').val(s.orden);
        $('#sec_mostrar').prop('checked', (s.mostrar === 'S'));
        $('#sec_titulo_es').val(s.titulo_es || '');
        $('#sec_titulo_en').val(s.titulo_en || '');
        $('#sec_contenido_es').val(s.contenido_es || '');
        $('#sec_contenido_en').val(s.contenido_en || '');
        $('#sec_boton_texto_es').val(s.boton_texto_es || '');
        $('#sec_boton_texto_en').val(s.boton_texto_en || '');
        $('#sec_boton_url').val(s.boton_url || '');
        $('#sec_clase_css').val(s.clase_css || '');
    }

    function frResetServicio(){
        $('#srv_id').val('');
        $('#srv_orden').val(1);
        $('#srv_icono').val('');
        $('#srv_titulo_es').val('');
        $('#srv_titulo_en').val('');
        $('#srv_desc_es').val('');
        $('#srv_desc_en').val('');
    }
    function frEditServicio(s){
        $('#srv_id').val(s.id);
        $('#srv_orden').val(s.orden);
        $('#srv_icono').val(s.icono || '');
        $('#srv_titulo_es').val(s.titulo_es || '');
        $('#srv_titulo_en').val(s.titulo_en || '');
        $('#srv_desc_es').val(s.descripcion_es || '');
        $('#srv_desc_en').val(s.descripcion_en || '');
    }

    function frResetPort(){
        $('#port_id').val('');
        $('#port_orden').val(1);
        $('#port_cat_es').val('');
        $('#port_cat_en').val('');
        $('#port_tit_es').val('');
        $('#port_tit_en').val('');
        $('#port_url').val('');
    }
    function frEditPort(p){
        $('#port_id').val(p.id);
        $('#port_orden').val(p.orden);
        $('#port_cat_es').val(p.categoria_es || '');
        $('#port_cat_en').val(p.categoria_en || '');
        $('#port_tit_es').val(p.titulo_es || '');
        $('#port_tit_en').val(p.titulo_en || '');
        $('#port_url').val(p.url || '');
    }
</script>
@endsection
