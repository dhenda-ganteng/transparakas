<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMembership
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Jika Super Admin platform (Aturan T-04: hanya boleh akses daftar organisasi platform)
        if ($user->is_super_admin) {
            if (in_array('super_admin', $roles, true)) {
                return $next($request);
            }

            abort(403, 'Super Admin tidak memiliki akses ke data keuangan organisasi (Aturan T-04).');
        }

        $membership = $user->membership()->with('organization')->first();

        // Jika belum punya keanggotaan (masih menunggu persetujuan atau ditolak)
        if (!$membership) {
            return redirect()->route('onboarding.pending');
        }

        // Jika akun anggota dinonaktifkan atau organisasi nonaktif
        if ($membership->status !== 'active' || $membership->organization?->status !== 'active') {
            return redirect()->route('onboarding.pending');
        }

        // Jika route meminta peran spesifik, cek apakah peran user sesuai
        if (!empty($roles) && !in_array($membership->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}