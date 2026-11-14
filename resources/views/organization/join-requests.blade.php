@extends('layouts.app')

@section('title', 'Persetujuan Permintaan Gabung')
@section('header', 'Persetujuan Permintaan Gabung Anggota')

@section('content')
<!-- Daftar Permintaan Menunggu Persetujuan (PRD 10.18) -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <h3 class="text-base font-semibold text-slate-900">Permintaan Gabung Menunggu Persetujuan ({{ $pendingRequests->count() }})</h3>
        <p class="text-xs text-slate-500">Pilih peran yang akan diberikan (default Anggota) sebelum menyetujui calon anggota.</p>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($pendingRequests as $req)
            <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-900 text-sm">{{ $req->user?->name }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Menunggu Persetujuan</span>
                    </div>
                    <p class="text-xs text-slate-600">{{ $req->user?->email }} • Dikirim {{ $req->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Form Setujui dengan Pilihan Peran -->
                    <form method="POST" action="{{ route('organization.join-requests.approve', $req) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="role" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs bg-white">
                            <option value="anggota" @selected(($req->invitation?->default_role ?? 'anggota') === 'anggota')>Sebagai Anggota</option>
                            <option value="bendahara" @selected(($req->invitation?->default_role ?? '') === 'bendahara')>Sebagai Bendahara</option>
                            <option value="auditor" @selected(($req->invitation?->default_role ?? '') === 'auditor')>Sebagai Auditor / Ketua</option>
                            <option value="admin_organisasi">Sebagai Admin Organisasi</option>
                        </select>
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#16A34A] hover:bg-emerald-700 text-white text-xs font-semibold">
                            ✔ Setujui
                        </button>
                    </form>

                    <!-- Form Tolak -->
                    <form method="POST" action="{{ route('organization.join-requests.reject', $req) }}"
                        onsubmit="return confirm('Tolak permintaan bergabung dari {{ $req->user?->name }}?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="rejection_reason" value="Tidak terdaftar sebagai anggota aktif periode ini.">
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 text-xs font-semibold">
                            ✕ Tolak
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-10 text-center space-y-1">
                <p class="text-base">📭</p>
                <p class="text-sm font-semibold text-slate-700">Belum ada permintaan gabung</p>
                <p class="text-xs text-slate-400">Permintaan dari pendaftar yang menggunakan kode undangan akan tampil di sini.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Riwayat Permintaan yang Sudah Diproses -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">Riwayat Persetujuan Terakhir</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Diproses Oleh</th>
                    <th class="px-5 py-3">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($historyRequests as $hist)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $hist->user?->name }}</td>
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $hist->user?->email }}</td>
                        <td class="px-5 py-3">
                            @if($hist->status === 'approved')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Disetujui</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Ditolak</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $hist->reviewer?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $hist->reviewed_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-xs text-slate-400">Belum ada riwayat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
