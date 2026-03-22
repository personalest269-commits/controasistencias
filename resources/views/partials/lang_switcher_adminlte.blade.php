@php
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Session;
    $currentLang = (string) Session::get('lang', app()->getLocale() ?: 'es');
    $idiomas = collect();
    try {
        if (Schema::hasTable('idiomas')) {
            $idiomas = \App\Models\Idioma::query()
                ->where('activo', 1)
                ->orderByDesc('por_defecto')
                ->orderBy('nombre')
                ->get(['codigo','nombre']);
        }
    } catch (\Throwable $e) {
        $idiomas = collect();
    }
@endphp

@if($idiomas->count() > 0)
  <li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
      <i class="fas fa-language"></i>
      <span class="ml-1">{{ $idiomas->firstWhere('codigo', $currentLang)->nombre ?? strtoupper($currentLang) }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-right p-0">
      @foreach($idiomas as $lang)
        <form method="POST" action="{{ route('setLang') }}" class="m-0">
          @csrf
          <input type="hidden" name="lang" value="{{ $lang->codigo }}">
          <button type="submit" class="dropdown-item {{ $lang->codigo === $currentLang ? 'active' : '' }}">
            {{ $lang->nombre }}
          </button>
        </form>
      @endforeach
    </div>
  </li>
@endif
