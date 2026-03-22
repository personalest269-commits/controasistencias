@extends("templates.".config("sysconfig.theme").".master")
@section('head')
  <script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('admin_lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
  {{-- FormBuilder (LOCAL) --}}
  <script src="{{ asset('vendor/formbuilder/form-builder.min.js') }}"></script>
  <script>
  jQuery(function($) {
    $(document.getElementById('fb-editor')).formBuilder();
  });
  </script>
@stop
@section('content')
  <div id="fb-editor"></div>
@stop