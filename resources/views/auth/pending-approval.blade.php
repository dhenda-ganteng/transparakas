@extends('layouts.guest')

@section('title', 'Status Keanggotaan — TransparaKas')

@section('content')
<div class="min-h-screen py-12 px-4 flex items-center justify-center">
    <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl border-2 border-slate-400 p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2.5">
                <x-brand-logo size="sm" />
                <span class="font-bold text-slate-900">TransparaKas</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Keluar ({{ auth()->user()->name }})</button>
            </form>
        </div>

        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-medium">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-medium">
                @foreach($errors->all() as $error)
                    <p>⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if($membership && $membership->organization?->status === 'inactive')
            <!-- Varian: Organisasi Dinonaktifkan -->
            <div class="text-center space-y-3 py-4">
                <div class="w-14 h-14 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-2xl mx-auto">🚫</div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Organisasi Nonaktif</span>
                <h1 class="text-xl font-bold text-slate-900">Organisasi Sedang Dinonaktifkan</h1>
                <p class="text-sm text-slate-600">
                    Organisasi <strong>{{ $membership->organization->name }}</strong> saat ini sedang dinonaktifkan oleh pengelola platform.
                </p>
            </div>
        @elseif($membership && $membership->status === 'inactive')
            <!-- Varian: Keanggotaan Dinonaktifkan -->
            <div class="text-center space-y-3 py-4">
                <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-2xl mx-auto">⏸️</div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Keanggotaan Nonaktif</span>
                <h1 class="text-xl font-bold text-slate-900">Akun Keanggotaan Anda Nonaktif</h1>
                <p class="text-sm text-slate-600">
                    Keanggotaan Anda di <strong>{{ $membership->organization?->name }}</strong> telah dinonaktifkan oleh Admin Organisasi.
                </p>
            </div>
        @elseif($joinRequest && $joinRequest->status === 'pending')
            <!-- Layar 5: Status Menunggu Persetujuan (PRD 10.5) -->
            <div class="text-center space-y-3 py-2">
                <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-2xl mx-auto">⏳</div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Menunggu Persetujuan</span>
                <h1 class="text-xl font-bold text-slate-900">Menunggu Persetujuan Admin Organisasi</h1>
                <p class="text-sm text-slate-600">
                    Permintaan bergabung Anda telah dikirim. Anda akan mendapatkan akses ke dashboard begitu Admin Organisasi menyetujui permintaan Anda.
                </p>
            </div>

            <!-- Nama organisasi baru ditampilkan SETELAH permintaan terkirim (Aturan T-06) -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Organisasi Tujuan:</span>
                    <span class="font-semibold text-slate-900">{{ $joinRequest->organization?->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Waktu Permintaan:</span>
                    <span class="font-medium text-slate-700">{{ $joinRequest->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('onboarding.cancel') }}" onsubmit="return confirm('Batalkan permintaan bergabung ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 font-semibold text-xs transition">
                    Batalkan Permintaan Gabung
                </button>
            </form>
        @else
            <!-- Varian: Permintaan Ditolak atau Belum Mengirim Kode Undangan (Layar 4 & 5) -->
            @if($joinRequest && $joinRequest->status === 'rejected')
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 space-y-1 text-center">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Permintaan Ditolak</span>
                    <p class="text-sm font-semibold text-slate-900 mt-1">
                        Permintaan gabung ke {{ $joinRequest->organization?->name }} belum disetujui.
                    </p>
                    @if($joinRequest->rejection_reason)
                        <p class="text-xs text-red-700">Catatan: "{{ $joinRequest->rejection_reason }}"</p>
                    @endif
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Gabung dengan Kode Undangan</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Masukkan kode undangan aktif yang Anda terima dari Admin Organisasi. Nama organisasi hanya akan ditampilkan setelah permintaan terkirim (Aturan T-06).
                    </p>
                </div>

                <form method="POST" action="{{ route('onboarding.join') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Undangan</label>
                        <input type="text" name="invitation_code" required placeholder="Masukkan kode undangan"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-mono uppercase tracking-wider focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-2.5 text-sm shadow-sm transition">
                        Kirim Permintaan Gabung
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
