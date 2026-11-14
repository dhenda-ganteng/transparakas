<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TransparaKas') — Kas Transparan, Organisasi Tepercaya</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F1F5F9] text-[#0F172A] font-sans antialiased min-h-screen flex flex-col"
    style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;">
    <!-- Tombol Mode Gelap / Terang di Halaman Tamu -->
    <button type="button" onclick="toggleGuestTheme()" title="Ganti Mode Gelap / Terang"
        class="fixed top-4 right-4 z-50 p-2.5 rounded-xl border-2 border-slate-400 bg-white hover:bg-slate-100 text-slate-700 shadow-sm transition cursor-pointer" aria-label="Ganti tema">
        <svg id="guest-icon-moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <svg id="guest-icon-sun" class="w-4 h-4 hidden text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
    </button>
    @yield('content')
    <script>
        function syncGuestThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('guest-icon-moon')?.classList.toggle('hidden', isDark);
            document.getElementById('guest-icon-sun')?.classList.toggle('hidden', !isDark);
        }
        function toggleGuestTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            syncGuestThemeIcons();
        }
        syncGuestThemeIcons();
    </script>
</body>
</html>
