@extends('layouts.app')

@section('title', 'Daftar Organisasi Platform')
@section('header', 'Manajemen Organisasi Platform (Super Admin)')

@section('content')
<!-- Catatan Pembatasan Super Admin (PRD 10.20 & Aturan T-04) -->
<div class="rounded-xl bg-purple-50 border border-purple-200 p-4 text-xs text-purple-900 flex items-center justify-between">
    <span>🔒 <strong>Aturan Privasi Tenant (T-04):</strong> Data keuangan organisasi (transaksi, saldo, iuran, dan audit log) tidak dapat diakses dari sini. Super Admin hanya mengelola status aktif organisasi.</span>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <h3 class="text-base font-semibold text-slate-900">Daftar Organisasi Terdaftar ({{ $organizations->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama Organisasi</th>
                    <th class="px-5 py-3">Jenis</th>
                    <th class="px-5 py-3">Tanggal Dibuat</th>
                    <th class="px-5 py-3">Jumlah Anggota Aktif</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($organizations as $org)
                    <tr class="{{ $org->deactivation_requested_at ? 'bg-amber-50/50' : '' }}">
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-slate-900">{{ $org->name }}</div>
                            @if($org->deactivation_requested_at)
                                <div class="mt-1.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 max-w-md">
                                    <div class="font-semibold text-amber-800">
                                        Permintaan Nonaktif ({{ $org->deactivation_requested_at->format('d/m/Y H:i') }})
                                    </div>
                                    <p class="mt-0.5 text-amber-800/90">Alasan: {{ $org->deactivation_reason }}</p>
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $org->type ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $org->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-700 font-medium">{{ $org->memberships_count }} anggota</td>
                        <td class="px-5 py-3.5">
                            <div class="flex flex-col items-start gap-1">
                                @if($org->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Organisasi Aktif</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Organisasi Nonaktif</span>
                                @endif
                                @if($org->deactivation_requested_at)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Mengajukan Nonaktif</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="inline-flex items-center gap-2 justify-end">
                                @if($org->deactivation_requested_at && $org->status === 'active')
                                    <form method="POST" action="{{ route('platform.organizations.reject-deactivation', $org) }}"
                                        onsubmit="return confirm('Tolak permintaan penonaktifan dan biarkan {{ $org->name }} tetap aktif?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                                            Tolak Permintaan
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('platform.organizations.toggle', $org) }}"
                                    onsubmit="return confirm('{{ $org->deactivation_requested_at ? "Setujui permintaan dan nonaktifkan organisasi {$org->name}?" : "Ubah status aktif organisasi {$org->name}?" }}')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $org->status === 'active' ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                        @if($org->deactivation_requested_at && $org->status === 'active')
                                            Setujui &amp; Nonaktifkan
                                        @else
                                            {{ $org->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400">Belum ada organisasi terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
