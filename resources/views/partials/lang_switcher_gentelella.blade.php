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
  <li class="dropdown">
    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
      <i class="fa fa-language"></i>
      <span class="ml-1">{{ $idiomas->firstWhere('codigo', $currentLang)->nombre ?? strtoupper($currentLang) }}</span>
      <span class=" fa fa-angle-down"></span>
    </a>
    <ul class="dropdown-menu dropdown-usermenu animated fadeInDown pull-right">
      @foreach($idiomas as $lang)
        <li>
          <form method="POST" action="{{ route('setLang') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="lang" value="{{ $lang->codigo }}">
            <button type="submit" class="btn btn-link" style="padding:6px 20px; width:100%; text-align:left; color:#5A738E; text-decoration:none;">
              {{ $lang->nombre }}
            </button>
          </form>
        </li>
      @endforeach
    </ul>
  </li>
@endif
