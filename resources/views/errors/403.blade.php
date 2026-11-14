@extends('layouts.guest')

@section('title', '403 Akses Ditolak')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center space-y-4">
        <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl font-bold mx-auto">
            403
        </div>
        <h1 class="text-xl font-bold text-slate-900">Akses Tidak Diizinkan</h1>
        <p class="text-sm text-slate-600">
            {{ $exception->getMessage() ?: 'Peran Anda tidak memiliki izin untuk mengakses halaman atau melakukan aksi ini.' }}
        </p>
        <a href="{{ url('/dashboard') }}" class="inline-block px-5 py-2.5 rounded-lg bg-[#0F766E] text-white font-semibold text-xs">
            ← Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
