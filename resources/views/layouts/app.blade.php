<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — TransparaKas</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" defer></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-[#F1F5F9] text-[#0F172A] font-sans antialiased min-h-screen flex"
    style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px;">
@php
    $user = auth()->user();
    $membership = $user?->membership()->with('organization')->first();
    $org = $membership?->organization;
    $role = $user?->is_super_admin ? 'super_admin' : ($membership?->role ?? 'guest');

    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin_organisasi' => 'Admin Organisasi',
        'bendahara' => 'Bendahara',
        'auditor' => 'Auditor / Ketua',
        'anggota' => 'Anggota',
    ];

    $roleBadgeColors = [
        'super_admin' => 'bg-purple-100 text-purple-800 border-purple-200',
        'admin_organisasi' => 'bg-blue-100 text-blue-800 border-blue-200',
        'bendahara' => 'bg-[#CCFBF1] text-[#0F766E] border-teal-300',
        'auditor' => 'bg-amber-100 text-amber-800 border-amber-200',
        'anggota' => 'bg-slate-100 text-slate-700 border-slate-200',
    ];

    $pendingJoinCount = ($role === 'admin_organisasi' && $org)
        ? \App\Models\JoinRequest::where('org_id', $org->id)->where('status', 'pending')->count()
        : 0;

    $pendingDuesCount = ($role === 'bendahara' && $org)
        ? \App\Models\Due::where('org_id', $org->id)->where('status', 'pending')->count()
        : 0;

    $myUnpaidDuesCount = ($user && $org)
        ? \App\Models\Due::where('org_id', $org->id)->where('user_id', $user->id)->whereIn('status', ['unpaid', 'rejected'])->count()
        : 0;
