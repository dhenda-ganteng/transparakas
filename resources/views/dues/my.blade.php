@extends('layouts.app')

@section('title', 'Iuran Saya')
@section('header', 'Iuran Saya')

@section('content')
<div>
    <h2 class="text-lg font-bold text-slate-900">Tagihan & Riwayat Iuran Anggota</h2>
    <p class="text-xs text-slate-500">Unggah bukti pembayaran (JPG, PNG, PDF maks 2 MB) untuk diverifikasi oleh Bendahara.</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Periode</th>
                    <th class="px-5 py-3">Jatuh Tempo</th>
                    <th class="px-5 py-3 text-right">Nominal</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Keterangan / Bukti</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($dues as $due)
                    <tr>
                        <td class="px-5 py-4 font-semibold text-slate-900">{{ $due->period }}</td>
                        <td class="px-5 py-4 text-xs text-slate-600">{{ $due->due_date ? $due->due_date->format('d/m/Y') : '-' }}</td>
                        <td class="px-5 py-4 text-right font-semibold text-slate-900">Rp {{ number_format($due->amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-4">
                            @if($due->status === 'paid')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Lunas</span>
                            @elseif($due->status === 'pending')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-[#D97706]">Menunggu Verifikasi</span>
                            @elseif($due->status === 'rejected')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Ditolak</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-600">
                            @if($due->status === 'rejected' && $due->rejection_reason)
                                <p class="text-red-600 font-medium">Ditolak: "{{ $due->rejection_reason }}"</p>
                            @endif
                            @if($due->proof_path)
                                <a href="{{ route('dues.proof', $due) }}" target="_blank" class="text-[#0F766E] font-semibold hover:underline">📎 Lihat Bukti Terunggah</a>
                            @else
                                <span class="text-slate-400">Belum ada bukti</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if(in_array($due->status, ['unpaid', 'rejected']))
                                <form method="POST" action="{{ route('dues.upload', $due) }}" enctype="multipart/form-data" class="inline-flex items-center gap-2">
                                    @csrf
                                    <input type="file" name="proof" required accept=".jpg,.jpeg,.png,.pdf"
                                        class="w-48 text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:bg-teal-50 file:text-[#0F766E] file:font-semibold">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white text-xs font-semibold">
                                        Unggah
                                    </button>
                                </form>
                            @elseif($due->status === 'pending')
                                <span class="text-xs text-amber-700 font-medium">Sedang diperiksa</span>
                            @else
                                <span class="text-xs text-emerald-700 font-semibold">✔ Terverifikasi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400">Belum ada tagihan iuran untuk akun Anda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
