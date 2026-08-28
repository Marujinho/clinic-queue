<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Clinic Queue' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-background flex items-center justify-center font-sans text-ink antialiased">
    <div class="max-w-md w-full px-4">
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary"></span>
            <span class="font-semibold text-ink text-lg">Clinic Queue</span>
        </div>

        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
