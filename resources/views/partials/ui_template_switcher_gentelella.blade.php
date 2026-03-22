@php
    $current = session('ui_template', 'gentelella');
    $label = $current === 'admin_lte' ? 'AdminLTE' : 'Gentelella';
@endphp

<li class="dropdown" style="margin-right: 6px;">
    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="padding: 10px 12px;">
        <i class="fa fa-desktop"></i> {{ $label }} <span class=" fa fa-angle-down"></span>
    </a>
    <ul class="dropdown-menu dropdown-usermenu animated fadeInDown pull-right" style="min-width: 180px;">
        <li>
            <form method="POST" action="{{ route('setUiTemplate') }}" style="margin:0;">
                @csrf
                <input type="hidden" name="ui_template" value="gentelella">
                <button type="submit" class="btn btn-link" style="width:100%;text-align:left;padding:8px 15px;">
                    {{ tr('Gentelella') }}
                </button>
            </form>
        </li>
        <li>
            <form method="POST" action="{{ route('setUiTemplate') }}" style="margin:0;">
                @csrf
                <input type="hidden" name="ui_template" value="admin_lte">
                <button type="submit" class="btn btn-link" style="width:100%;text-align:left;padding:8px 15px;">
                    {{ tr('AdminLTE') }}
                </button>
            </form>
        </li>
    </ul>
</li>
