@extends('layouts.app')

@section('title', 'Audit Log Organisasi')
@section('header', 'Audit Log Organisasi')

@section('content')
<!-- Catatan Hanya-Baca (Aturan T-02 & PRD 10.14) -->
<div class="rounded-xl bg-slate-900 text-white p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
    <div class="flex items-center gap-2.5">
        <span class="text-base">🔒</span>
        <span><strong>Log Bersifat Hanya-Baca (Aturan T-02):</strong> Seluruh aktivitas keuangan dan organisasi terekam permanen serta tidak dapat diubah maupun dihapus oleh peran mana pun.</span>
    </div>
    <span class="px-2.5 py-1 rounded bg-white/10 font-mono text-[11px]">Tenant Isolated</span>
</div>

<!-- Filter Audit Log -->
<form method="GET" action="{{ route('audit-logs.index') }}" class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm grid sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
    <div>
        <label class="block font-medium text-slate-600 mb-1">Pengguna</label>
        <select name="user_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white">
            <option value="">Semua Pengguna</option>
            @foreach($members as $m)
                <option value="{{ $m->user_id }}" @selected(request('user_id') == $m->user_id)>{{ $m->user?->name }} ({{ $m->role }})</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block font-medium text-slate-600 mb-1">Aksi</label>
        <select name="action" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white">
            <option value="">Semua Aksi</option>
            @foreach($actions as $act)
                <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block font-medium text-slate-600 mb-1">Dari Tanggal</label>
        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
    </div>

    <div>
        <label class="block font-medium text-slate-600 mb-1">Sampai Tanggal</label>
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
    </div>

    <div class="flex items-end gap-2">
        <button type="submit" class="w-full py-1.5 px-3 rounded-lg bg-[#0F766E] text-white font-semibold">Terapkan Filter</button>
    </div>
</form>

<!-- Tabel Audit Log -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Waktu</th>
                    <th class="px-5 py-3">Pengguna</th>
                    <th class="px-5 py-3">Aksi</th>
                    <th class="px-5 py-3">Entitas</th>
                    <th class="px-5 py-3">IP Address</th>
                    <th class="px-5 py-3 text-right">Perubahan Nilai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-5 py-3.5 text-xs text-slate-600 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-5 py-3.5 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-[#0F766E] border border-teal-200">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $log->entity_type }} {{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                        <td class="px-5 py-3.5 text-xs font-mono text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-right">
                            @if($log->old_values || $log->new_values)
                                <button type="button" onclick="document.getElementById('diff_{{ $log->id }}').classList.toggle('hidden')"
                                    class="text-xs font-semibold text-[#0F766E] hover:underline">
                                    🔍 Lihat Perubahan
                                </button>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @if($log->old_values || $log->new_values)
                        <tr id="diff_{{ $log->id }}" class="hidden bg-slate-50">
                            <td colspan="6" class="px-6 py-4">
                                <div class="grid sm:grid-cols-2 gap-4 text-xs">
                                    <div class="p-3 rounded-lg bg-red-50/60 border border-red-200">
                                        <p class="font-bold text-red-800 mb-1.5">Nilai Lama (Sebelum):</p>
                                        @if($log->old_values)
                                            @foreach($log->old_values as $k => $v)
                                                <div><span class="font-semibold capitalize">{{ str_replace('_', ' ', $k) }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-slate-400 italic">Tidak ada nilai sebelumnya (Data Baru)</span>
                                        @endif
                                    </div>
                                    <div class="p-3 rounded-lg bg-emerald-50/60 border border-emerald-200">
                                        <p class="font-bold text-emerald-800 mb-1.5">Nilai Baru (Sesudah):</p>
                                        @if($log->new_values)
                                            @foreach($log->new_values as $k => $v)
                                                <div><span class="font-semibold capitalize">{{ str_replace('_', ' ', $k) }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-slate-400 italic">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400">Belum ada data audit log yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
