@extends('layouts.master')

@section('content')
@php
    // ResponseController envía los datos dentro de la variable $data
    // (no hace extract de claves). Definimos defaults para evitar "Undefined variable".
    $items = $data['items'] ?? [];
    $argos = (bool)($data['argos'] ?? false);
@endphp
@php
    $ui = session('ui_template', 'gentelella');
    $isAdminLTE = ($ui === 'admin_lte');
@endphp

@if(!$isAdminLTE)
    <div class="page-title">
        <div class="title_left">
            <h3>{{ tr('Traducciones') }}</h3>
        </div>
    </div>
    <div class="clearfix"></div>
@else
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ tr('Traducciones') }}</h1>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        @if(!$isAdminLTE)
            {{-- Gentelella --}}
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{ tr('Gestionar textos (Español / English)') }}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li>
                            <button type="button" class="btn btn-success btn-sm" id="btnNuevo">{{ tr('Nuevo') }}</button>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
        @else
            {{-- AdminLTE --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ tr('Gestionar textos (Español / English)') }}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="btnNuevo">{{ tr('Nuevo') }}</button>
                    </div>
                </div>
                <div class="card-body">
        @endif

                @if(!$argos)
                <div class="alert alert-warning" style="margin-bottom: 15px;">
                    <strong>{{ tr('Traducción automática:') }}</strong>
                    {{ tr('Argos Translate no está instalado en el servidor. Puedes traducir manualmente y guardar.') }}
                </div>
                @else
                <div class="alert alert-info" style="margin-bottom: 15px;">
                    <strong>{{ tr('Traducción automática:') }}</strong>
                    {{ tr('Argos Translate detectado. Puedes usar “Auto traducir” (ES → EN).') }}
                </div>
                @endif

                <div class="row" style="margin-bottom:10px;">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="trSearch" placeholder="{{ tr('Buscar... (clave, español o english)') }}">
                        <small class="text-muted">{{ tr('Escribe para filtrar la tabla sin recargar.') }}</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="tblTr">
                        <thead>
                            <tr>
                                <th style="width: 25%">{{ tr('Clave') }}</th>
                                <th style="width: 35%">{{ tr('Español') }}</th>
                                <th style="width: 35%">{{ tr('English') }}</th>
                                <th style="width: 5%">{{ tr('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $it)
                            <tr>
                                <td style="word-break: break-all">{{ $it['clave'] }}</td>
                                <td>{{ $it['es'] }}</td>
                                <td>{{ $it['en'] }}</td>
                                <td>
                                    <button class="btn btn-primary btn-xs btnEdit"
                                        data-clave="{{ $it['clave'] }}"
                                        data-es="{{ e($it['es']) }}"
                                        data-en="{{ e($it['en']) }}"
                                    >{{ tr('Editar') }}</button>
                                    <button class="btn btn-danger btn-xs btnDel" data-clave="{{ $it['clave'] }}">{{ tr('Eliminar') }}</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

        @if(!$isAdminLTE)
                </div> {{-- x_content --}}
            </div> {{-- x_panel --}}
        @else
                </div> {{-- card-body --}}
            </div> {{-- card --}}
        @endif
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdlTr" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{{ tr('Traducción') }}</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>{{ tr('Clave') }}</label>
            <input type="text" class="form-control" id="trClave" placeholder="menu.reportes / auto..." />
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ tr('Español') }}</label>
                    <textarea class="form-control" id="trEs" rows="4"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ tr('English') }}</label>
                    <textarea class="form-control" id="trEn" rows="4"></textarea>
                </div>
                <button type="button" class="btn btn-default" id="btnAuto" style="margin-top: -5px;" {{ !$argos ? 'disabled' : '' }}>
                    <i class="fa fa-magic"></i> {{ tr('Auto traducir') }}
                </button>
            </div>
        </div>
        <small class="text-muted">{{ tr('Tip: el sistema crea claves auto.* cuando usas tr(\'Texto\') en las vistas.') }}</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ tr('Cerrar') }}</button>
        <button type="button" class="btn btn-primary" id="btnGuardar">{{ tr('Guardar') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer')
<script>
(function(){
    // Filtro rápido en cliente
    $('#trSearch').on('keyup', function(){
        var q = ($(this).val() || '').toString().toLowerCase().trim();
        var $rows = $('#tblTr tbody tr');
        if(!q){
            $rows.show();
            return;
        }
        $rows.each(function(){
            var t = $(this).text().toLowerCase();
            $(this).toggle(t.indexOf(q) !== -1);
        });
    });

    // Filtro rápido en la tabla
    $('#trSearch').on('keyup', function(){
        var q = ($(this).val() || '').toString().toLowerCase().trim();
        $('#tblTr tbody tr').each(function(){
            var t = $(this).text().toLowerCase();
            $(this).toggle(t.indexOf(q) !== -1);
        });
    });

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    $('#btnNuevo').on('click', function(){
        $('#trClave').val('');
        $('#trEs').val('');
        $('#trEn').val('');
        $('#mdlTr').modal('show');
    });

    $('.btnEdit').on('click', function(){
        var $b = $(this);
        $('#trClave').val($b.data('clave'));
        $('#trEs').val($b.data('es'));
        $('#trEn').val($b.data('en'));
        $('#mdlTr').modal('show');
    });

    $('#btnGuardar').on('click', function(){
        $.ajax({
            url: '{{ route('PgTraduccionesGuardar') }}',
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf()},
            data: {
                clave: $('#trClave').val(),
                es: $('#trEs').val(),
                en: $('#trEn').val()
            }
        }).done(function(){
            location.reload();
        }).fail(function(xhr){
            alert(xhr.responseJSON?.message || 'Error');
        });
    });

    $('.btnDel').on('click', function(){
        if(!confirm('{{ tr('¿Eliminar esta clave?') }}')) return;
        var clave = $(this).data('clave');
        $.ajax({
            url: '{{ route('PgTraduccionesEliminar') }}',
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf()},
            data: {clave: clave}
        }).done(function(){
            location.reload();
        }).fail(function(){
            alert('Error');
        });
    });

    $('#btnAuto').on('click', function(){
        var texto = $('#trEs').val();
        if(!texto){
            alert('{{ tr('Primero escribe el texto en Español.') }}');
            return;
        }
        $.ajax({
            url: '{{ route('PgTraduccionesAuto') }}',
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf()},
            data: {texto: texto, from: 'es', to: 'en'}
        }).done(function(resp){
            if(resp && resp.data && resp.data.texto !== undefined){
                $('#trEn').val(resp.data.texto);
            }
        }).fail(function(xhr){
            alert(xhr.responseJSON?.message || '{{ tr('No se pudo traducir.') }}');
        });
    });
})();
</script>
@endsection
