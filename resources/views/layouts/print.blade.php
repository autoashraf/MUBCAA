<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Document' }}</title>
        <style>{!! file_get_contents(resource_path('css/print.css')) !!}</style>
    </head>
    <body>
        @yield('content')
    </body>
</html>
