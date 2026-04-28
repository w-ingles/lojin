<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IdealFood') }}</title>

    <link rel="icon" href="/favicon.ico">

    {{-- PrimeVue theme e layout CSS servidos diretamente de /public --}}
    <link id="theme-css" rel="stylesheet" type="text/css" href="/theme/theme-blue.css">
    <link id="layout-css" rel="stylesheet" type="text/css" href="/layout/css/layout-blue.css">

    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="loader-screen">
            <div class="loader">
                <div class="bar1"></div>
                <div class="bar2"></div>
                <div class="bar3"></div>
                <div class="bar4"></div>
                <div class="bar5"></div>
                <div class="bar6"></div>
            </div>
        </div>
    </div>
</body>
</html>
