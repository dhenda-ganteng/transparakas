@extends('layouts.app')

@section('title', $transaction ? 'Ubah Transaksi' : 'Tambah Transaksi')
@section('header', $transaction ? 'Ubah Transaksi #' . $transaction->id : 'Tambah Transaksi Kas')

@section('content')
@php
    $currentType = old('type', $transaction?->type ?? 'expense');
@endphp

<div class="max-w-2xl mx-auto bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
    <div>
        <h2 class="text-lg font-bold text-slate-900">{{ $transaction ? 'Ubah Data Transaksi' : 'Catat Transaksi Baru' }}</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $transaction ? 'Setiap perubahan nilai akan direkam otomatis (nilai lama vs nilai baru) di Audit Log (Aturan S-02).' : 'Pastikan nominal, kategori, dan bukti nota sesuai dengan transaksi sebenarnya.' }}
        </p>
    </div>

    <form method="POST" action="{{ $transaction ? route('transactions.update', $transaction) : route('transactions.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($transaction)
            @method('PUT')
        @endif

        <input type="hidden" name="type" id="tx_type" value="{{ $currentType }}">

        <!-- Toggle Pemasukan / Pengeluaran (PRD 10.8) -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Transaksi</label>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" id="btn_income" onclick="setTxType('income')" class="py-2.5 px-4 rounded-xl border-2 font-semibold text-sm transition">
                    📈 Pemasukan Kas
                </button>
                <button type="button" id="btn_expense" onclick="setTxType('expense')" class="py-2.5 px-4 rounded-xl border-2 font-semibold text-sm transition">
                    📉 Pengeluaran Kas
                </button>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Transaksi</label>
                <input type="date" name="transaction_date" value="{{ old('transaction_date', $transaction?->transaction_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nominal (Rp)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">Rp</span>
                    <input type="number" name="amount" id="amount_input" step="1" min="1" value="{{ old('amount', $transaction ? (int) $transaction->amount : '') }}" required
                        oninput="checkBudgetWarning()"
                        placeholder="500000"
                        class="w-full rounded-lg border border-slate-300 pl-10 pr-3.5 py-2 text-sm font-semibold focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                </div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                <select name="category_id" id="category_select" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-type="{{ $cat->type }}" @selected(old('category_id', $transaction?->category_id) == $cat->id)>
                            [{{ $cat->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}] {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="budget_wrapper">
                <label class="block text-sm font-medium text-slate-700 mb-1">Pos Anggaran Kegiatan (Opsional)</label>
                <select name="budget_id" id="budget_select" onchange="checkBudgetWarning()" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-[#0F766E] focus:outline-none">
                    <option value="" data-remaining="0">-- Tanpa Pos Anggaran --</option>
                    @foreach($budgets as $b)
                        <option value="{{ $b->id }}" data-remaining="{{ $b->remainingAmount() }}" @selected(old('budget_id', $transaction?->budget_id) == $b->id)>
                            {{ $b->activity_name }} (Sisa: Rp {{ number_format($b->remainingAmount(), 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Peringatan Inline Jika Pengeluaran Melebihi Sisa Anggaran (PRD 10.8) -->
        <div id="budget_warning" class="hidden rounded-xl bg-amber-50 border border-amber-300 p-3.5 text-xs text-amber-800 font-medium">
            ⚠️ <strong>Peringatan Anggaran:</strong> Pengeluaran ini akan membuat pos anggaran kegiatan melebihi batas yang ditetapkan!
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan Transaksi</label>
            <textarea name="description" rows="3" required placeholder="Contoh: Pembelian konsumsi rapat koordinasi pengurus"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#0F766E] focus:outline-none">{{ old('description', $transaction?->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Unggah Bukti / Nota (Opsional)</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3.5 py-2 text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-[#0F766E] file:text-white file:font-semibold">
            <p class="text-[11px] text-slate-500 mt-1">Format diizinkan: JPG, PNG, PDF (Maksimal 2 MB).</p>
            @if($transaction?->receipt_path)
                <p class="text-xs text-[#0F766E] mt-1 font-medium">
                    📎 Bukti saat ini sudah terlampir (<a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="underline">Lihat Bukti</a>). Unggah file baru jika ingin menggantinya.
                </p>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('transactions.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold text-xs shadow-sm">
                Simpan Transaksi
            </button>
        </div>
    </form>
</div>

<script>
function setTxType(type) {
    document.getElementById('tx_type').value = type;
    const btnIncome = document.getElementById('btn_income');
    const btnExpense = document.getElementById('btn_expense');
    const budgetWrapper = document.getElementById('budget_wrapper');

    if (type === 'income') {
        btnIncome.className = 'py-2.5 px-4 rounded-xl border-2 border-[#16A34A] bg-emerald-50 text-[#16A34A] font-semibold text-sm transition';
        btnExpense.className = 'py-2.5 px-4 rounded-xl border-2 border-slate-200 text-slate-500 font-semibold text-sm transition';
        budgetWrapper.classList.add('opacity-50');
    } else {
        btnExpense.className = 'py-2.5 px-4 rounded-xl border-2 border-[#DC2626] bg-red-50 text-[#DC2626] font-semibold text-sm transition';
        btnIncome.className = 'py-2.5 px-4 rounded-xl border-2 border-slate-200 text-slate-500 font-semibold text-sm transition';
        budgetWrapper.classList.remove('opacity-50');
    }
    checkBudgetWarning();
}

function checkBudgetWarning() {
    const type = document.getElementById('tx_type').value;
    const amount = parseFloat(document.getElementById('amount_input').value || '0');
    const select = document.getElementById('budget_select');
    const selectedOpt = select.options[select.selectedIndex];
    const remaining = parseFloat(selectedOpt?.getAttribute('data-remaining') || '0');
    const warning = document.getElementById('budget_warning');

    if (type === 'expense' && select.value && amount > remaining) {
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }
}

setTxType('{{ $currentType }}');
</script>
@endsection
