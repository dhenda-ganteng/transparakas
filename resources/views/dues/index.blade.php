@extends('layouts.app')

@section('title', 'Verifikasi & Kelola Iuran')
@section('header', 'Manajemen & Verifikasi Iuran Anggota')

@section('content')
@php
    $role = auth()->user()->membership?->role;
@endphp

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Kolom Kiri: Antrean Verifikasi Bukti Bayar & Daftar Semua Iuran -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Antrean Menunggu Verifikasi (PRD 10.12) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Antrean Menunggu Verifikasi ({{ $pendingDues->count() }})</h3>
                    <p class="text-xs text-slate-500">Periksa bukti bayar sebelum menyetujui. Penolakan wajib disertai alasan (Aturan S-03).</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($pendingDues as $due)
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm">{{ $due->user?->name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $due->period }}</span>
                            </div>
                            <p class="text-sm font-semibold text-[#0F766E]">Rp {{ number_format($due->amount, 0, ',', '.') }}</p>
                            @if($due->proof_path)
                                <a href="{{ route('dues.proof', $due) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                                    🔍 Pratinjau Bukti Pembayaran
                                </a>
                            @endif
                        </div>

                        @if($role === 'bendahara')
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route('dues.verify', $due) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-[#16A34A] hover:bg-emerald-700 text-white text-xs font-semibold">
                                        ✔ Setujui & Catat Kas
                                    </button>
                                </form>

                                <button type="button" onclick="document.getElementById('reject_box_{{ $due->id }}').classList.toggle('hidden')"
                                    class="px-3.5 py-2 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 text-xs font-semibold">
                                    ✕ Tolak
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($role === 'bendahara')
                        <div id="reject_box_{{ $due->id }}" class="hidden px-5 pb-5 bg-red-50/40">
                            <form method="POST" action="{{ route('dues.reject', $due) }}" class="flex flex-col sm:flex-row gap-2 pt-2">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="rejection_reason" required minlength="4"
                                    placeholder="Wajib isi alasan penolakan (misal: Bukti transfer buram / nominal kurang)..."
                                    class="flex-1 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs focus:outline-none">
                                <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#DC2626] text-white text-xs font-semibold">
                                    Konfirmasi Tolak
                                </button>
                            </form>
                        </div>
                    @endif
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">
                        Tidak ada pembayaran iuran yang menunggu verifikasi saat ini.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Tabel Semua Tagihan Iuran -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Daftar Seluruh Tagihan Iuran Organisasi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Anggota</th>
                            <th class="px-5 py-3">Periode</th>
                            <th class="px-5 py-3 text-right">Nominal</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Bukti</th>
                            @if($role === 'bendahara')
                                <th class="px-5 py-3 text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($allDues as $due)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $due->user?->name }}</td>
                                <td class="px-5 py-3 text-xs text-slate-600">{{ $due->period }}</td>
                                <td class="px-5 py-3 text-right font-semibold">Rp {{ number_format($due->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-3">
                                    @if($due->status === 'paid')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Lunas</span>
                                    @elseif($due->status === 'pending')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-[#D97706]">Menunggu Verifikasi</span>
                                    @elseif($due->status === 'rejected')
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Ditolak</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Belum Lunas</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    @if($due->proof_path)
                                        <a href="{{ route('dues.proof', $due) }}" target="_blank" class="text-[#0F766E] font-semibold hover:underline">Lihat</a>
                                    @elseif($due->status === 'paid')
                                        <span class="text-slate-500">Tunai Langsung</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                @if($role === 'bendahara')
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        @if($due->status !== 'paid')
                                            <form method="POST" action="{{ route('dues.verify', $due) }}"
                                                onsubmit="return confirm('Tandai tagihan {{ $due->user?->name }} ({{ $due->period }}) sudah dibayar tunai langsung? Nominal akan otomatis masuk ke pemasukan kas.')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-3 py-1 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white text-xs font-semibold">
                                                    💵 Bayar Tunai
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" onclick="openCancelDueModal('{{ route('dues.cancel', $due) }}', '{{ addslashes($due->user?->name) }} ({{ addslashes($due->period) }})')"
                                                class="px-2.5 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold">
                                                Batalkan
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $role === 'bendahara' ? 6 : 5 }}" class="px-5 py-6 text-center text-xs text-slate-400">Belum ada data tagihan iuran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($allDues->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $allDues->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan: Form Buat Tagihan Iuran Baru (Khusus Bendahara) -->
    @if($role === 'bendahara')
    <div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Buat Tagihan Iuran</h3>
                <p class="text-xs text-slate-500">Terbitkan tagihan untuk seluruh anggota aktif sekaligus atau per anggota.</p>
            </div>

            <form method="POST" action="{{ route('dues.store') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Anggota</label>
                    <select name="target" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs bg-white">
                        <option value="all">Semua Anggota Aktif ({{ $members->count() }} orang)</option>
                        @foreach($members as $m)
                            <option value="{{ $m->user_id }}">{{ $m->user?->name }} ({{ $m->role }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Periode Tagihan</label>
                    <input type="text" name="period" required placeholder="Contoh: Maret 2026"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Iuran (Rp)</label>
                    <input type="number" name="amount" required min="1000" value="25000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Jatuh Tempo (Opsional)</label>
                    <input type="date" name="due_date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                </div>

                <button type="submit" class="w-full rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-2.5 text-xs shadow-sm">
                    ＋ Terbitkan Tagihan Iuran
                </button>
            </form>
        </div>
    </div>
    @endif
</div>

@if($role === 'bendahara')
<!-- Modal Batalkan Verifikasi Iuran (Jika Salah Klik / Verifikasi) -->
<div id="cancelDueModal" onclick="if(event.target === this) this.classList.add('hidden')"
    class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Batalkan Status Lunas Iuran?</h3>
            <button type="button" onclick="document.getElementById('cancelDueModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>
        <p class="text-xs text-slate-600">
            Anda akan membatalkan status lunas untuk <strong id="cancelDueLabel"></strong>. Status tagihan kembali menjadi <strong>Belum Lunas</strong>, transaksi pemasukan terkait ditandai <strong>Dibatalkan</strong> (tidak dihapus permanen), dan tercatat di Audit Log.
        </p>
        <form id="cancelDueForm" method="POST" action="" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pembatalan (Wajib Diisi)</label>
                <textarea name="cancellation_reason" rows="3" required minlength="4"
                    placeholder="Contoh: Salah klik anggota saat mencatat bayar tunai"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:ring-2 focus:ring-red-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('cancelDueModal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold">
                    Kembali
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#DC2626] hover:bg-red-700 text-white text-xs font-semibold">
                    Ya, Batalkan Lunas
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelDueModal(actionUrl, label) {
    document.getElementById('cancelDueForm').action = actionUrl;
    document.getElementById('cancelDueLabel').textContent = label;
    document.getElementById('cancelDueModal').classList.remove('hidden');
}
</script>
@endif
@endsection
