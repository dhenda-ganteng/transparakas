@extends('layouts.app')

@section('title', 'Dashboard Admin Organisasi')
@section('header', 'Dashboard Admin Organisasi')

@section('content')
<!-- Catatan Hak Akses Admin Organisasi (PRD Bagian 7: tanpa angka keuangan rinci) -->
<div class="rounded-xl bg-teal-50 border border-teal-200 p-4 flex items-center justify-between text-xs text-[#0F766E]">
    <span>🛡️ <strong>Mode Admin Organisasi:</strong> Anda mengelola anggota, peran, kode undangan, persetujuan gabung, dan memantau Audit Log (tanpa intervensi angka keuangan).</span>
    <a href="{{ route('organization.settings') }}" class="font-semibold underline ml-2">Pengaturan Organisasi</a>
</div>

<div class="grid sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Anggota Aktif</span>
        <p class="text-2xl font-bold text-slate-900 mt-2">{{ $memberCount }} Orang</p>
        <p class="text-xs text-slate-500 mt-1">{{ $inactiveMemberCount }} anggota nonaktif</p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Permintaan Gabung Menunggu</span>
        <p class="text-2xl font-bold text-[#D97706] mt-2">{{ $pendingJoinCount }} Permintaan</p>
        <a href="{{ route('organization.join-requests') }}" class="text-xs font-semibold text-[#0F766E] hover:underline mt-1 inline-block">
            Tinjau Permintaan →
        </a>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Kode Undangan Aktif</span>
        @if($activeInvitation && $activeInvitation->isValid())
            <p class="text-2xl font-mono font-bold text-[#0F766E] mt-2 tracking-wider">{{ $activeInvitation->code }}</p>
            <p class="text-xs text-slate-500 mt-1">Berlaku s/d {{ $activeInvitation->expires_at->format('d/m/Y H:i') }}</p>
        @else
            <p class="text-base font-semibold text-red-600 mt-2">Tidak Ada Kode Aktif</p>
            <a href="{{ route('organization.settings') }}" class="text-xs font-semibold text-[#0F766E] hover:underline mt-1 inline-block">Buat Kode Baru →</a>
        @endif
    </div>
</div>

<!-- Aktivitas Terbaru dari Audit Log -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">Aktivitas Audit Log Terbaru</h3>
        <a href="{{ route('audit-logs.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Buka Audit Log Lengkap →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Waktu</th>
                    <th class="px-5 py-3">Pengguna</th>
                    <th class="px-5 py-3">Aksi</th>
                    <th class="px-5 py-3">Entitas</th>
                    <th class="px-5 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentLogs as $log)
                    <tr>
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">{{ $log->action }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $log->entity_type }} {{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                        <td class="px-5 py-3 text-xs font-mono text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-xs text-slate-400">Belum ada aktivitas terekam.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
