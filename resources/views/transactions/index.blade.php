@extends('layouts.app')

@section('title', 'Daftar Transaksi Keuangan')
@section('header', 'Daftar Transaksi Keuangan')

@section('content')
@php
    $role = auth()->user()->membership?->role;
@endphp

<!-- Baris Atas: Judul & Tombol Aksi -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Semua Transaksi Keuangan</h2>
        <p class="text-xs text-slate-500">Mencakup pemasukan kas/iuran, pengeluaran rutin, dan pengeluaran anggaran kegiatan. Transaksi yang dibatalkan tetap tampil dicoret (Aturan T-01).</p>
    </div>
    <div class="flex items-center gap-2.5">
        <a href="{{ route('transactions.export.csv') }}" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
            📥 Ekspor CSV
        </a>
        @if($role === 'bendahara')
            <a href="{{ route('transactions.create') }}" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold text-xs shadow-sm transition">
                ＋ Tambah Transaksi
            </a>
        @endif
    </div>
</div>

<!-- Bar Filter (PRD 10.7) -->
<form method="GET" action="{{ route('transactions.index') }}" class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm grid sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
    <div>
        <label class="block font-medium text-slate-600 mb-1">Dari Tanggal</label>
        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
    </div>
    <div>
        <label class="block font-medium text-slate-600 mb-1">Sampai Tanggal</label>
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
    </div>
    <div>
        <label class="block font-medium text-slate-600 mb-1">Tipe</label>
        <select name="type" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white">
            <option value="">Semua Tipe</option>
            <option value="income" @selected(request('type') === 'income')>Pemasukan</option>
            <option value="expense" @selected(request('type') === 'expense')>Pengeluaran</option>
        </select>
    </div>
    <div>
        <label class="block font-medium text-slate-600 mb-1">Kategori</label>
        <select name="category_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white">
            <option value="">Semua Kategori</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-medium text-slate-600 mb-1">Status</label>
        <select name="status" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 bg-white">
            <option value="">Semua Status</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
        </select>
    </div>
    <div>
        <label class="block font-medium text-slate-600 mb-1">Cari Keterangan</label>
        <div class="flex gap-1.5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5">
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#0F766E] text-white font-semibold">Cari</button>
        </div>
    </div>
</form>

<!-- Tabel Data Transaksi -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Keterangan</th>
                    <th class="px-5 py-3">Kategori</th>
                    <th class="px-5 py-3">Tipe</th>
                    <th class="px-5 py-3 text-right">Nominal</th>
                    <th class="px-5 py-3">Dibuat Oleh</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50/80 {{ $tx->status === 'cancelled' ? 'bg-slate-50/50 text-slate-400' : '' }}">
                        <td class="px-5 py-3.5 whitespace-nowrap text-xs">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5">
                            <span class="{{ $tx->status === 'cancelled' ? 'line-through' : 'font-medium text-slate-900' }}">
                                {{ $tx->description }}
                            </span>
                            @if($tx->budget)
                                <span class="block text-[11px] text-[#0F766E] font-medium mt-0.5">🎯 Acara: {{ $tx->budget->activity_name }}</span>
                            @endif
                            @if($tx->is_flagged)
                                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">⚠️ Ditandai Auditor</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs">{{ $tx->category?->name ?? '-' }}</td>
                        <td class="px-5 py-3.5">
                            @if($tx->type === 'income')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Pemasukan</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Pengeluaran</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right font-semibold whitespace-nowrap {{ $tx->status === 'cancelled' ? 'line-through text-slate-400' : ($tx->type === 'income' ? 'text-[#16A34A]' : 'text-[#DC2626]') }}">
                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5 text-xs">{{ $tx->creator?->name ?? '-' }}</td>
                        <td class="px-5 py-3.5">
                            @if($tx->status === 'cancelled')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Dibatalkan</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2 text-xs font-semibold">
                            <a href="{{ route('transactions.show', $tx) }}" class="text-[#0F766E] hover:underline">Detail</a>
                            @if($role === 'bendahara' && $tx->status === 'active')
                                <a href="{{ route('transactions.edit', $tx) }}" class="text-blue-600 hover:underline">Ubah</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-slate-400 text-xs">
                            Belum ada data transaksi yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
