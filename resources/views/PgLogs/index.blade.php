@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Logs del sistema</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <small class="text-muted">Total abiertos: <b>{{ $counts['open'] ?? 0 }}</b> · Resueltos: <b>{{ $counts['resolved'] ?? 0 }}</b> · Eliminados: <b>{{ $counts['deleted'] ?? 0 }}</b></small>
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
                <div class="card-header">
                    <form method="GET" action="{{ route('PgLogsIndex') }}" class="form-inline" style="gap:8px;display:flex;flex-wrap:wrap;align-items:center;">
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar mensaje / clase / archivo / URL / usuario..." style="min-width:260px;">

                        <select name="level" class="form-control">
                            <option value="">-- Nivel --</option>
                            @foreach($levels as $lv)
                                <option value="{{ $lv }}" @if($level===$lv) selected @endif>{{ strtoupper($lv) }}</option>
                            @endforeach
                        </select>

                        <select name="estado" class="form-control">
                            <option value="">Activos + resueltos</option>
                            <option value="open" @if($estado==='open') selected @endif>Solo abiertos</option>
                            <option value="resolved" @if($estado==='resolved') selected @endif>Solo resueltos</option>
                            <option value="deleted" @if($estado==='deleted') selected @endif>Solo eliminados (X)</option>
                            <option value="all" @if($estado==='all') selected @endif>Todos</option>
                        </select>

                        <input type="date" name="from" value="{{ $from }}" class="form-control" title="Desde">
                        <input type="date" name="to" value="{{ $to }}" class="form-control" title="Hasta">

                        <button class="btn btn-secondary" type="submit">Filtrar</button>
                        <a class="btn btn-light" href="{{ route('PgLogsIndex') }}">Limpiar</a>
                    </form>
                </div>

                <div class="card-body table-responsive">
                    <div class="mb-2 text-muted" style="font-size:12px;">
                        Tip: abre un log y copia <b>message</b> + <b>trace</b> para enviármelo y te ayudo a corregir.
                    </div>

                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width:90px;">ID</th>
                                <th style="width:110px;">Nivel</th>
                                <th>Mensaje</th>
                                <th style="width:170px;">Usuario</th>
                                <th style="width:170px;">Fecha</th>
                                <th style="width:170px;">Estado</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $l)
                                <tr>
                                    <td><strong>#{{ $l->id }}</strong></td>
                                    <td>
                                        <span class="badge badge-@if($l->level==='error' || $l->level==='critical' || $l->level==='alert' || $l->level==='emergency')danger @elseif($l->level==='warning')warning @else secondary @endif">
                                            {{ strtoupper($l->level) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width:720px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $l->message }}
                                        </div>
                                        @if($l->exception_class)
                                            <small class="text-muted">{{ $l->exception_class }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $l->usuario_id ?? '-' }}</div>
                                        @if($l->ip)
                                            <small class="text-muted">{{ $l->ip }}</small>
                                        @endif
                                    </td>
                            <td>{{ \App\Models\PgConfiguracion::formatFecha($l->created_at) }}</td>
                                    <td>
                                        @if(is_null($l->estado))
                                            <span class="badge badge-info">Abierto</span>
                                        @elseif($l->estado==='R')
                                            <span class="badge badge-success">Resuelto</span>
                                            @if($l->resolved_at)
                                                <small class="text-muted d-block">{{ $l->resolved_at }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">X</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-primary" href="{{ route('PgLogsShow', $l->id) }}">Ver</a>
                                        @if($l->estado !== 'X')
                                            <form action="{{ route('PgLogsResolve', $l->id) }}" method="POST" style="display:inline-block">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-@if($l->estado==='R')warning @else success @endif">@if($l->estado==='R')Reabrir @else Resolver @endif</button>
                                            </form>
                                            <form action="{{ route('PgLogsDelete', $l->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Marcar este log como eliminado (X)?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay logs con esos filtros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
