@extends('layouts.guest')

@section('title', 'Pendaftaran Akun — TransparaKas')

@section('content')
@php
    $initialPathway = old('pathway', !empty($inviteCode) ? 'join' : 'create');
@endphp
<div class="min-h-screen bg-[#F1F5F9] relative py-10 px-4 sm:px-6 flex items-center justify-center overflow-hidden">
    <!-- Latar belakang pola grid halus (sama dengan halaman login) -->
    <div class="absolute inset-0 pointer-events-none opacity-40"
        style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="relative z-10 max-w-2xl w-full bg-white rounded-2xl shadow-xl border-2 border-slate-400 p-6 sm:p-10 space-y-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-brand-logo size="lg" />
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Daftar Akun TransparaKas</h1>
                    <p class="text-xs text-slate-500">Satu akun terhubung ke satu organisasi mahasiswa</p>
                </div>
            </div>
            <a href="{{ route('login') }}" class="text-sm font-medium text-[#0F766E] hover:underline">Sudah punya akun? Masuk</a>
        </div>

        @if($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 space-y-1">
                <p class="font-semibold">Mohon periksa kembali isian Anda:</p>
                <ul class="list-disc list-inside text-xs">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.submit') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="pathway" id="pathway_input" value="{{ $initialPathway }}">

            <!-- Langkah 1: Pemilih Jalur (2 Kartu Pilihan Besar - PRD 10.2) -->
            <div>
                <label class="block text-sm font-semibold text-slate-800 mb-3">Langkah 1: Pilih Jalur Pendaftaran</label>
                <div class="grid sm:grid-cols-2 gap-4">
                    <button type="button" id="card_create" onclick="selectPathway('create')"
                        class="text-left p-4 rounded-xl border-2 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">🏛️</span>
                                <span id="badge_create" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#CCFBF1] text-[#0F766E]">Dipilih</span>
                            </div>
                            <h3 class="font-semibold text-slate-900">Buat organisasi baru</h3>
                            <p class="text-xs text-slate-500 mt-1">Untuk ketua atau pengurus yang mendaftarkan ormawa/UKM baru. Anda otomatis menjadi <strong>Admin Organisasi</strong>.</p>
                        </div>
                    </button>

                    <button type="button" id="card_join" onclick="selectPathway('join')"
                        class="text-left p-4 rounded-xl border-2 transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">🎟️</span>
                                <span id="badge_join" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#CCFBF1] text-[#0F766E] hidden">Dipilih</span>
                            </div>
                            <h3 class="font-semibold text-slate-900">Gabung organisasi</h3>
                            <p class="text-xs text-slate-500 mt-1">Untuk anggota yang sudah memiliki <strong>Kode Undangan</strong> dari Admin Organisasi.</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Langkah 2: Data Akun & Organisasi -->
            <div class="space-y-4 pt-2 border-t border-slate-100">
                <label class="block text-sm font-semibold text-slate-800">Langkah 2: Lengkapi Data Diri & Organisasi</label>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="Contoh: Budi Santoso"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Mahasiswa / Aktif</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            placeholder="budi@mahasiswa.ac.id"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    </div>
                </div>

                <!-- Field Khusus Jalur Buat Organisasi -->
                <div id="section_create" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Organisasi / Ormawa</label>
                            <input type="text" name="organization_name" id="organization_name" value="{{ old('organization_name') }}"
                                placeholder="Contoh: HIMATIF Universitas Nusantara"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Organisasi (Opsional)</label>
                            <select name="organization_type" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                                <option value="HIMA">Himpunan Mahasiswa (HIMA)</option>
                                <option value="BEM">Badan Eksekutif Mahasiswa (BEM)</option>
                                <option value="UKM">Unit Kegiatan Mahasiswa (UKM)</option>
                                <option value="Kepanitiaan">Kepanitiaan Acara</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-[#0F766E] font-medium">
                        ℹ️ Pendaftar otomatis menjadi <strong>Admin Organisasi</strong> dan sistem akan membuatkan kode undangan unik.
                    </p>
                </div>

                <!-- Field Khusus Jalur Gabung Organisasi -->
                <div id="section_join" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 hidden">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Undangan Organisasi</label>
                        <div class="flex gap-2">
                            <input type="text" name="invitation_code" id="invitation_code" value="{{ old('invitation_code', $inviteCode) }}"
                                placeholder="Contoh: HIMA2026XY"
                                class="flex-1 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-mono uppercase tracking-wider focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                            <button type="button" onclick="navigator.clipboard.readText().then(t => document.getElementById('invitation_code').value = t.trim())"
                                class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700">
                                Tempel
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1.5">
                            Permintaan bergabung perlu disetujui oleh Admin Organisasi. Peran awal Anda adalah <strong>Anggota</strong>. Demi keamanan privasi organisasi, nama organisasi baru ditampilkan setelah permintaan terkirim (Aturan T-06).
                        </p>
                    </div>
                </div>

                <!-- Password & Indikator Kekuatan (PRD 10.2) -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" id="reg_password" required oninput="checkPasswordStrength(this.value)"
                            placeholder="Minimal 10 karakter"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required
                            placeholder="Ulangi password"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    </div>
                </div>

                <!-- Checklist Aturan Password -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-600">Kekuatan Password:</span>
                        <span id="pw_strength_label" class="font-semibold text-slate-500">Belum diisi</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                        <div id="pw_bar" class="h-full bg-slate-400 transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <ul class="text-xs space-y-1 pt-1">
                        <li id="rule_length" class="text-slate-500">○ Minimal 10 karakter</li>
                        <li id="rule_mix" class="text-slate-500">○ Mengandung kombinasi huruf dan angka</li>
                    </ul>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-3 text-sm shadow-sm transition">
                Daftar Sekarang
            </button>
        </form>
    </div>
</div>

<script>
function selectPathway(pathway) {
    document.getElementById('pathway_input').value = pathway;
    const cardCreate = document.getElementById('card_create');
    const cardJoin = document.getElementById('card_join');
    const badgeCreate = document.getElementById('badge_create');
    const badgeJoin = document.getElementById('badge_join');
    const sectionCreate = document.getElementById('section_create');
    const sectionJoin = document.getElementById('section_join');

    if (pathway === 'create') {
        cardCreate.className = 'text-left p-4 rounded-xl border-2 border-[#0F766E] bg-teal-50/30 transition flex flex-col justify-between';
        cardJoin.className = 'text-left p-4 rounded-xl border-2 border-slate-200 hover:border-slate-300 transition flex flex-col justify-between';
        badgeCreate.classList.remove('hidden');
        badgeJoin.classList.add('hidden');
        sectionCreate.classList.remove('hidden');
        sectionJoin.classList.add('hidden');
    } else {
        cardJoin.className = 'text-left p-4 rounded-xl border-2 border-[#0F766E] bg-teal-50/30 transition flex flex-col justify-between';
        cardCreate.className = 'text-left p-4 rounded-xl border-2 border-slate-200 hover:border-slate-300 transition flex flex-col justify-between';
        badgeJoin.classList.remove('hidden');
        badgeCreate.classList.add('hidden');
        sectionJoin.classList.remove('hidden');
        sectionCreate.classList.add('hidden');
    }
}

function checkPasswordStrength(val) {
    const hasLength = val.length >= 10;
    const hasMix = /[a-zA-Z]/.test(val) && /[0-9]/.test(val);

    const ruleLength = document.getElementById('rule_length');
    const ruleMix = document.getElementById('rule_mix');
    const bar = document.getElementById('pw_bar');
    const label = document.getElementById('pw_strength_label');

    ruleLength.textContent = (hasLength ? '✔ ' : '○ ') + 'Minimal 10 karakter';
    ruleLength.className = hasLength ? 'text-emerald-600 font-medium' : 'text-slate-500';

    ruleMix.textContent = (hasMix ? '✔ ' : '○ ') + 'Mengandung kombinasi huruf dan angka';
    ruleMix.className = hasMix ? 'text-emerald-600 font-medium' : 'text-slate-500';

    if (!val) {
        bar.style.width = '0%';
        label.textContent = 'Belum diisi';
    } else if (hasLength && hasMix) {
        bar.style.width = '100%';
        bar.className = 'h-full bg-emerald-600 transition-all duration-300';
        label.textContent = 'Kuat & Memenuhi Syarat';
        label.className = 'font-semibold text-emerald-600';
    } else {
        bar.style.width = '50%';
        bar.className = 'h-full bg-amber-500 transition-all duration-300';
        label.textContent = 'Belum memenuhi semua syarat';
        label.className = 'font-semibold text-amber-600';
    }
}

selectPathway('{{ $initialPathway }}');
</script>
@endsection
