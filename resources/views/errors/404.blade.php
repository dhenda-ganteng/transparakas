@extends('layouts.guest')

@section('title', '404 Halaman Tidak Ditemukan')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center space-y-4">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-2xl font-bold mx-auto">
            404
        </div>
        <h1 class="text-xl font-bold text-slate-900">Data atau Halaman Tidak Ditemukan</h1>
        <p class="text-sm text-slate-600">
            Data yang Anda cari tidak ditemukan atau berada di luar cakupan organisasi Anda (Aturan T-03).
        </p>
        <a href="{{ url('/dashboard') }}" class="inline-block px-5 py-2.5 rounded-lg bg-[#0F766E] text-white font-semibold text-xs">
            ← Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
