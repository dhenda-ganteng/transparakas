@extends('layouts.app')

@section('title', 'Laporan & Ringkasan Kas')
@section('header', 'Laporan & Ringkasan Transparansi Kas')

@section('content')
@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $role = $membership->role;
@endphp

<!-- Filter Periode & Tombol Ekspor (PRD 10.10) -->
<div class="no-print bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-3 text-xs">
        <div>
            <label class="font-semibold text-slate-600 mr-1">Bulan:</label>
            <select name="month" class="rounded-lg border border-slate-300 px-3 py-2 bg-white">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected($month === $num)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="font-semibold text-slate-600 mr-1">Tahun:</label>
            <select name="year" class="rounded-lg border border-slate-300 px-3 py-2 bg-white">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-[#0F766E] text-white font-semibold">
            Tampilkan Periode
        </button>
    </form>

    @if(in_array($role, ['bendahara', 'auditor']))
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold text-xs">
                🖨️ Ekspor PDF / Cetak
            </button>
            <a href="{{ route('reports.export.csv', ['month' => $month, 'year' => $year]) }}"
                class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs">
                📥 Ekspor CSV
            </a>
        </div>
    @endif
</div>

<!-- Pratinjau Lembar Laporan Resmi dengan Kop Organisasi -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-8">
    <!-- Kop Surat Laporan -->
    <div class="border-b-2 border-slate-800 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-[#0F766E]">Laporan Keuangan & Transparansi Kas</p>
            <h2 class="text-2xl font-bold text-slate-900">{{ $organization->name }}</h2>
            <p class="text-xs text-slate-500">Periode Fokus: <strong>{{ $months[$month] ?? $month }} {{ $year }}</strong></p>
        </div>
        <div class="text-xs text-slate-500 sm:text-right">
            <p>Sistem: <strong>TransparaKas</strong></p>
            <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- 3 Kartu Ringkasan Periode -->
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200">
            <span class="text-xs font-semibold text-emerald-800 uppercase">Pemasukan ({{ $months[$month] }})</span>
            <p class="text-xl font-bold text-[#16A34A] mt-1">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</p>
        </div>
        <div class="p-4 rounded-xl bg-red-50/60 border border-red-200">
            <span class="text-xs font-semibold text-red-800 uppercase">Pengeluaran ({{ $months[$month] }})</span>
            <p class="text-xl font-bold text-[#DC2626] mt-1">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
        </div>
        <div class="p-4 rounded-xl bg-teal-50/60 border border-teal-200">
            <span class="text-xs font-semibold text-teal-900 uppercase">Saldo Kas Akhir Organisasi</span>
            <p class="text-xl font-bold text-[#0F766E] mt-1">Rp {{ number_format($currentBalance, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Bagian 1: Rekapitulasi Per Kategori Bulan Terpilih -->
    <div>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3">1. Rincian Sumber Pemasukan & Pengeluaran ({{ $months[$month] }} {{ $year }})</h3>
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Kategori</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3 text-right">Total Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categoryRecap as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-5 py-3">
                                @if($item->type === 'income')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Pemasukan</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Pengeluaran</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $item->type === 'income' ? 'text-[#16A34A]' : 'text-[#DC2626]' }}">
                                Rp {{ number_format($item->period_total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-xs text-slate-400">
                                Tidak ada transaksi aktif pada periode {{ $months[$month] }} {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-sm">
                    <tr>
                        <td colspan="2" class="px-5 py-3 text-slate-800">Selisih Bersih Periode ({{ $months[$month] }} {{ $year }})</td>
                        <td class="px-5 py-3 text-right {{ $netPeriod >= 0 ? 'text-[#16A34A]' : 'text-[#DC2626]' }}">
                            {{ $netPeriod >= 0 ? '+' : '' }} Rp {{ number_format($netPeriod, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Bagian 2: Rincian Uang Kas Masuk & Keluar Tiap Bulan (Tahun Terpilih) -->
    <div class="space-y-4">
        <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">2. Rincian Kas Masuk & Keluar Tiap Bulan (Tahun {{ $year }})</h3>
            <p class="text-xs text-slate-500">Memudahkan seluruh anggota memantau berapa kas yang masuk dan keluar setiap bulan.</p>
        </div>

        <div class="h-60 no-print">
            <canvas id="monthlyReportChart"></canvas>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-2.5">Bulan</th>
                        <th class="px-5 py-2.5 text-right">Kas Masuk</th>
                        <th class="px-5 py-2.5 text-right">Kas Keluar</th>
                        <th class="px-5 py-2.5 text-right">Selisih Bulan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthlyBreakdown as $row)
                        <tr class="{{ $row['month_num'] === $month ? 'bg-teal-50/50 font-semibold' : '' }}">
                            <td class="px-5 py-2.5 text-slate-800">
                                {{ $months[$row['month_num']] }}
                                @if($row['month_num'] === $month)
                                    <span class="ml-1 text-[10px] px-2 py-0.5 rounded-full bg-[#CCFBF1] text-[#0F766E]">Dipilih</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right text-[#16A34A]">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5 text-right text-[#DC2626]">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5 text-right {{ $row['net'] >= 0 ? 'text-slate-800' : 'text-[#DC2626]' }}">
                                {{ $row['net'] >= 0 ? '+' : '' }} Rp {{ number_format($row['net'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bagian 3: Rincian Alokasi Anggaran Kegiatan / Acara -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">3. Rincian Alokasi Anggaran Kegiatan & Acara</h3>
                <p class="text-xs text-slate-500">Menampilkan ke mana saja dana kas organisasi dialokasikan per kegiatan.</p>
            </div>
            <a href="{{ route('budgets.index') }}" class="no-print text-xs font-semibold text-[#0F766E] hover:underline">Buka Halaman Anggaran →</a>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            @forelse($budgetAllocations as $b)
                @php
                    $used = $b->usedAmount();
                    $pct = $b->usagePercentage();
                @endphp
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">{{ $b->activity_name }}</h4>
                            <p class="text-xs text-slate-500">
                                Terpakai <strong>Rp {{ number_format($used, 0, ',', '.') }}</strong> dari batas <strong>Rp {{ number_format($b->limit_amount, 0, ',', '.') }}</strong> ({{ $pct }}%)
                            </p>
                        </div>
                    </div>

                    @if($b->transactions->isNotEmpty())
                        <div class="space-y-1.5 border-t border-slate-200 pt-2">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase">Alokasi Pengeluaran:</p>
                            @foreach($b->transactions as $tx)
                                <div class="flex items-center justify-between text-xs bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                    <span class="text-slate-700 truncate pr-2">{{ $tx->description }} <span class="text-slate-400">({{ $tx->transaction_date->format('d/m') }})</span></span>
                                    <span class="font-semibold text-[#DC2626] whitespace-nowrap">Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">Belum ada pengeluaran tercatat untuk acara ini.</p>
                    @endif
                </div>
            @empty
                <div class="sm:col-span-2 p-6 rounded-xl border border-slate-200 text-center text-xs text-slate-400">
                    Belum ada anggaran kegiatan yang dibuat.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('monthlyReportChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_values($months)),
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: @json(array_column($monthlyBreakdown, 'income')),
                        backgroundColor: '#16A34A',
                        borderRadius: 4
                    },
                    {
                        label: 'Kas Keluar',
                        data: @json(array_column($monthlyBreakdown, 'expense')),
                        backgroundColor: '#DC2626',
                        borderRadius: 4
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
});
</script>
@endsection
