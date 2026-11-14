<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\JoinRequest;
use App\Models\Membership;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationAdminController extends Controller
{
    // Layar 17: Anggota Organisasi
    public function members(Request $request): View
    {
        $orgId = $request->user()->currentOrgId();

        $members = Membership::with('user')
            ->where('org_id', $orgId)
            ->orderByRaw("FIELD(role, 'admin_organisasi', 'bendahara', 'auditor', 'anggota')")
            ->get();

        $activeAdminCount = $members->where('role', 'admin_organisasi')->where('status', 'active')->count();

        return view('organization.members', compact('members', 'activeAdminCount'));
    }

    // Ubah peran anggota (Menegakkan Aturan T-07)
    public function updateRole(Request $request, Membership $membership): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();
        if ($membership->org_id !== $orgId) {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin_organisasi,bendahara,auditor,anggota'],
        ]);

        // Aturan T-07: Admin Organisasi tidak boleh menurunkan dirinya sendiri bila ia satu-satunya admin aktif
        if ($membership->user_id === $request->user()->id && $validated['role'] !== 'admin_organisasi') {
            $activeAdminCount = Membership::where('org_id', $orgId)
                ->where('role', 'admin_organisasi')
                ->where('status', 'active')
                ->count();

            if ($activeAdminCount <= 1) {
                return back()->with('error', 'Anda tidak dapat menurunkan peran diri sendiri karena Anda satu-satunya Admin Organisasi aktif (Aturan T-07).');
            }
        }

        $oldRole = $membership->role;
        $membership->update(['role' => $validated['role']]);

        AuditLog::record(
            action: 'update_member_role',
            entityType: 'Membership',
            entityId: $membership->id,
            oldValues: ['anggota' => $membership->user?->name, 'peran' => $oldRole],
            newValues: ['anggota' => $membership->user?->name, 'peran' => $validated['role']]
        );

        return back()->with('success', "Peran {$membership->user?->name} berhasil diubah menjadi {$validated['role']}.");
    }

    // Aktifkan / Nonaktifkan anggota (Menegakkan Aturan T-07)
    public function toggleStatus(Request $request, Membership $membership): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();
        if ($membership->org_id !== $orgId) {
            abort(404);
        }

        $newStatus = $membership->status === 'active' ? 'inactive' : 'active';

        // Aturan T-07: Admin Organisasi tidak boleh menonaktifkan dirinya sendiri bila ia satu-satunya admin
        if ($membership->user_id === $request->user()->id && $newStatus === 'inactive') {
            $activeAdminCount = Membership::where('org_id', $orgId)
                ->where('role', 'admin_organisasi')
                ->where('status', 'active')
                ->count();

            if ($activeAdminCount <= 1) {
                return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri karena Anda satu-satunya Admin Organisasi aktif (Aturan T-07).');
            }
        }

        $oldStatus = $membership->status;
        $membership->update(['status' => $newStatus]);

        AuditLog::record(
            action: $newStatus === 'active' ? 'activate_member' : 'deactivate_member',
            entityType: 'Membership',
            entityId: $membership->id,
            oldValues: ['anggota' => $membership->user?->name, 'status' => $oldStatus],
            newValues: ['anggota' => $membership->user?->name, 'status' => $newStatus]
        );

        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Keanggotaan {$membership->user?->name} berhasil {$label}.");
    }

    // Layar 18: Pengaturan Organisasi & Kode Undangan
    public function settings(Request $request): View
    {
        $membership = $request->user()->membership()->with('organization')->firstOrFail();
        $organization = $membership->organization;

        $activeInvitation = Invitation::where('org_id', $organization->id)
            ->whereNull('revoked_at')
            ->latest()
            ->first();

        $invitationHistory = Invitation::with('creator')
            ->where('org_id', $organization->id)
            ->latest()
            ->take(5)
            ->get();

        return view('organization.settings', compact('organization', 'activeInvitation', 'invitationHistory'));
    }

    // Simpan pengaturan informasi organisasi & kebijakan keterlihatan transaksi (Aturan S-05)
    public function updateSettings(Request $request): RedirectResponse
    {
        $organization = $request->user()->membership()->with('organization')->firstOrFail()->organization;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'visibility_policy' => ['required', 'in:summary,detail'],
        ]);

        $oldValues = [
            'nama' => $organization->name,
            'jenis' => $organization->type,
            'kebijakan_visibilitas' => $organization->visibility_policy,
        ];

        $organization->update($validated);

        AuditLog::record(
            action: 'update_organization_settings',
            entityType: 'Organization',
            entityId: $organization->id,
            oldValues: $oldValues,
            newValues: [
                'nama' => $organization->name,
                'jenis' => $organization->type,
                'kebijakan_visibilitas' => $organization->visibility_policy,
            ]
        );

        return back()->with('success', 'Pengaturan organisasi dan kebijakan keterlihatan transaksi berhasil disimpan.');
    }

    // Buat ulang kode undangan baru (otomatis mencabut kode lama)
    public function regenerateInvitation(Request $request): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();

        $validated = $request->validate([
            'default_role' => ['required', 'in:anggota,bendahara,auditor'],
            'days_valid' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        // Cabut semua kode aktif sebelumnya
        Invitation::where('org_id', $orgId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $newInvitation = Invitation::create([
            'org_id' => $orgId,
            'code' => Invitation::generateUniqueCode(),
            'default_role' => $validated['default_role'],
            'expires_at' => now()->addDays((int) $validated['days_valid']),
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record(
            action: 'create_invitation_code',
            entityType: 'Invitation',
            entityId: $newInvitation->id,
            newValues: [
                'kode' => $newInvitation->code,
                'peran_default' => $newInvitation->default_role,
                'berlaku_sampai' => $newInvitation->expires_at->format('Y-m-d H:i'),
            ]
        );

        return back()->with('success', "Kode undangan baru ({$newInvitation->code}) berhasil dibuat dan kode lama dicabut.");
    }

    // Aturan S-04: Mencabut kode undangan lewat konfirmasi
    public function revokeInvitation(Request $request, Invitation $invitation): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();
        if ($invitation->org_id !== $orgId) {
            abort(404);
        }

        $invitation->update(['revoked_at' => now()]);

        AuditLog::record(
            action: 'revoke_invitation_code',
            entityType: 'Invitation',
            entityId: $invitation->id,
            oldValues: ['status' => 'Aktif', 'kode' => $invitation->code],
            newValues: ['status' => 'Dicabut', 'kode' => $invitation->code]
        );

        return back()->with('success', "Kode undangan {$invitation->code} telah dicabut dan tidak berlaku lagi.");
    }

    // Layar 19: Persetujuan Permintaan Gabung
    public function joinRequests(Request $request): View
    {
        $orgId = $request->user()->currentOrgId();

        $pendingRequests = JoinRequest::with(['user', 'invitation'])
            ->where('org_id', $orgId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $historyRequests = JoinRequest::with(['user', 'reviewer'])
            ->where('org_id', $orgId)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('reviewed_at')
            ->take(10)
            ->get();

        return view('organization.join-requests', compact('pendingRequests', 'historyRequests'));
    }

    // Setujui permintaan gabung
    public function approveRequest(Request $request, JoinRequest $joinRequest): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();
        if ($joinRequest->org_id !== $orgId || $joinRequest->status !== 'pending') {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:anggota,bendahara,auditor,admin_organisasi'],
        ]);

        DB::transaction(function () use ($request, $joinRequest, $validated, $orgId) {
            $joinRequest->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            Membership::updateOrCreate(
                ['user_id' => $joinRequest->user_id],
                [
                    'org_id' => $orgId,
                    'role' => $validated['role'],
                    'status' => 'active',
                    'joined_at' => now(),
                ]
            );

            AuditLog::record(
                action: 'approve_join_request',
                entityType: 'JoinRequest',
                entityId: $joinRequest->id,
                newValues: [
                    'anggota_baru' => $joinRequest->user?->name,
                    'email' => $joinRequest->user?->email,
                    'peran_diberikan' => $validated['role'],
                ]
            );
        });

        return back()->with('success', "Permintaan bergabung {$joinRequest->user?->name} disetujui sebagai {$validated['role']}.");
    }

    // Tolak permintaan gabung
    public function rejectRequest(Request $request, JoinRequest $joinRequest): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();
        if ($joinRequest->org_id !== $orgId || $joinRequest->status !== 'pending') {
            abort(404);
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $joinRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? 'Permintaan bergabung tidak disetujui oleh Admin Organisasi.',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record(
            action: 'reject_join_request',
            entityType: 'JoinRequest',
            entityId: $joinRequest->id,
            newValues: [
                'calon_anggota' => $joinRequest->user?->name,
                'status' => 'Ditolak',
                'alasan' => $joinRequest->rejection_reason,
            ]
        );

        return back()->with('success', "Permintaan bergabung {$joinRequest->user?->name} telah ditolak.");
    }

    // Ajukan penonaktifan organisasi kepada Super Admin Platform
    public function requestDeactivation(Request $request): RedirectResponse
    {
        $organization = Organization::findOrFail($request->user()->currentOrgId());

        $validated = $request->validate([
            'deactivation_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'deactivation_reason.required' => 'Alasan penonaktifan wajib diisi.',
            'deactivation_reason.min' => 'Alasan penonaktifan minimal 10 karakter agar jelas bagi Super Admin.',
        ]);

        $organization->update([
            'deactivation_reason' => $validated['deactivation_reason'],
            'deactivation_requested_at' => now(),
        ]);

        AuditLog::record(
            action: 'request_deactivation',
            entityType: 'Organization',
            entityId: $organization->id,
            newValues: [
                'organisasi' => $organization->name,
                'alasan' => $validated['deactivation_reason'],
                'status_pengajuan' => 'Menunggu Persetujuan Super Admin',
            ]
        );

        return back()->with('success', 'Permintaan penonaktifan organisasi telah dikirim ke Super Admin Platform.');
    }

    // Batalkan pengajuan penonaktifan organisasi
    public function cancelDeactivationRequest(Request $request): RedirectResponse
    {
        $organization = Organization::findOrFail($request->user()->currentOrgId());

        $oldReason = $organization->deactivation_reason;

        $organization->update([
            'deactivation_reason' => null,
            'deactivation_requested_at' => null,
        ]);

        AuditLog::record(
            action: 'cancel_deactivation_request',
            entityType: 'Organization',
            entityId: $organization->id,
            oldValues: [
                'alasan_sebelumnya' => $oldReason,
                'status_pengajuan' => 'Menunggu Persetujuan Super Admin',
            ],
            newValues: [
                'status_pengajuan' => 'Dibatalkan oleh Admin Organisasi',
            ]
        );

        return back()->with('success', 'Pengajuan penonaktifan organisasi berhasil dibatalkan.');
    }
}
