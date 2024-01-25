<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('/vendor/illuminar/favicon.ico') }}">

    <meta name="robots" content="noindex, nofollow">

    <title>Illuminar{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600" rel="stylesheet"/>
    <link href="{{ asset(mix('app.css', 'vendor/illuminar')) }}" rel="stylesheet" type="text/css">
</head>
<body>
<div id="illuminar" class="min-h-full">
    <header class="bg-white shadow">
        <nav class="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="{{route('illuminar')}}" class="-m-1.5 p-1.5">
                    <span class="self-center text-xl font-semibold whitespace-nowrap">Illuminar</span>
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button"
                        class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <div class="mx-auto max-w-7xl text-xs py-6 sm:px-6 lg:px-2">
            <illuminar></illuminar>
        </div>
    </main>
</div>
<script src="{{ asset(mix('app.js', 'vendor/illuminar')) }}"></script>

</body>
</html>
