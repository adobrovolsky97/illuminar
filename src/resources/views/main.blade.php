<!DOCTYPE html>
<html lang="en" class="{{ config('illuminar.theme', 'dark') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ route('illuminar.asset', ['file' => 'favicon.ico']) }}">

    <title>Illuminar{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <script>
        // Apply saved theme before the first paint
        try {
            var theme = localStorage.getItem('illuminar-theme');
            if (theme) {
                document.documentElement.classList.toggle('dark', theme === 'dark');
            }
        } catch (e) {
        }
        window.Illuminar = @json($config);
    </script>

    {!! $dumpHeader !!}

    <link href="{{ route('illuminar.asset', ['file' => 'app.css']) }}?v={{ $assetVersion }}" rel="stylesheet">
</head>
<body class="bg-white dark:bg-gray-900">
<div id="illuminar"></div>
<script type="module" src="{{ route('illuminar.asset', ['file' => 'app.js']) }}?v={{ $assetVersion }}"></script>
</body>
</html>
