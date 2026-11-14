@extends('layouts.app')

@section('title', 'Dashboard ' . ($membership->role === 'bendahara' ? 'Bendahara' : 'Auditor'))
@section('header', 'Dashboard ' . ($membership->role === 'bendahara' ? 'Bendahara' : 'Auditor / Ketua'))

@section('content')
@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $periodLabel = ($months[$month] ?? $month) . ' ' . $year;
@endphp

<!-- Baris Atas: Sapaan, Filter Bulan/Tahun & Tombol Cepat Tambah Transaksi -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-slate-900">Ringkasan Kas {{ $organization->name }}</h2>
        <p class="text-sm text-slate-500">Pemantauan arus kas periode <strong>{{ $periodLabel }}</strong> dengan jejak audit transparan.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 shadow-xs text-xs">
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

        @if($membership->role === 'bendahara')
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold px-4 py-2.5 text-sm shadow-sm transition">
                <span>＋</span> Tambah Transaksi
            </a>
        @endif
    </div>
</div>

<!-- 4 Kartu Ringkasan (PRD 10.6) -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase">
            <span>Saldo Kas Aktif</span>
            <span class="text-lg">💰</span>
        </div>
        <p class="text-2xl font-bold text-slate-900 mt-2">Rp {{ number_format($balance, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1">Total kas bersih organisasi</p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase">
            <span>Pemasukan Periode Ini</span>
            <span class="text-lg">📈</span>
        </div>
        <p class="text-2xl font-bold text-[#16A34A] mt-2">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1">Periode {{ $periodLabel }}</p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase">
            <span>Pengeluaran Periode Ini</span>
            <span class="text-lg">📉</span>
        </div>
        <p class="text-2xl font-bold text-[#DC2626] mt-2">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1">Periode {{ $periodLabel }}</p>
    </div>

    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase">
            <span>Iuran Belum Lunas</span>
            <span class="text-lg">🧾</span>
        </div>
        <p class="text-2xl font-bold text-[#D97706] mt-2">Rp {{ number_format($unpaidDuesAmount, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $pendingDuesCount }} menunggu verifikasi</p>
    </div>
</div>

<!-- Grafik Garis (6 Bulan) & Grafik Donat (Pengeluaran per Kategori) -->
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-slate-900">Arus Kas 6 Bulan Terakhir</h3>
            <span class="text-xs text-slate-500">Pemasukan vs Pengeluaran</span>
        </div>
        <div class="h-64">
            <canvas id="cashflowChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900 mb-1">Pengeluaran per Kategori</h3>
            <p class="text-xs text-slate-500 mb-4">Proporsi dana keluar periode <strong>{{ $periodLabel }}</strong></p>
        </div>
        @if(count($donutValues) > 0)
            <div class="h-56 flex items-center justify-center">
                <canvas id="categoryDonutChart"></canvas>
            </div>
        @else
            <div class="h-56 flex flex-col items-center justify-center text-center text-xs text-slate-400 space-y-2">
                <span class="text-2xl">🍩</span>
                <p>Belum ada pengeluaran pada <strong>{{ $periodLabel }}</strong>.</p>
                @if($membership->role === 'bendahara')
                    <a href="{{ route('transactions.create') }}" class="text-[#0F766E] font-semibold hover:underline">+ Tambah Pengeluaran</a>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Tabel 5 Transaksi Terbaru & Panel Peringatan Anggaran / Verifikasi Iuran -->
<div class="grid lg:grid-cols-3 gap-6">
    <!-- 5 Transaksi Terbaru -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900">5 Transaksi Terbaru</h3>
            <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Lihat Semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Keterangan</th>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($latestTransactions as $tx)
                        <tr class="hover:bg-slate-50/80 {{ $tx->status === 'cancelled' ? 'line-through text-slate-400' : '' }}">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-600">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('transactions.show', $tx) }}" class="font-medium text-slate-900 hover:text-[#0F766E]">
                                    {{ $tx->description }}
                                </a>
                                @if($tx->is_flagged)
                                    <span class="ml-1 px-2 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-800 font-semibold">⚠️ Ditandai</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">{{ $tx->category?->name ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @if($tx->status === 'cancelled')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Dibatalkan</span>
                                @elseif($tx->type === 'income')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Pemasukan</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Pengeluaran</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold whitespace-nowrap {{ $tx->type === 'income' ? 'text-[#16A34A]' : 'text-[#DC2626]' }}">
                                Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400 text-xs">Belum ada transaksi tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel Kecil: Anggaran Hampir Habis & Iuran Menunggu Verifikasi -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Peringatan Anggaran Kegiatan</h3>
                <a href="{{ route('budgets.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Kelola</a>
            </div>
            @forelse($budgets as $budget)
                @php $pct = $budget->usagePercentage(); @endphp
                <div class="p-3 rounded-lg bg-amber-50/70 border border-amber-200 space-y-1.5">
                    <div class="flex justify-between text-xs font-semibold">
                        <span class="text-slate-800">{{ $budget->activity_name }}</span>
                        <span class="{{ $pct > 100 ? 'text-red-600' : 'text-amber-700' }}">{{ $pct }}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full {{ $pct > 100 ? 'bg-[#DC2626]' : 'bg-[#D97706]' }}" style="width: {{ min($pct, 100) }}%"></div>
                    </div>
                    <p class="text-[11px] text-slate-600">
                        Terpakai Rp {{ number_format($budget->usedAmount(), 0, ',', '.') }} dari Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}
                    </p>
                </div>
            @empty
                <p class="text-xs text-slate-500 py-3">Semua anggaran kegiatan masih dalam batas aman (&lt; 70%).</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Iuran Menunggu Verifikasi</h3>
                <a href="{{ route('dues.index') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">Buka Antrean</a>
            </div>
            @forelse($pendingDues as $due)
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $due->user?->name }}</p>
                        <p class="text-slate-500">{{ $due->period }} • Rp {{ number_format($due->amount, 0, ',', '.') }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold">Pending</span>
                </div>
            @empty
                <p class="text-xs text-slate-500 py-2">Tidak ada antrean bukti iuran yang menunggu verifikasi.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctxLine = document.getElementById('cashflowChart');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: @json($chartMonths),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: @json($chartIncome),
                        borderColor: '#16A34A',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($chartExpense),
                        borderColor: '#DC2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    const ctxDonut = document.getElementById('categoryDonutChart');
    if (ctxDonut) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: @json($donutLabels),
                datasets: [{
                    data: @json($donutValues),
                    backgroundColor: ['#0F766E', '#D97706', '#2563EB', '#DC2626', '#7C3AED', '#059669']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
@endsection
