@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-8">
                    <h1 class="m-0">Log #{{ $log->id }}</h1>
                    <small class="text-muted">{{ \App\Models\PgConfiguracion::formatFecha($log->created_at) }} · {{ strtoupper($log->level) }} · {{ $log->channel ?? 'app' }}</small>
                </div>
                <div class="col-sm-4 text-right">
                    <a class="btn btn-light" href="{{ route('PgLogsIndex') }}">&laquo; Volver</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                    <div>
                        @if(is_null($log->estado))
                            <span class="badge badge-info">Abierto</span>
                        @elseif($log->estado==='R')
                            <span class="badge badge-success">Resuelto</span>
                        @else
                            <span class="badge badge-secondary">X</span>
                        @endif
                        <span class="badge badge-@if($log->level==='error' || $log->level==='critical' || $log->level==='alert' || $log->level==='emergency')danger @elseif($log->level==='warning')warning @else secondary @endif">
                            {{ strtoupper($log->level) }}
                        </span>
                    </div>

                    <div>
                        <button type="button" class="btn btn-primary" onclick="copyPgLogToClipboard()">Copiar todo</button>
                        <small id="pglog_copy_msg" class="text-success" style="display:none; margin-left:8px;">Copiado ✅</small>
                        @if($log->estado !== 'X')
                            <form action="{{ route('PgLogsResolve', $log->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                <button type="submit" class="btn btn-@if($log->estado==='R')warning @else success @endif">@if($log->estado==='R')Reabrir @else Marcar resuelto @endif</button>
                            </form>
                            <form action="{{ route('PgLogsDelete', $log->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Marcar este log como eliminado (X)?')">
                                @csrf
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    {{-- Payload listo para copiar (1 click) --}}
                    <textarea id="pglog_copy_payload" style="position:absolute; left:-9999px; top:-9999px;">
LOG #{{ $log->id }}
Fecha: {{ \App\Models\PgConfiguracion::formatFecha($log->created_at) }}
Estado: @if(is_null($log->estado)) Abierto @elseif($log->estado==='R') Resuelto @else X @endif
Nivel: {{ strtoupper($log->level) }}
Canal: {{ $log->channel ?? 'app' }}

Mensaje:
{{ $log->message }}

Excepción:
Clase: {{ $log->exception_class ?? '-' }}
Código: {{ $log->exception_code ?? '-' }}
Archivo: {{ $log->file ?? '-' }}
Línea: {{ $log->line ?? '-' }}

Request:
Método: {{ $log->method ?? '-' }}
IP: {{ $log->ip ?? '-' }}
URL: {{ $log->url ?? '-' }}
User-Agent: {{ $log->user_agent ?? '-' }}

Usuario:
ID: {{ $log->usuario_id ?? '-' }}
Nombre: {{ optional($log->usuario)->name ?? '-' }}
Email: {{ optional($log->usuario)->email ?? '-' }}

Contexto:
{{ json_encode($log->context ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}

Trace:
{{ $log->trace ?? '-' }}
                    </textarea>

                    <div class="row">
                        <div class="col-md-8">
                            <h5>Mensaje</h5>
                            <div class="alert alert-light" style="white-space:pre-wrap;">{{ $log->message }}</div>

                            @if($log->exception_class)
                                <h5>Excepción</h5>
                                <div class="mb-2"><b>Clase:</b> {{ $log->exception_class }}</div>
                                <div class="mb-2"><b>Código:</b> {{ $log->exception_code ?? '-' }}</div>
                                <div class="mb-2"><b>Archivo:</b> {{ $log->file ?? '-' }} @if($log->line) <b>Línea:</b> {{ $log->line }} @endif</div>
                            @endif

                            @if($log->url)
                                <h5>Request</h5>
                                <div class="mb-2"><b>Método:</b> {{ $log->method ?? '-' }} · <b>IP:</b> {{ $log->ip ?? '-' }}</div>
                                <div class="mb-2"><b>URL:</b> <span style="word-break:break-all;">{{ $log->url }}</span></div>
                                <div class="mb-2"><b>User-Agent:</b> <span style="word-break:break-all;">{{ $log->user_agent ?? '-' }}</span></div>
                            @endif

                            @if($log->trace)
                                <h5>Trace</h5>
                                <details open>
                                    <summary class="text-muted" style="cursor:pointer;">Mostrar/ocultar trace</summary>
                                    <pre class="mt-2" style="max-height:420px; overflow:auto; background:#0b1020; color:#e9e9e9; padding:12px; border-radius:6px;">{{ $log->trace }}</pre>
                                </details>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <h5>Usuario</h5>
                            <div class="mb-2"><b>ID:</b> {{ $log->usuario_id ?? '-' }}</div>
                            @if($log->usuario)
                                <div class="mb-2"><b>Nombre:</b> {{ $log->usuario->name ?? '-' }}</div>
                                <div class="mb-2"><b>Email:</b> {{ $log->usuario->email ?? '-' }}</div>
                            @endif

                            @if($log->estado==='R')
                                <h5 class="mt-4">Resolución</h5>
                                <div class="mb-2"><b>Resuelto el:</b> {{ $log->resolved_at ? \App\Models\PgConfiguracion::formatFecha($log->resolved_at) : '-' }}</div>
                                <div class="mb-2"><b>Resuelto por:</b> {{ $log->resolved_by ?? '-' }}</div>
                            @endif

                            <h5 class="mt-4">Contexto</h5>
                            <pre style="max-height:420px; overflow:auto; background:#f7f7f7; padding:12px; border-radius:6px;">{{ json_encode($log->context ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@stop

@section('footer')
<script type="text/javascript">
  (function(){
    function showCopied(ok){
      var el = document.getElementById('pglog_copy_msg');
      if(!el) return;
      el.style.display = 'inline';
      el.className = ok ? 'text-success' : 'text-danger';
      el.textContent = ok ? 'Copiado ✅' : 'No se pudo copiar';
      setTimeout(function(){ el.style.display = 'none'; }, 2500);
    }

    function fallbackCopy(){
      try{
        var ta = document.getElementById('pglog_copy_payload');
        if(!ta) return showCopied(false);
        ta.focus();
        ta.select();
        ta.setSelectionRange(0, 999999);
        var ok = document.execCommand('copy');
        showCopied(!!ok);
      }catch(e){
        showCopied(false);
      }
    }

    window.copyPgLogToClipboard = function(){
      var ta = document.getElementById('pglog_copy_payload');
      if(!ta) return showCopied(false);
      var text = ta.value || '';

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
          .then(function(){ showCopied(true); })
          .catch(function(){ fallbackCopy(); });
      } else {
        fallbackCopy();
      }
    };
  })();
</script>
@endsection
