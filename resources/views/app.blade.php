<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.png" sizes="any">
        <link rel="apple-touch-icon" href="/favicon.png">

        @fonts

        <meta name="og:site_name" content="{{ config('app.name', 'Portfolio') }}">
        <meta name="og:type" content="website">
        <meta name="og:url" content="{{ request()->url() }}">
        @if(isset($metaTitle))<meta name="og:title" content="{{ $metaTitle }}">@endif
        @if(isset($metaImage))<meta name="og:image" content="{{ $metaImage }}">@endif
        @if(isset($metaImageAlt))<meta name="og:image:alt" content="{{ $metaImageAlt }}">@endif
        @if(isset($metaDescription))<meta name="description" content="{{ $metaDescription }}">@endif

        <meta name="robots" content="{{ $metaRobots ?? 'index,follow' }}">
        @if(isset($metaKeywords))<meta name="keywords" content="{{ $metaKeywords }}">@endif

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
