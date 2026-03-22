@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar archivo digital</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
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
                <div class="card-body">
                    <form action="{{ route('ArchivosDigitalesUpdate', $archivo->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Vista actual</label>
                            <div>
                                @php
                                    $mime = strtolower((string)($archivo->tipo_mime ?? ''));
                                    $ext = strtolower(ltrim((string)($archivo->extension ?? ''), '.'));
                                    $isPdf = str_contains($mime, 'pdf') || $ext === 'pdf';
                                    $isXml = str_contains($mime, 'xml') || $ext === 'xml';
                                    $isImg = str_starts_with($mime, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']);
                                @endphp

                                @if(!empty($archivo->digital) || !empty($archivo->ruta))
                                    <div class="mb-2">
                                        <a href="{{ route('ArchivosDigitalesVer', $archivo->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Abrir en nueva pestaña</a>
                                        <span class="text-muted" style="margin-left:8px;">{{ $archivo->nombre_original }}</span>
                                    </div>

                                    @if($isPdf)
                                        <iframe src="{{ route('ArchivosDigitalesVer', $archivo->id) }}" style="width:100%; height:520px; border:1px solid #ddd; border-radius:6px;"></iframe>
                                    @elseif($isImg)
                                        <img src="{{ route('ArchivosDigitalesVer', $archivo->id) }}" alt="" style="max-width:420px; height:auto; border-radius:6px; border:1px solid #ddd; padding:4px;">
                                    @elseif($isXml)
                                        <pre id="xmlViewer" style="max-height:520px; overflow:auto; border:1px solid #ddd; border-radius:6px; padding:12px; background:#fafafa;">Cargando XML…</pre>
                                    @else
                                        <div class="text-muted">Vista previa no disponible para este tipo ({{ $archivo->tipo_mime ?? 'desconocido' }}).</div>
                                    @endif
                                @else
                                    <span class="text-muted">Sin archivo</span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Reemplazar archivo (opcional)</label>
                            <input type="file" name="archivo" id="archivo_input" class="form-control">
                            <small class="text-muted">El formato se valida según el "Tipo de archivo" y el tamaño según el "Tipo de documento".</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tipo de documento</label>
                                <select name="tipo_documento_codigo" id="tipo_documento_codigo" class="form-control">
                                    <option value="">-- Seleccione --</option>
                                    @foreach($tiposDocumento as $t)
                                        <option value="{{ $t->codigo }}" @selected($archivo->tipo_documento_codigo==$t->codigo)>
                                            {{ $t->descripcion }} ({{ $t->codigo }}) - {{ $t->tamano_maximo }} KB
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tipo de archivo</label>
                                <select name="tipo_archivo_codigo" id="tipo_archivo_codigo" class="form-control" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($tiposArchivo as $t)
                                        @php($ext = $t->extension ? ((substr($t->extension, 0, 1) == '.') ? $t->extension : '.'.$t->extension) : '')
                                        <option value="{{ $t->codigo }}" data-ext="{{ ltrim($ext,'.') }}" data-mime="{{ $t->tipo_mime }}" @selected($archivo->tipo_archivo_codigo==$t->codigo)>
                                            {{ $t->descripcion }} ({{ $t->codigo }}) - {{ $ext }} / {{ $t->tipo_mime }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion', $archivo->descripcion) }}" maxlength="255">
                        </div>

                        <div class="d-flex" style="gap:10px;">
                            <a class="btn btn-secondary" href="{{ route('ArchivosDigitalesIndex') }}">Volver</a>
                            <button class="btn btn-primary" type="submit">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('footer')
@parent
<script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
(function(){
    function normalizeExt(ext){
        ext = (ext || '').toString().toLowerCase().trim();
        if (ext.startsWith('.')) ext = ext.substring(1);
        return ext;
    }
    function normalizeMime(m){
        m = (m || '').toString().toLowerCase().trim();
        if (m === 'image/jpg') m = 'image/jpeg';
        return m;
    }

    function getTipoArchivoCfg(){
        var sel = document.getElementById('tipo_archivo_codigo');
        if (!sel) return null;
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return null;
        return { ext: normalizeExt(opt.getAttribute('data-ext')), mime: normalizeMime(opt.getAttribute('data-mime')) };
    }

    function setAcceptByTipo(){
        var cfg = getTipoArchivoCfg();
        var input = document.getElementById('archivo_input');
        if (!input) return;
        if (!cfg || !cfg.ext) {
            input.removeAttribute('accept');
            return;
        }
        if (cfg.mime && cfg.mime.startsWith('image/')) input.setAttribute('accept', 'image/*,.' + cfg.ext);
        else input.setAttribute('accept', '.' + cfg.ext);
    }

    function validateSelectedFile(){
        var input = document.getElementById('archivo_input');
        if (!input || !input.files || !input.files.length) return true;
        var cfg = getTipoArchivoCfg();
        if (!cfg) {
            alert('Selecciona primero el Tipo de archivo.');
            input.value = '';
            return false;
        }
        var f = input.files[0];
        var name = (f.name || '').toLowerCase();
        var ext = name.includes('.') ? name.split('.').pop() : '';
        ext = normalizeExt(ext);
        var mime = normalizeMime(f.type);

        if (cfg.ext && ext && cfg.ext !== ext) {
            alert('El archivo seleccionado no coincide con la extensión esperada: .' + cfg.ext);
            input.value = '';
            return false;
        }

        if (cfg.mime && mime) {
            var okMime = false;
            if (cfg.mime.includes('xml') && mime.includes('xml')) okMime = true;
            else if (cfg.mime === mime) okMime = true;
            else if (cfg.mime === 'image/jpeg' && mime === 'image/jpeg') okMime = true;
            if (!okMime) {
                alert('El archivo seleccionado no coincide con el tipo MIME esperado: ' + cfg.mime);
                input.value = '';
                return false;
            }
        }
        return true;
    }

    function prettyXml(xml){
        try {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xml, 'application/xml');
            var parseError = doc.getElementsByTagName('parsererror');
            if (parseError && parseError.length) return xml;
            var serializer = new XMLSerializer();
            var s = serializer.serializeToString(doc);
            // indent simple
            var P = /(>)(<)(\/*)/g;
            s = s.replace(P, '$1\n$2$3');
            var pad = 0;
            return s.split('\n').map(function(line){
                var indent = 0;
                if (line.match(/^<\//)) pad = Math.max(pad - 1, 0);
                indent = pad;
                if (line.match(/^<[^!?][^>]*[^\/]>$/) && !line.match(/^<\//) ) pad += 1;
                return '  '.repeat(indent) + line;
            }).join('\n');
        } catch(e){ return xml; }
    }

    $(document).ready(function(){
        try {
            if ($.fn.select2) {
                $('#tipo_documento_codigo').select2({ width: '100%', theme: 'bootstrap4', placeholder: '-- Seleccione --', allowClear: true });
                $('#tipo_archivo_codigo').select2({ width: '100%', theme: 'bootstrap4', placeholder: '-- Seleccione --', allowClear: true });
            }
        } catch(e) {}

        setAcceptByTipo();
        $('#tipo_archivo_codigo').on('change', function(){
            setAcceptByTipo();
            validateSelectedFile();
        });
        $('#archivo_input').on('change', function(){
            validateSelectedFile();
        });

        // Visor XML (si aplica)
        var xmlViewer = document.getElementById('xmlViewer');
        if (xmlViewer) {
            fetch(@json(route('ArchivosDigitalesVer', $archivo->id)))
                .then(function(r){ return r.text(); })
                .then(function(t){ xmlViewer.textContent = prettyXml(t); })
                .catch(function(){ xmlViewer.textContent = 'No se pudo cargar el XML.'; });
        }
    });
})();
</script>
@stop
