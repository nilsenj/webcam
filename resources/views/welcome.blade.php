<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
        <link href="{{asset('css/spectre.min.css', true)}}" rel="stylesheet" type="text/css">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Styles -->
        <style>
            .full-height {
                height: 100vh;
            }
        </style>
    </head>
    <body>
    <!-- include adapter for srcObject shim -->
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
    <script src='https://cdn.rawgit.com/zhuker/lamejs/c318d57d/lame.min.js'></script>
    <script src='https://unpkg.com/media-recorder-js/mediaRecorder.js'></script>
        <div id="app" class="full-height">
            <recorder></recorder>
        </div>
        <script SRC="{{asset('js/app.js', true)}}">
        </script>
    </body>
</html>
