@extends('layouts.guest')

@section('title', 'Organisasi Berhasil Dibuat — TransparaKas')

@section('content')
<div class="min-h-screen py-12 px-4 flex items-center justify-center">
    <div class="max-w-xl w-full bg-white rounded-2xl shadow-xl border-2 border-slate-400 p-8 space-y-6">
        <div class="text-center space-y-2">
            <div class="w-14 h-14 rounded-2xl bg-[#CCFBF1] text-[#0F766E] flex items-center justify-center text-2xl mx-auto font-bold">
                🎉
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Organisasi Berhasil Dibuat!</h1>
            <p class="text-sm text-slate-600">
                Selamat! Anda kini terdaftar sebagai <span class="font-semibold text-[#0F766E]">Admin Organisasi</span> untuk
                <strong>{{ $organization->name }}</strong>.
            </p>
        </div>

        @if($invitation)
            <!-- Kartu Kode Undangan (PRD 10.3) -->
            <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Kode Undangan Anggota</span>
                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-800 font-semibold">Kode Aktif</span>
                </div>

                <div class="p-4 rounded-xl bg-white border border-slate-200 text-center">
                    <p id="invite_code_text" class="text-3xl font-mono font-bold tracking-widest text-[#0F766E]">{{ $invitation->code }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        Berlaku sampai: {{ $invitation->expires_at->translatedFormat('d F Y, H:i') }}
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $invitation->code }}'); this.textContent='✔ Kode Tersalin!';"
                        class="w-full py-2.5 px-4 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 font-semibold text-xs transition">
                        📋 Salin Kode Undangan
                    </button>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ route('register', ['code' => $invitation->code]) }}'); this.textContent='✔ Tautan Tersalin!';"
                        class="w-full py-2.5 px-4 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 font-semibold text-xs transition">
                        🔗 Salin Tautan Undangan
                    </button>
                </div>
            </div>
        @endif

        <!-- Panduan 3 Langkah -->
        <div class="space-y-3">
            <h2 class="text-sm font-semibold text-slate-800">Langkah Berikutnya:</h2>
            <div class="grid sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                    <span class="font-bold text-[#0F766E] block mb-1">1. Bagikan Kode</span>
                    <span class="text-slate-600">Kirim kode undangan ke pengurus & anggota organisasi Anda.</span>
                </div>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                    <span class="font-bold text-[#0F766E] block mb-1">2. Setujui Permintaan</span>
                    <span class="text-slate-600">Tinjau dan setujui pendaftar di menu Permintaan Gabung.</span>
                </div>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                    <span class="font-bold text-[#0F766E] block mb-1">3. Atur Peran</span>
                    <span class="text-slate-600">Tetapkan siapa yang menjadi Bendahara dan Auditor/Ketua.</span>
                </div>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="block w-full text-center rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-3 text-sm shadow-sm transition">
            Lanjut ke Dashboard →
        </a>
    </div>
</div>
@endsection
