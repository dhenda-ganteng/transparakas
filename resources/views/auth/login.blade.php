@extends('layouts.guest')

@section('title', 'Masuk ke TransparaKas')

@section('content')
<div class="min-h-screen bg-[#F1F5F9] relative flex items-center justify-center p-4 sm:p-6 lg:p-10 overflow-hidden">
    <!-- Latar belakang pola grid halus -->
    <div class="absolute inset-0 pointer-events-none opacity-40"
        style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;"></div>

    <!-- Kartu Utama Login -->
    <div class="relative z-10 w-full max-w-5xl bg-white rounded-2xl border-2 border-slate-400 shadow-xl overflow-hidden grid lg:grid-cols-12">

        <!-- Panel Kiri: Identitas & Keunggulan Sistem -->
        <div class="lg:col-span-5 bg-gradient-to-br from-[#0F766E] via-[#115E59] to-[#0F172A] text-white p-8 sm:p-10 flex flex-col justify-between gap-8 border-b-2 lg:border-b-0 lg:border-r-2 border-[#064E3B]">
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/25 text-xs font-semibold text-teal-100 tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Sistem Kas Organisasi Mahasiswa
                </div>

                <div class="space-y-3">
                    <h2 class="text-2xl sm:text-3xl font-bold leading-snug tracking-tight">
                        Kas transparan, organisasi tepercaya.
                    </h2>
                    <p class="text-sm text-teal-100/90 leading-relaxed">
                        Catat pemasukan, pengeluaran, iuran anggota, dan anggaran kegiatan dalam satu platform yang teraudit.
                    </p>
                </div>

                <!-- Ringkasan Fitur dengan Outline Tegas -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 border border-white/25">
                        <div class="w-8 h-8 rounded-lg bg-emerald-400/20 border border-emerald-300/40 flex items-center justify-center shrink-0 text-emerald-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Audit Trail Otomatis</p>
                            <p class="text-[11px] text-teal-100/85 mt-0.5">Merekam pelaku, waktu, serta nilai sebelum &amp; sesudah perubahan.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 border border-white/25">
                        <div class="w-8 h-8 rounded-lg bg-emerald-400/20 border border-emerald-300/40 flex items-center justify-center shrink-0 text-emerald-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Isolasi Data Multi-Tenant</p>
                            <p class="text-[11px] text-teal-100/85 mt-0.5">Data kas setiap organisasi terpisah aman sesuai hak akses peran.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 border border-white/25">
                        <div class="w-8 h-8 rounded-lg bg-emerald-400/20 border border-emerald-300/40 flex items-center justify-center shrink-0 text-emerald-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Laporan &amp; Anggaran Real-Time</p>
                            <p class="text-[11px] text-teal-100/85 mt-0.5">Pantau arus kas bulanan, iuran anggota, dan realisasi kegiatan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-teal-200/80 border-t border-white/15 pt-4">
                © {{ date('Y') }} TransparaKas — Transparansi Keuangan Ormawa
            </p>
        </div>

        <!-- Panel Kanan: Form Login -->
        <div class="lg:col-span-7 p-8 sm:p-10 lg:p-12 flex flex-col justify-center bg-white">
            <div class="max-w-md w-full mx-auto space-y-6">
                <!-- Brand Header -->
                <div class="flex items-center gap-3 pb-4 border-b border-slate-200">
                    <x-brand-logo size="lg" />
                    <div>
                        <span class="font-bold text-xl tracking-tight text-slate-900">TransparaKas</span>
                        <span class="block text-xs font-medium text-slate-500">Audit Trail Kas Organisasi</span>
                    </div>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Masuk ke Akun Anda</h1>
                    <p class="text-sm text-slate-500 mt-1">Masukkan email dan password yang terdaftar untuk melanjutkan.</p>
                </div>

                @if($errors->any())
                    <div class="rounded-xl bg-red-50 border-2 border-red-300 p-4 text-sm text-red-800 flex items-start gap-2.5">
                        <span class="shrink-0">⚠️</span>
                        <div class="space-y-0.5">
                            @foreach($errors->all() as $error)
                                <p class="font-medium">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                placeholder="nama@kampus.ac.id"
                                class="w-full rounded-xl pl-10 pr-3.5 py-2.5 text-sm bg-white">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input id="password" type="password" name="password" required
                                placeholder="Masukkan password"
                                class="w-full rounded-xl pl-10 pr-24 py-2.5 text-sm bg-white">
                            <button type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; this.textContent = p.type === 'password' ? 'Tampilkan' : 'Sembunyikan';"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 px-2 py-1 rounded-md border border-slate-300 bg-slate-50 hover:bg-slate-100 text-[11px] font-semibold text-slate-700">
                                Tampilkan
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm pt-1">
                        <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-400 text-[#0F766E]">
                            <span>Ingat saya</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-[#0F766E] hover:bg-[#115E59] border-2 border-[#064E3B] text-white font-semibold py-2.5 text-sm shadow-sm transition cursor-pointer">
                        Masuk ke Dasbor →
                    </button>
                </form>

                <div class="pt-4 border-t border-slate-200 text-center text-sm text-slate-600">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-bold text-[#0F766E] hover:underline ml-1">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
