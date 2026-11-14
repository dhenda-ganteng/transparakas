<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TransparaKas') — Kas Transparan, Organisasi Tepercaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F1F5F9] text-[#0F172A] font-sans antialiased min-h-screen flex flex-col"
    style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;">
    @yield('content')
</body>
</html>
