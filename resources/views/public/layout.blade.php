<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name', 'PIOS'))</title>
        <meta name="description" content="@yield('meta_description', '')">

        {{-- Open Graph so the WhatsApp blast renders a rich link preview. --}}
        <meta property="og:title" content="@yield('og_title', config('app.name', 'PIOS'))">
        <meta property="og:description" content="@yield('og_description', '')">
        <meta property="og:type" content="product">
        @hasSection('og_image')
            <meta property="og:image" content="@yield('og_image')">
        @endif

        {{-- PWA META TAGS --}}
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#174A78">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="PIOS">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <style>
            /* Deeper brand blue + paper background */
            :root { --pios: #174A78; --pios-deep: #123A5F; --paper: #FBFAF7; }
            body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background: var(--paper); }
            .font-display { font-family: 'Fraunces', Georgia, serif; }
        </style>
    </head>
    <body class="min-h-screen text-slate-800 antialiased">
        @yield('body')

        <footer class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 text-center text-xs text-slate-400">
            {{ __('pios.public.powered_by') }}
        </footer>

        {{-- SERVICE WORKER REGISTER FOR PWA --}}
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('PWA Service Worker registered:', reg.scope))
                        .catch(err => console.log('PWA Service Worker registration failed:', err));
                });
            }
        </script>
    </body>
</html>
