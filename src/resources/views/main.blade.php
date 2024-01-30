<!DOCTYPE html>
<html lang="en" class="{{config('illuminar.theme', 'light')}}">
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
<div id="illuminar">
    <illuminar></illuminar>
</div>
<script src="{{ asset(mix('app.js', 'vendor/illuminar')) }}"></script>

</body>
</html>
