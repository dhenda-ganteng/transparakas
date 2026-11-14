<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $membership = $user->membership()->with('organization')->first();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();

        return view('profile.edit', compact('user', 'membership', 'sessions'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $oldName = $user->name;
        $user->update(['name' => $validated['name']]);

        if ($user->currentOrgId()) {
            AuditLog::record(
                action: 'update_profile',
                entityType: 'User',
                entityId: $user->id,
                oldValues: ['nama' => $oldName],
                newValues: ['nama' => $user->name]
            );
        }

        return back()->with('success', 'Nama profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Password lama tidak sesuai.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($user->currentOrgId()) {
            AuditLog::record(
                action: 'change_password',
                entityType: 'User',
                entityId: $user->id,
                newValues: ['keterangan' => 'Pengguna mengubah password akun']
            );
        }

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
