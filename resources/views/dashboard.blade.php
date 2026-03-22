@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    {{-- FullCalendar (LOCAL - AdminLTE) --}}
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fullcalendar/main.min.css') }}">
    <style>
        .dash-card{background:#fff;border-radius:12px;padding:16px 18px;box-shadow:0 6px 16px rgba(0,0,0,.06);margin-bottom:16px;min-height:92px;}
        .dash-card .dash-title{font-size:12px;color:#6b7280;margin-bottom:6px;}
        .dash-card .dash-value{font-size:26px;font-weight:700;line-height:1;}
        .dash-card .dash-label{font-size:13px;color:#111827;margin-top:8px;font-weight:600;}
        .dash-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
        .dash-row{display:flex;gap:16px;flex-wrap:wrap;}
        .dash-col{flex:1 1 220px;}
        #dashboardCalendar{background:#fff;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.06);padding:12px;}
    </style>
@endsection

@section('content')

    @php
        $stats = $stats ?? [
            'personas' => 0,
            'usuarios' => 0,
            'eventos' => 0,
        ];
    @endphp

    @include('partials.license_dashboard_alert')

    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">Dashboard</h3>
        </div>
    </div>

    {{-- Cards (reemplaza el gráfico subrayado) --}}
    <div class="dash-row">
        <div class="dash-col">
            <div class="dash-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div>
                        <div class="dash-title">Total</div>
                        <div class="dash-value">{{ number_format($stats['personas']) }}</div>
                        <div class="dash-label">Personas</div>
                    </div>
                    <div class="dash-icon" style="background:#22c55e;"><i class="fa fa-users"></i></div>
                </div>
            </div>
        </div>

        <div class="dash-col">
            <div class="dash-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div>
                        <div class="dash-title">Total</div>
                        <div class="dash-value">{{ number_format($stats['usuarios']) }}</div>
                        <div class="dash-label">Usuarios</div>
                    </div>
                    <div class="dash-icon" style="background:#06b6d4;"><i class="fa fa-user"></i></div>
                </div>
            </div>
        </div>

        <div class="dash-col">
            <div class="dash-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div>
                        <div class="dash-title">Total</div>
                        <div class="dash-value">{{ number_format($stats['eventos']) }}</div>
                        <div class="dash-label">Eventos</div>
                    </div>
                    <div class="dash-icon" style="background:#f59e0b;"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
        </div>

    </div>

    <div class="row" style="margin-top:8px;">
        <div class="col-md-12">
            <h4 style="margin: 6px 0 10px 0;">Calendario de eventos</h4>
            <div id="dashboardCalendar"></div>
        </div>
    </div>

@endsection

@section('footer')
    @parent
    <script src="{{ asset('admin_lte/plugins/fullcalendar/main.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/fullcalendar/locales/es.js') }}"></script>
    <script>
        (function () {
            var el = document.getElementById('dashboardCalendar');
            if (!el) return;

            var calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: "{{ route('dashboard.events') }}",
                    method: 'GET',
                    extraParams: function() {
                        return {};
                    },
                    failure: function() {
                        console.error('No se pudo cargar los eventos del dashboard');
                    }
                },
                eventDidMount: function(info) {
                    // tooltip simple
                    if (info.event.title) {
                        info.el.setAttribute('title', info.event.title);
                    }
                }
            });

            calendar.render();
        })();
    </script>
@endsection
