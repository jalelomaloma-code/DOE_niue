@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' — ' : '' }}Niue Department of Environment</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-text antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50
              focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-white">
        Skip to main content
    </a>

    <x-site.header />

    <main id="main" tabindex="-1">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
