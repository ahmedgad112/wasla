<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-name" content="{{ config('app.name', 'وصلة') }}">
    <meta name="color-scheme" content="light">

    <title inertia>{{ config('app.name', 'وصلة') }}</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="apple-touch-icon" href="/images/logo.png">

    <script>
        (function () {
            try {
                var theme = localStorage.getItem('fatrna_theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    localStorage.setItem('fatrna_theme', 'light');
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Google Fonts: Cairo (Arabic) & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- CSS is imported by app.ts via Vite. --}}
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-[#fafaf9] text-[#1c1917] dark:bg-[#0c0a09] dark:text-[#f5f5f4] selection:bg-orange-500 selection:text-white">
    @inertia
</body>
</html>
