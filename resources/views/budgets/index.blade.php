@extends('layouts.app')

@section('title', 'Anggaran Kegiatan')
@section('header', 'Anggaran & Alokasi Dana Kegiatan')

@section('content')
@php
    $membership = auth()->user()->membership()->with('organization')->first();
    $role = $membership?->role;
    $canViewTxDetail = in_array($role, ['bendahara', 'auditor'], true) || ($role === 'anggota' && $membership?->organization?->visibility_policy === 'detail');
@endphp

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Daftar Kartu Anggaran Kegiatan + Rincian Alokasi Pengeluarannya -->
    <div class="{{ $role === 'bendahara' ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Pos Anggaran & Alokasi Acara</h2>
                <p class="text-xs text-slate-500">Klik kartu atau tombol rincian untuk melihat daftar pengeluaran lengkap pada masing-masing kegiatan.</p>
            </div>
        </div>

        <div class="grid {{ $role === 'bendahara' ? 'sm:grid-cols-2' : 'sm:grid-cols-2 lg:grid-cols-3' }} gap-4">
            @forelse($budgets as $budget)
                @php
                    $used = $budget->usedAmount();
                    $remaining = $budget->remainingAmount();
                    $pct = $budget->usagePercentage();

                    if ($pct > 100) {
                        $barColor = 'bg-[#DC2626]';
                        $badgeColor = 'bg-red-100 text-[#DC2626]';
                        $statusLabel = 'Melebihi Anggaran';
                    } elseif ($pct >= 70) {
                        $barColor = 'bg-[#D97706]';
                        $badgeColor = 'bg-amber-100 text-[#D97706]';
                        $statusLabel = 'Hampir Habis';
                    } else {
                        $barColor = 'bg-[#16A34A]';
                        $badgeColor = 'bg-emerald-100 text-[#16A34A]';
                        $statusLabel = 'Aman';
                    }
                @endphp
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between space-y-4 hover:border-[#0F766E]/50 transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <button type="button" onclick="openBudgetModal({{ $budget->id }})" class="text-left font-bold text-slate-900 hover:text-[#0F766E] transition">
                                {{ $budget->activity_name }}
                            </button>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $badgeColor }}">
                                {{ $statusLabel }} ({{ $pct }}%)
                            </span>
                        </div>
                        @if($budget->description)
                            <p class="text-xs text-slate-500">{{ $budget->description }}</p>
                        @endif

                        <!-- Progress Bar & Ringkasan Nominal -->
                        <div class="space-y-2 pt-1">
                            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $barColor }} transition-all" style="width: {{ min($pct, 100) }}%"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs pt-1">
                                <div>
                                    <span class="text-slate-400 block">Anggaran</span>
                                    <span class="font-semibold text-slate-800">Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Terpakai</span>
                                    <span class="font-semibold text-[#DC2626]">Rp {{ number_format($used, 0, ',', '.') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Sisa</span>
                                    <span class="font-semibold {{ $remaining < 0 ? 'text-red-600' : 'text-[#16A34A]' }}">
                                        Rp {{ number_format($remaining, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Alokasi Dana Kegiatan -->
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Rincian Alokasi ({{ $budget->transactions->count() }} pos)
                            </span>
                            @if($role === 'bendahara')
                                <a href="{{ route('transactions.create') }}" class="text-[11px] font-semibold text-[#0F766E] hover:underline">
                                    + Catat Pengeluaran
                                </a>
                            @endif
                        </div>

                        @if($budget->transactions->isNotEmpty())
                            <ul class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                                @foreach($budget->transactions as $item)
                                    <li>
                                        <button type="button" onclick="openBudgetModal({{ $budget->id }})"
                                            class="w-full text-left p-2 rounded-lg bg-slate-50 hover:bg-teal-50/60 border border-slate-100 hover:border-teal-200 text-xs flex items-start justify-between gap-2 transition">
                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-800 truncate">{{ $item->description }}</p>
                                                <p class="text-[11px] text-slate-500">
                                                    {{ $item->transaction_date->format('d/m/Y') }} • {{ $item->category?->name ?? '-' }}
                                                </p>
                                            </div>
                                            <span class="font-semibold text-[#DC2626] whitespace-nowrap">
                                                Rp {{ number_format($item->amount, 0, ',', '.') }}
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-400 italic py-1">Belum ada dana yang dicairkan untuk kegiatan ini.</p>
                        @endif

                        <button type="button" onclick="openBudgetModal({{ $budget->id }})"
                            class="w-full mt-1 py-1.5 px-3 rounded-lg bg-slate-100 hover:bg-[#CCFBF1] text-slate-700 hover:text-[#0F766E] text-xs font-semibold transition">
                            🔍 Lihat Rincian Pengeluaran
                        </button>
                    </div>
                </div>

                <!-- Modal Rincian Pengeluaran Lengkap untuk Kegiatan Ini -->
                <div id="budget_modal_{{ $budget->id }}" onclick="if(event.target === this) closeBudgetModal({{ $budget->id }})"
                    class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-xl border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#0F766E]">Rincian Pengeluaran Kegiatan</span>
                                <h3 class="text-lg font-bold text-slate-900">{{ $budget->activity_name }}</h3>
                                @if($budget->description)
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $budget->description }}</p>
                                @endif
                            </div>
                            <button type="button" onclick="closeBudgetModal({{ $budget->id }})" class="text-slate-400 hover:text-slate-700 text-lg leading-none p-1">✕</button>
                        </div>

                        <!-- Ringkasan Anggaran di Dalam Modal -->
                        <div class="grid grid-cols-3 gap-3 p-4 bg-slate-50 border-b border-slate-100 text-xs">
                            <div class="bg-white p-3 rounded-xl border border-slate-200">
                                <span class="text-slate-500 block">Batas Anggaran</span>
                                <span class="text-sm font-bold text-slate-900">Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200">
                                <span class="text-slate-500 block">Total Terpakai ({{ $pct }}%)</span>
                                <span class="text-sm font-bold text-[#DC2626]">Rp {{ number_format($used, 0, ',', '.') }}</span>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200">
                                <span class="text-slate-500 block">Sisa Anggaran</span>
                                <span class="text-sm font-bold {{ $remaining < 0 ? 'text-red-600' : 'text-[#16A34A]' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Rincian Pengeluaran -->
                        <div class="flex-1 overflow-y-auto p-5">
                            @if($budget->transactions->isNotEmpty())
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-50 text-slate-500 uppercase border-b border-slate-200">
                                        <tr>
                                            <th class="px-3 py-2.5">Tanggal</th>
                                            <th class="px-3 py-2.5">Keterangan Pengeluaran</th>
                                            <th class="px-3 py-2.5">Kategori</th>
                                            <th class="px-3 py-2.5 text-right">Nominal</th>
                                            @if($canViewTxDetail)
                                                <th class="px-3 py-2.5 text-right">Nota</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($budget->transactions as $item)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ $item->transaction_date->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3">
                                                    <p class="font-semibold text-slate-900">{{ $item->description }}</p>
                                                    <p class="text-[11px] text-slate-400">Dicatat: {{ $item->creator?->name ?? '-' }}</p>
                                                </td>
                                                <td class="px-3 py-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                                                <td class="px-3 py-3 text-right font-bold text-[#DC2626] whitespace-nowrap">
                                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                                </td>
                                                @if($canViewTxDetail)
                                                    <td class="px-3 py-3 text-right whitespace-nowrap">
                                                        <a href="{{ route('transactions.show', $item) }}" class="text-[#0F766E] font-semibold hover:underline">Detail</a>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="py-8 text-center text-xs text-slate-400">
                                    Belum ada pengeluaran yang dialokasikan untuk kegiatan ini.
                                </div>
                            @endif
                        </div>

                        <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                            @if($role === 'bendahara')
                                <a href="{{ route('transactions.create') }}" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white text-xs font-semibold">
                                    + Catat Pengeluaran Baru
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button type="button" onclick="closeBudgetModal({{ $budget->id }})" class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-xl p-8 border border-slate-200 text-center text-xs text-slate-400">
                    Belum ada pos anggaran kegiatan yang dibuat oleh Bendahara.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Form Tambah Anggaran Kegiatan (Khusus Bendahara) -->
    @if($role === 'bendahara')
    <div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4 sticky top-24">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Setting Anggaran Acara / Kas</h3>
                <p class="text-xs text-slate-500">Tetapkan plafon anggaran untuk acara, proker, atau alokasi kas organisasi agar dapat dipantau anggota.</p>
            </div>
            <form method="POST" action="{{ route('budgets.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Kegiatan / Acara</label>
                    <input type="text" name="activity_name" required placeholder="Contoh: Seminar Nasional Teknologi 2026"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Batas Anggaran (Rp)</label>
                    <input type="number" name="limit_amount" required min="1000" placeholder="5000000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 font-semibold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Rencana Alokasi / Keterangan</label>
                    <textarea name="description" rows="3" placeholder="Contoh: Untuk sewa tempat, konsumsi peserta, sertifikat, dan dokumentasi..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </div>
                <button type="submit" class="w-full rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-2.5 shadow-sm">
                    ＋ Simpan Anggaran Kegiatan
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
function openBudgetModal(id) {
    document.getElementById('budget_modal_' + id)?.classList.remove('hidden');
}
function closeBudgetModal(id) {
    document.getElementById('budget_modal_' + id)?.classList.add('hidden');
}
</script>
@endsection
