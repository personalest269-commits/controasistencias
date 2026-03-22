<!doctype html>
<html lang="{{ app()->getLocale() ?? 'es' }}">
<head>
  <meta charset="UTF-8">
  <title>Laravel Filemanager</title>
  <link rel="shortcut icon" type="image/png" href="{{ asset('/assets/img/folder.png') }}">
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap3/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}">
</head>
<body>
  <div class="container">
    <h1 class="page-header">Integration Demo Page</h1>
    <div class="row">
      <div class="col-md-6">
        <h2>CKEditor</h2>
        <textarea name="ce" class="form-control"></textarea>
      </div>
      <div class="col-md-6">
        <h2>TinyMCE</h2>
        <textarea name="tm" class="form-control"></textarea>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <h2>Standalone Button</h2>
        <div class="input-group">
          <span class="input-group-btn">
            <a id="lfm" data-input="thumbnail" data-preview="holder" class="btn btn-primary">
              <i class="fa fa-picture-o"></i> Choose
            </a>
          </span>
          <input id="thumbnail" class="form-control" type="text" name="filepath">
        </div>
        <img id="holder" style="margin-top:15px;max-height:100px;">
      </div>
    </div>
  </div>

  <script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
  {{-- Bootstrap 3 (LOCAL) requerido por este filemanager --}}
  <script src="{{ asset('vendor/bootstrap3/bootstrap.min.js') }}"></script>
  <script>
   var route_prefix = "{{ url(config('lfm.prefix')) }}";
  </script>

  <!-- CKEditor init -->
  <script src="{{ asset('assets/js/ckeditor/ckeditor.js') }}"></script>
  <script src="{{ asset('assets/js/ckeditor/adapters/jquery.js') }}"></script>
  <script>
    $('textarea[name=ce]').ckeditor({
      height: 100,
      filebrowserImageBrowseUrl: route_prefix + '?type=Images',
      filebrowserImageUploadUrl: route_prefix + '/upload?type=Images&_token={{csrf_token()}}',
      filebrowserBrowseUrl: route_prefix + '?type=Files',
      filebrowserUploadUrl: route_prefix + '/upload?type=Files&_token={{csrf_token()}}'
    });
  </script>

  <!-- TinyMCE init -->
  {{-- TinyMCE (LOCAL) --}}
  <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
  <script>
    var editor_config = {
      path_absolute : "",
      selector: "textarea[name=tm]",
      plugins: [
        "link image"
      ],
      relative_urls: false,
      height: 129,
      file_browser_callback : function(field_name, url, type, win) {
        var x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
        var y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight;

        var cmsURL = editor_config.path_absolute + route_prefix + '?field_name=' + field_name;
        if (type == 'image') {
          cmsURL = cmsURL + "&type=Images";
        } else {
          cmsURL = cmsURL + "&type=Files";
        }

        tinyMCE.activeEditor.windowManager.open({
          file : cmsURL,
          title : 'Filemanager',
          width : x * 0.8,
          height : y * 0.8,
          resizable : "yes",
          close_previous : "no"
        });
      }
    };

    tinymce.init(editor_config);
  </script>

  <script>
    {!! \File::get(base_path('vendor/unisharp/laravel-filemanager/public/js/lfm.js')) !!}
  </script>
  <script>
    $('#lfm').filemanager('files', {prefix: route_prefix});
  </script>
</body>
</html>
