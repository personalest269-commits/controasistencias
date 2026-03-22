@php
    /**
     * Layout wrapper dinámico.
     *
     * Permite que cualquier vista que haga `@extends('layouts.master')`
     * se renderice en Gentelella o AdminLTE según la selección del usuario.
     */
    $ui = session('ui_template', 'gentelella');
    $layout = ($ui === 'admin_lte') ? 'templates.admin_lte.master' : 'templates.gentelella.master';
@endphp

@extends($layout)
