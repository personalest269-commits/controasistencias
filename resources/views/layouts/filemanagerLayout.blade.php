@php
    $ui = session('ui_template', 'gentelella');
    $layout = ($ui === 'admin_lte') ? 'templates.admin_lte.filemanagerLayout' : 'templates.gentelella.filemanagerLayout';
@endphp

@extends($layout)
