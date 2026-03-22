<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" class="form-control" maxlength="255" required value="{{ old('titulo', $menu->titulo ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" class="form-control" required>
                @php $tipo = old('tipo', $menu->tipo ?? 'G'); @endphp
                <option value="G" {{ $tipo==='G' ? 'selected' : '' }}>Grupo</option>
                <option value="M" {{ $tipo==='M' ? 'selected' : '' }}>Módulo</option>
            </select>
            <small class="text-muted">Grupo no navega (url #). Módulo usa nombre de ruta (route).</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Activo</label>
            @php $activo = old('activo', $menu->activo ?? 'S'); @endphp
            <select name="activo" class="form-control" required>
                <option value="S" {{ $activo==='S' ? 'selected' : '' }}>S</option>
                <option value="N" {{ $activo==='N' ? 'selected' : '' }}>N</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Padre</label>
            @php $idPadre = old('id_padre', $menu->id_padre ?? ''); @endphp
            <select name="id_padre" class="form-control">
                <option value="">(Sin padre)</option>
                @foreach($padres as $p)
                    <option value="{{ $p->id }}" {{ (string)$idPadre === (string)$p->id ? 'selected' : '' }}>{{ $p->titulo }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Ruta / URL</label>
            <input type="text" name="url" class="form-control" maxlength="255" value="{{ old('url', $menu->url ?? '') }}" placeholder="Ej: PersonasIndex">
            <small class="text-muted">Recomendado: nombre de ruta (ej: <code>PersonasIndex</code>).</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>Orden</label>
            <input type="number" name="orden" class="form-control" min="0" max="32767" value="{{ old('orden', $menu->orden ?? 0) }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Roles que verán esta opción</label>
            @php
                $selected = old('roles', $rolesSeleccionados ?? []);
                if (!is_array($selected)) { $selected = []; }
            @endphp
            <select name="roles[]" class="form-control" multiple size="5">
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ in_array((int)$r->id, array_map('intval', $selected)) ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">La visibilidad del menú se controla por rol (pg_opcion_menu_rol).</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Imagen del menú (opcional)</label>
            <input type="file" name="imagen" class="form-control" accept="image/*">
            <small class="text-muted">Se guarda en <code>ad_archivo_digital.digital</code> (cifrado) y se relaciona con <code>pg_opcion_menu.id_archivo</code>.</small>
        </div>

        @if(!empty($menu) && $menu->id_archivo)
            <div class="mb-2">
                <label class="d-block">Imagen actual</label>
                <a href="{{ route('ArchivosDigitalesVer', $menu->id_archivo) }}" target="_blank">
                    <img src="{{ route('ArchivosDigitalesVer', $menu->id_archivo) }}" style="max-width:120px; height:auto; border-radius:6px;" alt="">
                </a>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="quitarImagen" name="quitar_imagen" value="1">
                <label class="custom-control-label" for="quitarImagen">Quitar imagen</label>
            </div>
        @endif
    </div>
</div>
