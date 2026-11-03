<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title data-inertia>{{ config('app.name', 'myCLAP') }}</title>

    <link rel="icon" type="image/png" href="/favicon.ico">

    @isset($meta)
        <meta property="og:type" content="video.other">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $meta['description']), 200) }}">
        <meta property="og:image" content="{{ $meta['image'] }}">
        <meta property="og:video" content="{{ $meta['video'] }}">
        <meta property="og:video:type" content="video/mp4">
    @endisset

    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-dark-bg text-white">
@inertia
</body>
</html>
