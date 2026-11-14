@extends('layouts.app')

@section('title', 'Detail Transaksi #' . $transaction->id)
@section('header', 'Detail Transaksi & Riwayat Audit')

@section('content')
@php
    $role = auth()->user()->membership?->role;
@endphp

<div class="flex items-center justify-between">
    <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-slate-600 hover:text-[#0F766E]">
        ← Kembali ke Daftar Transaksi
    </a>

    <!-- Tombol Aksi Sesuai Peran (PRD 10.9) -->
    <div class="flex items-center gap-2.5">
        @if($role === 'bendahara' && $transaction->status === 'active')
            <a href="{{ route('transactions.edit', $transaction) }}" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs">
                ✏️ Ubah Transaksi
            </a>
            <button type="button" onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                class="px-3.5 py-2 rounded-lg border border-red-300 bg-red-50 hover:bg-red-100 text-red-700 font-semibold text-xs">
                🚫 Batalkan Transaksi
            </button>
        @endif

        @if($role === 'auditor')
            <button type="button" onclick="document.getElementById('flagModal').classList.remove('hidden')"
                class="px-3.5 py-2 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold text-xs">
                {{ $transaction->is_flagged ? '✔ Cabut Tanda Mencurigakan' : '⚠️ Tandai Mencurigakan' }}
            </button>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Kolom Kiri: Informasi Transaksi & Bukti Nota -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        @if($transaction->status === 'cancelled')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">Dibatalkan</span>
                        @elseif($transaction->type === 'income')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Pemasukan Kas</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Pengeluaran Kas</span>
                        @endif

                        @if($transaction->is_flagged)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">⚠️ Ditandai Mencurigakan</span>
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mt-2 {{ $transaction->status === 'cancelled' ? 'line-through text-slate-400' : '' }}">
                        {{ $transaction->description }}
                    </h2>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold {{ $transaction->status === 'cancelled' ? 'line-through text-slate-400' : ($transaction->type === 'income' ? 'text-[#16A34A]' : 'text-[#DC2626]') }}">
                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-500">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-xs text-slate-500 block">Kategori</span>
                    <span class="font-semibold text-slate-800">{{ $transaction->category?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Pos Anggaran Kegiatan</span>
                    <span class="font-semibold text-slate-800">{{ $transaction->budget?->activity_name ?? 'Tidak terikat anggaran kegiatan' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Dicatat Oleh</span>
                    <span class="font-semibold text-slate-800">{{ $transaction->creator?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Waktu Input</span>
                    <span class="font-semibold text-slate-800">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            @if($transaction->status === 'cancelled')
                <div class="p-4 rounded-xl bg-slate-100 border border-slate-300 text-xs space-y-1">
                    <p class="font-bold text-slate-800">🚫 Informasi Pembatalan Transaksi (Aturan S-01):</p>
                    <p class="text-slate-700">Alasan: <strong>"{{ $transaction->cancellation_reason }}"</strong></p>
                    <p class="text-slate-500">Dibatalkan oleh {{ $transaction->canceller?->name ?? '-' }} pada {{ $transaction->cancelled_at?->format('d/m/Y H:i') }}</p>
                </div>
            @endif

            @if($transaction->is_flagged)
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs space-y-1">
                    <p class="font-bold text-amber-900">⚠️ Catatan Pemeriksaan Auditor:</p>
                    <p class="text-amber-800">"{{ $transaction->flag_note }}"</p>
                    <p class="text-amber-700">Ditandai oleh {{ $transaction->flagger?->name ?? 'Auditor' }} pada {{ $transaction->flagged_at?->format('d/m/Y H:i') }}</p>
                </div>
            @endif
        </div>

        <!-- Kartu Pratinjau Bukti Nota -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Bukti / Nota Transaksi</h3>
                @if($transaction->receipt_path)
                    <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="text-xs font-semibold text-[#0F766E] hover:underline">
                        🔗 Buka File Penuh
                    </a>
                @endif
            </div>

            @if($transaction->receipt_path)
                @if(\Illuminate\Support\Str::endsWith(strtolower($transaction->receipt_path), '.pdf'))
                    <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-2">
                        <p class="text-sm font-semibold text-slate-800">📄 Dokumen Bukti PDF Terlampir</p>
                        <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="inline-block px-4 py-2 rounded-lg bg-[#0F766E] text-white text-xs font-semibold">
                            Lihat / Unduh PDF
                        </a>
                    </div>
                @else
                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 p-2">
                        <img src="{{ route('transactions.receipt', $transaction) }}" alt="Bukti Transaksi" class="max-h-96 mx-auto rounded-lg object-contain">
                    </div>
                @endif
            @else
                <div class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                    Tidak ada lampiran nota pada transaksi ini.
                </div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan: Timeline Riwayat Perubahan Audit (Nilai Lama -> Nilai Baru, PRD 10.9 & S-02) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Riwayat Perubahan (Audit Trail)</h3>
            <p class="text-xs text-slate-500">Rekaman permanen setiap aksi pada transaksi ini.</p>
        </div>

        <div class="relative pl-5 border-l-2 border-teal-200 space-y-6">
            @forelse($auditHistory as $log)
                <div class="relative space-y-1.5">
                    <div class="absolute -left-[25px] top-1 w-3.5 h-3.5 rounded-full bg-[#0F766E] border-2 border-white"></div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800 uppercase">{{ $log->action }}</span>
                        <span class="text-slate-400">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <p class="text-xs text-slate-600">Oleh: <strong>{{ $log->user?->name ?? 'Sistem' }}</strong></p>

                    @if($log->old_values && $log->new_values)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                            @foreach($log->new_values as $key => $newVal)
                                @php $oldVal = $log->old_values[$key] ?? null; @endphp
                                @if($oldVal !== $newVal)
                                    <div>
                                        <span class="font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $key) }}:</span>
                                        <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                            <span class="line-through text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                                {{ is_numeric($oldVal) && $key === 'nominal' ? 'Rp '.number_format($oldVal, 0, ',', '.') : ($oldVal ?? '-') }}
                                            </span>
                                            <span class="text-slate-400">→</span>
                                            <span class="font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                                                {{ is_numeric($newVal) && $key === 'nominal' ? 'Rp '.number_format($newVal, 0, ',', '.') : $newVal }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif($log->new_values)
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-600 space-y-0.5">
                            @foreach($log->new_values as $k => $v)
                                <div><strong class="capitalize">{{ str_replace('_', ' ', $k) }}:</strong> {{ is_numeric($v) && $k === 'nominal' ? 'Rp '.number_format($v, 0, ',', '.') : $v }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada riwayat perubahan.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Layar 21: Modal Konfirmasi Pembatalan Transaksi (Wajib Isi Alasan - Aturan S-01) -->
@if($role === 'bendahara' && $transaction->status === 'active')
<div id="cancelModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Batalkan Transaksi Ini?</h3>
            <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
            Sesuai <strong>Aturan T-01 & S-01</strong>, transaksi tidak akan dihapus permanen melainkan ditandai <strong>Dibatalkan</strong>. Nominalnya tidak lagi dihitung dalam saldo kas dan alasan pembatalan wajib tercatat di Audit Log.
        </p>
        <form method="POST" action="{{ route('transactions.cancel', $transaction) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pembatalan (Wajib Diisi)</label>
                <textarea name="cancellation_reason" rows="3" required minlength="5"
                    placeholder="Contoh: Salah input nominal, seharusnya Rp 150.000 bukan Rp 1.500.000"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold">
                    Kembali
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#DC2626] hover:bg-red-700 text-white text-xs font-semibold">
                    Batalkan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal Tandai Mencurigakan oleh Auditor -->
@if($role === 'auditor')
<div id="flagModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900">
            {{ $transaction->is_flagged ? 'Cabut Tanda Mencurigakan?' : 'Tandai Transaksi Mencurigakan' }}
        </h3>
        <form method="POST" action="{{ route('transactions.flag', $transaction) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            @if(!$transaction->is_flagged)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pemeriksaan Auditor</label>
                    <textarea name="flag_note" rows="3" required placeholder="Contoh: Nota bukti belum dilampirkan atau nominal tidak sesuai anggaran"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>
            @endif
            <div class="flex justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('flagModal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#D97706] hover:bg-amber-700 text-white text-xs font-semibold">
                    Simpan Status Audit
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
