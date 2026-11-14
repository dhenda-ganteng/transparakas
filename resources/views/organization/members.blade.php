@extends('layouts.app')

@section('title', 'Anggota Organisasi')
@section('header', 'Kelola Anggota & Peran Organisasi')

@section('content')
<!-- Peringatan Bila Admin Tunggal (Aturan T-07 & PRD 10.16) -->
@if($activeAdminCount <= 1)
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-800 flex items-center gap-2.5">
        <span class="text-base">⚠️</span>
        <span><strong>Perhatian (Aturan T-07):</strong> Anda saat ini adalah satu-satunya <strong>Admin Organisasi</strong> aktif. Anda tidak dapat menurunkan peran atau menonaktifkan akun diri sendiri sebelum mengangkat admin lain.</span>
    </div>
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Daftar Anggota Organisasi ({{ $members->count() }})</h3>
            <p class="text-xs text-slate-500">Atur pembagian peran pengurus (Bendahara, Auditor/Ketua, Anggota) dan status keanggotaan.</p>
        </div>
        <a href="{{ route('organization.join-requests') }}" class="text-xs font-semibold text-[#0F766E] hover:underline">
            Lihat Permintaan Gabung →
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama Anggota</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Tanggal Bergabung</th>
                    <th class="px-5 py-3">Peran Saat Ini</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($members as $m)
                    @php
                        $isSelfSingleAdmin = ($m->user_id === auth()->id() && $m->role === 'admin_organisasi' && $activeAdminCount <= 1);
                    @endphp
                    <tr>
                        <td class="px-5 py-3.5 font-semibold text-slate-900">
                            {{ $m->user?->name }}
                            @if($m->user_id === auth()->id())
                                <span class="ml-1 text-[11px] text-[#0F766E] font-medium">(Anda)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $m->user?->email }}</td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">{{ $m->joined_at ? $m->joined_at->format('d/m/Y') : '-' }}</td>
                        <td class="px-5 py-3.5">
                            <form method="POST" action="{{ route('organization.members.role', $m) }}"
                                onsubmit="return confirm('Ubah peran anggota {{ $m->user?->name }}?')" class="inline-flex items-center gap-1.5">
                                @csrf
                                @method('PATCH')
                                <select name="role" @disabled($isSelfSingleAdmin) class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs bg-white disabled:bg-slate-100">
                                    <option value="admin_organisasi" @selected($m->role === 'admin_organisasi')>Admin Organisasi</option>
                                    <option value="bendahara" @selected($m->role === 'bendahara')>Bendahara</option>
                                    <option value="auditor" @selected($m->role === 'auditor')>Auditor / Ketua</option>
                                    <option value="anggota" @selected($m->role === 'anggota')>Anggota</option>
                                </select>
                                @if(!$isSelfSingleAdmin)
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                        Simpan
                                    </button>
                                @endif
                            </form>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($m->status === 'active')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-[#16A34A]">Aktif</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @if(!$isSelfSingleAdmin)
                                <form method="POST" action="{{ route('organization.members.status', $m) }}"
                                    onsubmit="return confirm('Konfirmasi perubahan status keanggotaan {{ $m->user?->name }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold {{ $m->status === 'active' ? 'text-red-600 hover:underline' : 'text-[#0F766E] hover:underline' }}">
                                        {{ $m->status === 'active' ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">Terkunci (Admin Tunggal)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
