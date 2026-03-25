@extends("templates.".config("sysconfig.theme").".master")

@section('content')
    @php
        $routeName = optional(request()->route())->getName();
        $isEmpresaFlow = str_starts_with((string)$routeName, 'importaciones_empresa.');
        $rIndex = $isEmpresaFlow ? 'importaciones_empresa.index' : 'personas.import.index';
        $rReport = 'personas.import.report';
        $rClear = $isEmpresaFlow ? 'importaciones_empresa.clear' : 'personas.import.clear';
        $rApply = $isEmpresaFlow ? 'importaciones_empresa.apply' : 'personas.import.apply';
    @endphp

    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">Preview de Importación</h3>
            <p class="text-muted" style="margin-bottom:0;">Lote: <code>{{ $batch }}</code>. Revisa los mapeos antes de aplicar.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($hasErrors)
        <div class="alert alert-danger">
            Hay registros con <strong>empresa no válida</strong>. Corrige el origen o crea la empresa faltante antes de aplicar.
        </div>
    @else
        <div class="alert alert-success">
            Todo listo: no se detectaron problemas de mapeo en este lote.
        </div>
    @endif

    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12" style="display:flex;gap:8px;">
            <a class="btn btn-default" href="{{ route($rIndex) }}"><i class="fa fa-arrow-left"></i> Volver</a>

            <a class="btn btn-warning" href="{{ route($rReport, $batch) }}">
                <i class="fa fa-file-excel-o"></i> Reporte de cambios
            </a>

            <form method="POST" action="{{ route($rClear, $batch) }}" style="display:inline;">
                @csrf
                <button class="btn btn-warning" type="submit" onclick="return confirm('¿Eliminar este lote temporal?')"><i class="fa fa-trash"></i> Limpiar lote</button>
            </form>

            <form method="POST" action="{{ route('personas.import.truncate_stg') }}" style="display:inline;">
                @csrf
                <button class="btn btn-danger" type="submit" onclick="return confirm('⚠️ Esto hará TRUNCATE a pg_persona_stg (se borrará TODO lo temporal). ¿Continuar?')">
                    <i class="fa fa-eraser"></i> Truncar tabla temporal
                </button>
            </form>

            <form method="POST" action="{{ route($rApply, $batch) }}" style="display:inline;">
                @csrf
                <button class="btn btn-primary" type="submit" {{ $hasErrors ? 'disabled' : '' }} onclick="return confirm('¿Aplicar importación a pg_persona?')">
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
                        <span class="text-muted" style="margin-left:8px;">Total: <strong>{{ $rows->total() }}</strong></span>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" style="display:flex;align-items:center;gap:6px;margin:0;">
                        <span class="text-muted small">Ver:</span>
                        @php $pp = request('per_page', 50); @endphp
                        <select name="per_page" class="form-control input-sm" style="width:110px;">
                            @foreach([50,100,200,500,1000,2000] as $n)
                                <option value="{{ $n }}" {{ (int)$pp === (int)$n ? 'selected' : '' }}>{{ $n }} / pág.</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="all" value="0">
                        <button class="btn btn-default btn-sm" type="submit">OK</button>

                        <a class="btn btn-primary btn-sm" href="{{ request()->fullUrlWithQuery(['all'=>1,'page'=>1]) }}">
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
                            @foreach($rows as $r)
                                @php
                                    // Solo EMPRESA bloquea. Estado civil/departamento pueden quedar NULL.
                                    $bad = ($r->empresa_check !== 'OK');
                                @endphp
                                <tr style="{{ $bad ? 'background:#fff1f2;' : '' }}">
                                    <td><span class="label label-{{ $r->accion==='INSERT' ? 'success' : 'info' }}">{{ $r->accion }}</span></td>
                                    <td>{{ $r->identificacion }}</td>
                                    <td>{{ $r->tipo }}</td>
                                    <td>{{ $r->nombres }}</td>
                                    <td>{{ trim(($r->apellido1 ?? '').' '.($r->apellido2 ?? '')) }}</td>
                                    <td>{{ $r->empresa_nombre }}</td>
                                    <td>{{ $r->empresa_id }}</td>
                                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $r->direccion }}">{{ $r->direccion }}</td>
                                    <td>{{ $r->vigencia_desde }}</td>
                                    <td>{{ $r->vigencia_hasta }}</td>
                                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $r->departamento }}">{{ $r->departamento }}</td>
                                    <td>{{ $r->descripcion_estado_civil }}</td>
                                    <td>{{ $r->cod_estado_civil_resuelto }}</td>
                                    <td>{{ $r->cod_departamento }}</td>
                                    <td>{{ $r->departamento_id_resuelto }}</td>
                                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $r->email }}">{{ $r->email }}</td>
                                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $r->email_laboral }}">{{ $r->email_laboral }}</td>
                                    <td>{{ $r->fecha_nacimiento }}</td>
                                    <td>{{ $r->tipo_identificacion }}</td>
                                    <td>{{ $r->sexo }}</td>
                                    <td>{{ $r->celular }}</td>
                                    <td>{{ $r->fecha_ingreso }}</td>
                                    <td><span class="label label-{{ $r->estado_civil_check==='OK' ? 'success' : 'warning' }}">{{ $r->estado_civil_check }}</span></td>
                                    <td><span class="label label-{{ $r->departamento_check==='OK' ? 'success' : 'warning' }}">{{ $r->departamento_check }}</span></td>
                                    <td><span class="label label-{{ $r->empresa_check==='OK' ? 'success' : 'danger' }}">{{ $r->empresa_check }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="panel-footer">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
