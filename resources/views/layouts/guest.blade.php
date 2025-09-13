<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" integrity="sha512-gZ5m16rDpD+s/rs24kTx5cDIe8JD7BqXc9E+u6KDAdAm8YGtS+wGGyRyvE4s46HoPazTA/gkGEXUXMaLLfi5iw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="grey lighten-4">
    @yield('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js" integrity="sha512-CyfgQ2YeliYg+6TS//N/xGaxzwoMPmxjSPdZRhqUWLWyd4InENcy+XUG6uHgnIY7qDpiRnbV0wCJAV2Xt0hVQw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @yield('scripts')
</body>
</html>