@endphp

    <!-- Overlay Mobile Sidebar -->
    <div id="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden');"
        class="no-print fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-[1px] hidden lg:hidden"></div>

    <!-- Sidebar Desktop & Mobile (Tetap diam / tidak ikut scroll) -->
    <aside id="sidebar" class="no-print fixed inset-y-0 left-0 z-40 w-64 shrink-0 bg-white border-r-2 border-slate-300 shadow-sm transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto transition-transform duration-200 flex flex-col justify-between">
        <div>
            <!-- Logo Brand -->
            <div class="h-16 px-5 flex items-center justify-between border-b-2 border-slate-300 bg-slate-50/80">
                <a href="{{ $role === 'super_admin' ? route('platform.organizations') : route('dashboard') }}" class="flex items-center gap-2.5">
                    <x-brand-logo size="md" />
                    <div>
                        <span class="font-bold text-lg tracking-tight text-slate-900">TransparaKas</span>
                        <span class="block text-[11px] font-medium text-slate-500 -mt-1">Audit Trail Kas Ormawa</span>
                    </div>
                </a>
                <button type="button" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); document.getElementById('sidebar-backdrop').classList.add('hidden');"
                    class="lg:hidden p-1.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 hover:text-slate-900" aria-label="Tutup menu">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @php
                $navActive = 'bg-[#CCFBF1] text-[#0F766E] border border-[#0F766E]/50 font-semibold shadow-2xs';
                $navIdle = 'text-slate-700 border border-transparent hover:bg-slate-100 hover:border-slate-300 hover:text-slate-900';
            @endphp

            <!-- Menu Navigasi sesuai Tabel Bagian 7 PRD -->
            <nav class="p-3.5 space-y-1 text-sm font-medium">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Menu Utama</p>

                @if($role === 'super_admin')
                    <a href="{{ route('platform.organizations') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('platform.*') ? $navActive : $navIdle }}">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Daftar Organisasi Platform</span>
                    </a>
                @else
                    <!-- Dashboard (Semua peran di organisasi) -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Transaksi (Bendahara CRUD, Auditor baca, Anggota jika kebijakan 'detail' S-05) -->
                    @if(in_array($role, ['bendahara', 'auditor']) || ($role === 'anggota' && $org?->visibility_policy === 'detail'))
                        <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('transactions.*') ? $navActive : $navIdle }}">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span>Transaksi Keuangan</span>
                        </a>
                    @endif

                    <!-- Iuran (Bendahara verifikasi, Auditor baca, Anggota milik sendiri) -->
                    @if(in_array($role, ['bendahara', 'auditor']))
                        <a href="{{ route('dues.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dues.index') ? $navActive : $navIdle }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Verifikasi Iuran</span>
                            </span>
                            @if($pendingDuesCount > 0)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 border border-amber-300 text-amber-800 font-bold">{{ $pendingDuesCount }}</span>
                            @endif
                        </a>
                    @endif

                    <!-- Iuran Saya (Semua peran di organisasi ikut membayar kas pribadinya) -->
                    @if(in_array($role, ['admin_organisasi', 'bendahara', 'auditor', 'anggota']))
                        <a href="{{ route('dues.my') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dues.my') ? $navActive : $navIdle }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <span>Iuran Saya</span>
                            </span>
                            @if($myUnpaidDuesCount > 0)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 border border-red-300 text-red-700 font-bold">{{ $myUnpaidDuesCount }}</span>
                            @endif
                        </a>
                    @endif

                    <!-- Anggaran Kegiatan (Bendahara, Auditor, Anggota) -->
                    @if(in_array($role, ['bendahara', 'auditor', 'anggota']))
                        <a href="{{ route('budgets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('budgets.*') ? $navActive : $navIdle }}">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <span>Anggaran Kegiatan</span>
                        </a>
                    @endif

                    <!-- Laporan / Ringkasan Kas (Bendahara, Auditor, Anggota) -->
                    @if(in_array($role, ['bendahara', 'auditor', 'anggota']))
                        <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('reports.*') ? $navActive : $navIdle }}">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>{{ $role === 'anggota' ? 'Ringkasan Kas' : 'Laporan Keuangan' }}</span>
                        </a>
                    @endif

                    <!-- Kategori (Bendahara) -->
                    @if($role === 'bendahara')
                        <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('categories.*') ? $navActive : $navIdle }}">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Kategori</span>
                        </a>
                    @endif

                    <!-- Audit Log (Auditor & Admin Organisasi) -->
                    @if(in_array($role, ['auditor', 'admin_organisasi']))
                        <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('audit-logs.*') ? $navActive : $navIdle }}">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>Audit Log</span>
                        </a>
                    @endif

                    <!-- Menu Khusus Admin Organisasi -->
                    @if($role === 'admin_organisasi')
                        <div class="pt-3 mt-3 border-t border-slate-200 space-y-1">
                            <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Kelola Organisasi</p>
                            <a href="{{ route('organization.members') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('organization.members') ? $navActive : $navIdle }}">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Anggota Organisasi</span>
                            </a>
                            <a href="{{ route('organization.join-requests') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('organization.join-requests') ? $navActive : $navIdle }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                    <span>Permintaan Gabung</span>
                                </span>
                                @if($pendingJoinCount > 0)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 border border-amber-300 text-amber-800 font-bold">{{ $pendingJoinCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('organization.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('organization.settings') ? $navActive : $navIdle }}">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Pengaturan &amp; Kode</span>
                            </a>
                        </div>
                    @endif
                @endif
            </nav>
        </div>

        <!-- Profil & Logout di Bawah Sidebar -->
        <div class="p-3.5 border-t-2 border-slate-200 bg-slate-50/70 space-y-2">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('profile.*') ? $navActive : $navIdle }}">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profil &amp; Password</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold text-red-700 bg-white border border-red-300 hover:bg-red-50 transition cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Header Topbar (PRD Bagian 2.6: Selalu tampilkan nama organisasi & badge peran) -->
        <header class="no-print h-16 bg-white/95 backdrop-blur-md border-b-2 border-slate-300 shadow-2xs px-4 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-20">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('sidebar-backdrop').classList.toggle('hidden');"
                    class="lg:hidden p-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-100" aria-label="Buka menu">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="min-w-0">
                    <h1 class="text-base sm:text-lg lg:text-xl font-bold tracking-tight text-slate-900 truncate">@yield('header', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                <!-- Pengenal Organisasi & Badge Peran -->
                <div class="flex items-center gap-2 bg-slate-100 border border-slate-400 rounded-xl px-3 py-1.5 shadow-2xs">
                    <svg class="w-4 h-4 text-[#0F766E] hidden sm:block shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-xs sm:text-sm font-bold text-slate-800 max-w-[140px] sm:max-w-xs truncate">
                        {{ $role === 'super_admin' ? 'Platform TransparaKas' : ($org?->name ?? 'Belum Ada Organisasi') }}
                    </span>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full border font-bold {{ $roleBadgeColors[$role] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $roleLabels[$role] ?? $role }}
                    </span>
                </div>

                <a href="{{ route('profile.edit') }}" class="hidden sm:flex items-center gap-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 px-2.5 py-1.5 text-sm text-slate-800 hover:text-[#0F766E] font-semibold transition">
                    <div class="w-7 h-7 rounded-lg bg-[#0F766E] border border-[#064E3B] text-white flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
                    </div>
                    <span class="max-w-[130px] truncate">{{ $user?->name }}</span>
                </a>

                <!-- Tombol Mode Gelap / Terang -->
                <button type="button" id="theme-toggle" onclick="toggleTheme()" title="Ganti Mode Gelap / Terang"
                    class="p-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 transition cursor-pointer" aria-label="Ganti tema">
                    <svg id="theme-icon-moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg id="theme-icon-sun" class="w-4 h-4 hidden text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </header>
        <script>
            function syncThemeIcons() {
                const isDark = document.documentElement.classList.contains('dark');
                document.getElementById('theme-icon-moon')?.classList.toggle('hidden', isDark);
                document.getElementById('theme-icon-sun')?.classList.toggle('hidden', !isDark);
            }
            function toggleTheme() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                syncThemeIcons();
            }
            syncThemeIcons();
        </script>

        <!-- Area Isi Halaman -->
        <main class="flex-1 p-4 lg:p-8 max-w-[1280px] w-full mx-auto space-y-6">
            @if(session('success'))
                <div class="no-print rounded-xl bg-emerald-50 border border-emerald-200 p-4 flex items-center justify-between text-emerald-800 text-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">✅</span>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="no-print rounded-xl bg-red-50 border border-red-200 p-4 flex items-center justify-between text-red-800 text-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base">⚠️</span>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-900">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div class="no-print rounded-xl bg-red-50 border border-red-200 p-4 text-red-800 text-sm space-y-1">
                    <p class="font-semibold">Mohon periksa kembali input Anda:</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
