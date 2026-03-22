@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Subir archivo digital</h1>
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
                    <form action="{{ route('ArchivosDigitalesStore') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>Archivo</label>
                            <input type="file" name="archivo" id="archivo_input" class="form-control" required>
                            <small class="text-muted">El formato se valida según el "Tipo de archivo" y el tamaño según el "Tipo de documento".</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tipo de documento</label>
                                <select name="tipo_documento_codigo" id="tipo_documento_codigo" class="form-control">
                                    <option value="">-- Seleccione --</option>
                                    @foreach($tiposDocumento as $t)
                                        <option value="{{ $t->codigo }}" @selected(old('tipo_documento_codigo')==$t->codigo)>
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
                                        <option value="{{ $t->codigo }}" data-ext="{{ ltrim($ext,'.') }}" data-mime="{{ $t->tipo_mime }}" @selected(old('tipo_archivo_codigo')==$t->codigo)>
                                            {{ $t->descripcion }} ({{ $t->codigo }}) - {{ $ext }} / {{ $t->tipo_mime }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion') }}" maxlength="255">
                        </div>

                        <div class="d-flex" style="gap:10px;">
                            <a class="btn btn-secondary" href="{{ route('ArchivosDigitalesIndex') }}">Volver</a>
                            <button class="btn btn-primary" type="submit">Guardar</button>
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
        return {
            ext: normalizeExt(opt.getAttribute('data-ext')),
            mime: normalizeMime(opt.getAttribute('data-mime'))
        };
    }

    function setAcceptByTipo(){
        var cfg = getTipoArchivoCfg();
        var input = document.getElementById('archivo_input');
        if (!input) return;
        if (!cfg || !cfg.ext) {
            input.removeAttribute('accept');
            return;
        }
        // Para imágenes, permitir cualquiera de image/* + extensión
        if (cfg.mime && cfg.mime.startsWith('image/')) {
            input.setAttribute('accept', 'image/*,.' + cfg.ext);
        } else {
            input.setAttribute('accept', '.' + cfg.ext);
        }
    }

    function validateSelectedFile(){
        var cfg = getTipoArchivoCfg();
        var input = document.getElementById('archivo_input');
        if (!input || !input.files || !input.files.length) return true;
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

        // XML puede venir como application/xml o text/xml
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

    $(document).ready(function(){
        try {
            if ($.fn.select2) {
                $('#tipo_documento_codigo').select2({ width: '100%', theme: 'bootstrap4', placeholder: '-- Seleccione --', allowClear: true });
                $('#tipo_archivo_codigo').select2({ width: '100%', theme: 'bootstrap4', placeholder: '-- Seleccione --', allowClear: true });
            }
        } catch (e) {}

        setAcceptByTipo();
        $('#tipo_archivo_codigo').on('change', function(){
            setAcceptByTipo();
            // Si ya se había elegido un archivo, revalidarlo
            validateSelectedFile();
        });
        $('#archivo_input').on('change', function(){
            validateSelectedFile();
        });
    });
})();
</script>
@stop
