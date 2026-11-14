<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformController extends Controller
{
    // Layar 23 (P3): Daftar Organisasi Platform - Super Admin tanpa akses data keuangan (Aturan T-04)
    public function organizations(): View
    {
        $organizations = Organization::withCount(['memberships' => function ($q) {
            $q->where('status', 'active');
        }])->latest()->get();

        return view('platform.organizations', compact('organizations'));
    }

    public function toggleOrganization(Organization $organization): RedirectResponse
    {
        $newStatus = $organization->status === 'active' ? 'inactive' : 'active';
        $organization->update([
            'status' => $newStatus,
            'deactivation_reason' => null,
            'deactivation_requested_at' => null,
        ]);

        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Organisasi {$organization->name} berhasil {$label}.");
    }

    public function rejectDeactivation(Organization $organization): RedirectResponse
    {
        $organization->update([
            'deactivation_reason' => null,
            'deactivation_requested_at' => null,
        ]);

        return back()->with('success', "Permintaan penonaktifan organisasi {$organization->name} ditolak. Organisasi tetap aktif.");
    }
}
