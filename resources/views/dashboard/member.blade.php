@extends('layouts.app')

@section('title', 'Dashboard Anggota')
@section('header', 'Dashboard Anggota')

@section('content')
@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $periodLabel = ($months[$month] ?? $month) . ' ' . $year;
@endphp

<!-- Filter Bulan & Tahun untuk Anggota -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Ringkasan Transparansi Kas</h2>
        <p class="text-xs text-slate-500">Menampilkan ringkasan arus kas periode <strong>{{ $periodLabel }}</strong>.</p>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 shadow-xs text-xs w-fit">
        <span class="text-slate-500 font-medium">Periode:</span>
        <select name="month" onchange="this.form.submit()" class="rounded border border-slate-200 px-2 py-1 bg-slate-50 font-semibold text-slate-800 focus:outline-none">
            @foreach($months as $num => $name)
                <option value="{{ $num }}" @selected($month === $num)>{{ $name }}</option>
            @endforeach
        </select>
        <select name="year" onchange="this.form.submit()" class="rounded border border-slate-200 px-2 py-1 bg-slate-50 font-semibold text-slate-800 focus:outline-none">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<!-- Kartu Ringkasan Anggota (PRD 10.11) -->
<div class="grid sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Saldo Kas Organisasi</span>
        <p class="text-2xl font-bold text-[#0F766E] mt-2">Rp {{ number_format($balance, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $organization->name }}</p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Status Iuran Saya</span>
        <div class="mt-2 flex items-center gap-2">
            @if($unpaidCount === 0)
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-[#16A34A]">✔ Semua Lunas</span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-[#D97706]">{{ $unpaidCount }} Tagihan Belum Lunas</span>
            @endif
        </div>
        <p class="text-xs text-slate-500 mt-2">
            <a href="{{ route('dues.my') }}" class="text-[#0F766E] font-semibold hover:underline">Lihat Riwayat Iuran →</a>
        </p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <span class="text-xs font-semibold uppercase text-slate-500">Ringkasan Kas ({{ $periodLabel }})</span>
        <div class="mt-2 space-y-1 text-xs">
            <div class="flex justify-between">
                <span class="text-slate-600">Pemasukan:</span>
                <span class="font-semibold text-[#16A34A]">+ Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-600">Pengeluaran:</span>
                <span class="font-semibold text-[#DC2626]">- Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Tagihan Terdekat -->
@if($upcomingDue)
    <div class="bg-white rounded-xl p-6 border border-amber-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Tagihan Iuran Aktif</span>
            <h3 class="text-lg font-bold text-slate-900">Iuran Periode {{ $upcomingDue->period }}</h3>
            <p class="text-sm text-slate-600">
                Nominal: <strong class="text-slate-900">Rp {{ number_format($upcomingDue->amount, 0, ',', '.') }}</strong>
                @if($upcomingDue->due_date)
                    • Jatuh tempo: {{ $upcomingDue->due_date->format('d/m/Y') }}
                @endif
            </p>
            @if($upcomingDue->status === 'rejected' && $upcomingDue->rejection_reason)
                <p class="text-xs text-red-600 font-medium">Alasan ditolak sebelumnya: "{{ $upcomingDue->rejection_reason }}"</p>
            @endif
        </div>
        <a href="{{ route('dues.my') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold px-5 py-2.5 text-sm shadow-sm transition">
            📤 Unggah Bukti Bayar
        </a>
    </div>
@endif

<!-- Transparansi Kas untuk Anggota (Mengikuti Kebijakan S-05) -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Transparansi Kas Organisasi</h3>
            <p class="text-xs text-slate-500">
                Kebijakan keterlihatan saat ini:
                <strong>{{ $organization->visibility_policy === 'detail' ? 'Detail Transaksi Terbuka untuk Anggota' : 'Ringkasan Publik Saja' }}</strong>
            </p>
        </div>
        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Lihat Ringkasan Bulanan →</a>
    </div>

    @if($organization->visibility_policy === 'detail')
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Keterangan</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $tx)
                        <tr>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $tx->description }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $tx->category?->name }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $tx->type === 'income' ? 'text-[#16A34A]' : 'text-[#DC2626]' }}">
                                Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-xs text-slate-400">Belum ada transaksi tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="p-6 text-center space-y-2">
            <p class="text-sm text-slate-600">
                Sesuai kebijakan organisasi, anggota dapat memantau <strong>Saldo Kas</strong> dan <strong>Ringkasan Rekap Kategori Bulanan</strong>.
            </p>
            <a href="{{ route('reports.index') }}" class="inline-block text-xs font-semibold text-[#0F766E] hover:underline">
                Buka Rekap Ringkasan Kas Publik →
            </a>
        </div>
    @endif
</div>

<!-- Rincian Alokasi Anggaran Kegiatan / Acara -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Alokasi Anggaran Kegiatan & Acara</h3>
            <p class="text-xs text-slate-500">Lihat kegiatan apa saja yang dibiayai kas organisasi dan rincian pengeluarannya.</p>
        </div>
        <a href="{{ route('budgets.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Lihat Semua Anggaran →</a>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        @forelse($budgets as $budget)
            @php
                $used = $budget->usedAmount();
                $pct = $budget->usagePercentage();
                $barColor = $pct > 100 ? 'bg-[#DC2626]' : ($pct >= 70 ? 'bg-[#D97706]' : 'bg-[#16A34A]');
            @endphp
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="font-bold text-slate-900 text-sm">{{ $budget->activity_name }}</h4>
                    <span class="text-xs font-semibold text-slate-600">{{ $pct }}% terpakai</span>
                </div>

                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }}" style="width: {{ min($pct, 100) }}%"></div>
                </div>

                <div class="flex justify-between text-xs text-slate-600">
                    <span>Terpakai: <strong class="text-[#DC2626]">Rp {{ number_format($used, 0, ',', '.') }}</strong></span>
                    <span>Batas: <strong>Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}</strong></span>
                </div>

                @if($budget->transactions->isNotEmpty())
                    <div class="pt-2 border-t border-slate-200 space-y-1">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">Alokasi ke:</p>
                        @foreach($budget->transactions->take(3) as $item)
                            <div class="flex justify-between text-xs text-slate-700">
                                <span class="truncate pr-2">• {{ $item->description }}</span>
                                <span class="font-semibold text-[#DC2626] whitespace-nowrap">Rp {{ number_format($item->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[11px] text-slate-400 italic">Belum ada pengeluaran pada kegiatan ini.</p>
                @endif
            </div>
        @empty
            <p class="sm:col-span-2 text-xs text-slate-400 text-center py-4">Belum ada anggaran kegiatan yang ditetapkan.</p>
        @endforelse
    </div>
</div>
@endsection
