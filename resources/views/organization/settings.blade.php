@extends('layouts.app')

@section('title', 'Pengaturan Organisasi & Kode Undangan')
@section('header', 'Pengaturan Organisasi & Kode Undangan')

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Bagian 1 & 3: Informasi Organisasi & Kebijakan Keterlihatan Transaksi (PRD 10.17 & Aturan S-05) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Informasi & Kebijakan Organisasi</h3>
            <p class="text-xs text-slate-500">Atur identitas organisasi dan sejauh mana anggota dapat melihat transaksi kas.</p>
        </div>

        <form method="POST" action="{{ route('organization.settings.update') }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Organisasi</label>
                <input type="text" name="name" value="{{ old('name', $organization->name) }}" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Organisasi</label>
                <input type="text" name="type" value="{{ old('type', $organization->type) }}" placeholder="HIMA / BEM / UKM"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                <label class="block text-xs font-bold text-slate-800">Kebijakan Keterlihatan Transaksi bagi Anggota (Aturan S-05)</label>
                <div class="space-y-2 text-xs">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="radio" name="visibility_policy" value="summary" @checked($organization->visibility_policy === 'summary') class="mt-0.5 text-[#0F766E]">
                        <span>
                            <strong class="text-slate-900 block">Ringkasan Publik Saja (Default)</strong>
                            <span class="text-slate-500">Anggota hanya melihat total saldo kas dan rekapitulasi bulanan per kategori, tanpa rincian baris transaksi.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="radio" name="visibility_policy" value="detail" @checked($organization->visibility_policy === 'detail') class="mt-0.5 text-[#0F766E]">
                        <span>
                            <strong class="text-slate-900 block">Detail Transaksi Terbuka</strong>
                            <span class="text-slate-500">Anggota diizinkan melihat daftar transaksi per baris dan bukti nota secara transparan.</span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold text-xs shadow-sm">
                Simpan Pengaturan Organisasi
            </button>
        </form>
    </div>

    <!-- Bagian 2: Kartu Kode Undangan (PRD 10.17 & Aturan S-04) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Kode Undangan Organisasi</h3>
                <p class="text-xs text-slate-500">Kode dibuat acak, memiliki masa berlaku, dan dapat dicabut sewaktu-waktu.</p>
            </div>
            @if($activeInvitation && $activeInvitation->isValid())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Kode Aktif</span>
            @else
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Tidak Aktif</span>
            @endif
        </div>

        @if($activeInvitation && $activeInvitation->isValid())
            <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-3">
                <p class="text-3xl font-mono font-bold tracking-widest text-[#0F766E]">{{ $activeInvitation->code }}</p>
                <p class="text-xs text-slate-600">
                    Peran Default: <strong class="uppercase">{{ $activeInvitation->default_role }}</strong> •
                    Kedaluwarsa: <strong>{{ $activeInvitation->expires_at->format('d/m/Y H:i') }}</strong>
                </p>

                <div class="flex flex-wrap justify-center gap-2 pt-1">
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $activeInvitation->code }}'); this.textContent='✔ Kode Disalin';"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700">
                        📋 Salin Kode
                    </button>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ route('register', ['code' => $activeInvitation->code]) }}'); this.textContent='✔ Tautan Disalin';"
                        class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700">
                        🔗 Salin Tautan Undangan
                    </button>

                    <!-- Tombol Cabut Kode dengan Konfirmasi (Aturan S-04) -->
                    <form method="POST" action="{{ route('organization.invitations.revoke', $activeInvitation) }}"
                        onsubmit="return confirm('Cabut kode undangan {{ $activeInvitation->code }}? Kode lama tidak akan berlaku lagi (Aturan S-04).')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg border border-red-300 bg-red-50 hover:bg-red-100 text-xs font-semibold text-red-700">
                            🚫 Cabut Kode
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="p-5 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                Saat ini tidak ada kode undangan aktif. Silakan buat kode undangan baru di bawah.
            </div>
        @endif

        <!-- Form Buat Ulang Kode Undangan -->
        <form method="POST" action="{{ route('organization.invitations.regenerate') }}"
            onsubmit="return confirm('Buat kode undangan baru? Kode aktif sebelumnya otomatis tidak berlaku lagi.')"
            class="pt-4 border-t border-slate-100 space-y-3 text-xs">
            @csrf
            <p class="font-semibold text-slate-800">Buat Kode Undangan Baru</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-600 mb-1">Peran Default</label>
                    <select name="default_role" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option value="anggota">Anggota</option>
                        <option value="bendahara">Bendahara</option>
                        <option value="auditor">Auditor / Ketua</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-600 mb-1">Masa Berlaku</label>
                    <select name="days_valid" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option value="7">7 Hari</option>
                        <option value="3">3 Hari</option>
                        <option value="14">14 Hari</option>
                        <option value="30">30 Hari</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold">
                🔄 Buat Kode Undangan Baru
            </button>
        </form>
    </div>
</div>

<!-- Pengajuan Penonaktifan Organisasi kepada Super Admin -->
<div class="mt-6 bg-white rounded-xl border border-red-200 shadow-sm p-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="space-y-1">
            <h3 class="text-base font-semibold text-red-700">Ajukan Penonaktifan Organisasi</h3>
            <p class="text-xs text-slate-600 max-w-2xl">
                Jika organisasi sudah tidak beroperasi atau masa kepengurusan telah berakhir permanen, Admin Organisasi dapat mengajukan penonaktifan akun organisasi kepada <strong>Super Admin Platform</strong>. Selama berstatus nonaktif, seluruh anggota tidak dapat masuk ke dasbor kas organisasi ini.
            </p>
        </div>
        @if($organization->deactivation_requested_at)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 shrink-0">
                ⏳ Menunggu Persetujuan Super Admin
            </span>
        @endif
    </div>

    @if($organization->deactivation_requested_at)
        <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-3">
            <div class="text-xs text-amber-900 space-y-1">
                <p><strong>Diajukan pada:</strong> {{ $organization->deactivation_requested_at->format('d/m/Y H:i') }}</p>
                <p><strong>Alasan Penonaktifan:</strong> {{ $organization->deactivation_reason }}</p>
            </div>
            <form method="POST" action="{{ route('organization.cancel-deactivation') }}"
                onsubmit="return confirm('Batalkan pengajuan penonaktifan organisasi ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold">
                    Batalkan Pengajuan
                </button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('organization.request-deactivation') }}" class="mt-4 space-y-3 text-sm"
            onsubmit="return confirm('Kirim permintaan penonaktifan organisasi {{ $organization->name }} kepada Super Admin Platform?')">
            @csrf
            <div>
                <label for="deactivation_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Alasan Penonaktifan <span class="text-red-600">*</span>
                </label>
                <textarea id="deactivation_reason" name="deactivation_reason" rows="3" required minlength="10" maxlength="500"
                    placeholder="Contoh: Kepengurusan periode 2025/2026 telah selesai dan organisasi dibekukan sementara."
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-red-400 focus:ring-red-400">{{ old('deactivation_reason') }}</textarea>
                @error('deactivation_reason')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold">
                    Ajukan Penonaktifan ke Super Admin
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
