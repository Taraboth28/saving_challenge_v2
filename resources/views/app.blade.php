<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Saving Challenge') }}</title>
    <link rel="icon" href="/favicon.ico">
    <script>
        {{-- Apply day / night mode before first paint (mirrors resources/js/composables/useTheme.js). --}}
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('theme'); } catch (e) {}
            var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();

        window.__APP_CONFIG__ = {{ Js::from(['name' => config('app.name', 'Saving Challenge'), 'currency' => config('savings.currency')]) }};
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
