@php
    $ui = session('ui_template', 'gentelella');
    $layout = ($ui === 'admin_lte') ? 'templates.admin_lte.dashboardlayout' : 'templates.gentelella.dashboardlayout';
@endphp

@extends($layout)
