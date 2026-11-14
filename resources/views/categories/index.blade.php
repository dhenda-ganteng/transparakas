@extends('layouts.app')

@section('title', 'Kategori Transaksi')
@section('header', 'Kategori Pemasukan & Pengeluaran')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-base font-semibold text-slate-900">Daftar Kategori Organisasi</h3>
            <p class="text-xs text-slate-500">Kategori bersifat spesifik milik organisasi Anda sendiri.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Nama Kategori</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">Jumlah Transaksi</th>
                        <th class="px-5 py-3 text-right">Ubah Nama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                            <td class="px-5 py-3">
                                @if($category->type === 'income')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Pemasukan</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-[#DC2626]">Pengeluaran</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600">{{ $category->transactions_count }} transaksi</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('categories.update', $category) }}" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="type" value="{{ $category->type }}">
                                    <input type="text" name="name" value="{{ $category->name }}" required
                                        class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs w-40">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Tambah Kategori Baru</h3>
            <form method="POST" action="{{ route('categories.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Sewa Tempat & Sound"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tipe</label>
                    <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                </div>
                <button type="submit" class="w-full rounded-lg bg-[#0F766E] hover:bg-[#115E59] text-white font-semibold py-2.5">
                    ＋ Tambah Kategori
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
