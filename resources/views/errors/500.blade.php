<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Server Error</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body style="padding:40px;">
    <div class="container">
        <div class="alert alert-danger">
            <h4>Server Error</h4>
            <p>{{ $error_message ?? 'An error occurred.' }}</p>
        </div>
    </div>
</body>
</html>
