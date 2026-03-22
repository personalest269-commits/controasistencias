@php
    $current = session('ui_template', 'gentelella');
    $label = $current === 'admin_lte' ? 'AdminLTE' : 'Gentelella';
@endphp

<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
    <i class="fas fa-desktop"></i>
    <span class="d-none d-md-inline">{{ $label }}</span>
  </a>
  <div class="dropdown-menu dropdown-menu-right">
    <form method="POST" action="{{ route('setUiTemplate') }}" style="margin:0;">
      @csrf
      <input type="hidden" name="ui_template" value="gentelella">
      <button type="submit" class="dropdown-item">{{ tr('Gentelella') }}</button>
    </form>
    <form method="POST" action="{{ route('setUiTemplate') }}" style="margin:0;">
      @csrf
      <input type="hidden" name="ui_template" value="admin_lte">
      <button type="submit" class="dropdown-item">{{ tr('AdminLTE') }}</button>
    </form>
  </div>
</li>
