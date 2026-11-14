@extends('layouts.app')

@section('title', 'Profil & Keamanan Akun')
@section('header', 'Profil & Ubah Password')

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Informasi Profil & Organisasi (Hanya-baca untuk Organisasi & Peran - PRD 10.19) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <h3 class="text-base font-semibold text-slate-900">Informasi Akun</h3>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email (Hanya-baca)</label>
                <input type="email" value="{{ $user->email }}" disabled
                    class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-500">
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Organisasi (Hanya-baca)</label>
                    <input type="text" value="{{ $membership?->organization?->name ?? 'Platform Super Admin' }}" disabled
                        class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs text-slate-600 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Peran (Hanya-baca)</label>
                    <input type="text" value="{{ $user->is_super_admin ? 'Super Admin' : ($membership?->role ?? '-') }}" disabled
                        class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs text-slate-600 font-semibold uppercase">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold text-xs">
                Simpan Perubahan Profil
            </button>
        </form>
    </div>

    <!-- Ubah Password -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <h3 class="text-base font-semibold text-slate-900">Ubah Password</h3>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password Saat Ini</label>
                <input type="password" name="current_password" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru (Min. 10 Karakter, Huruf & Angka)</label>
                <input type="password" name="password" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs">
                Perbarui Password
            </button>
        </form>
    </div>
</div>
@endsection
