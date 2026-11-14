<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Invitation;
use App\Models\JoinRequest;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Akun dikunci sementara, coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->currentOrgId()) {
            AuditLog::record(
                action: 'login',
                entityType: 'User',
                entityId: $user->id,
                orgId: $user->currentOrgId(),
                userId: $user->id
            );
        }

        return $this->redirectAfterLogin($user);
    }

    public function showRegister(Request $request): View
    {
        $inviteCode = $request->query('code');
        return view('auth.register', compact('inviteCode'));
    }

    public function register(Request $request): RedirectResponse
    {
        $rules = [
            'pathway' => ['required', 'in:create,join'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];

        if ($request->input('pathway') === 'create') {
            $rules['organization_name'] = ['required', 'string', 'max:255'];
            $rules['organization_type'] = ['nullable', 'string', 'max:100'];
            $rules['visibility_policy'] = ['nullable', 'in:summary,detail'];
        } else {
            $rules['invitation_code'] = ['required', 'string', 'max:32'];
        }

        $validated = $request->validate($rules);

        $invitation = null;
        if ($validated['pathway'] === 'join') {
            $codeKey = 'invite-attempt:' . $request->ip();
            if (RateLimiter::tooManyAttempts($codeKey, 5)) {
                $seconds = RateLimiter::availableIn($codeKey);
                throw ValidationException::withMessages([
                    'invitation_code' => "Terlalu banyak percobaan kode undangan. Akun dikunci sementara selama {$seconds} detik.",
                ]);
            }

            $invitation = Invitation::with('organization')
                ->where('code', strtoupper(trim($validated['invitation_code'])))
                ->first();

            if (!$invitation || !$invitation->isValid() || $invitation->organization?->status !== 'active') {
                RateLimiter::hit($codeKey, 120);
                throw ValidationException::withMessages([
                    'invitation_code' => 'Kode undangan tidak valid atau sudah kedaluwarsa.',
                ]);
            }

            RateLimiter::clear($codeKey);
        }

        return DB::transaction(function () use ($validated, $invitation) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            if ($validated['pathway'] === 'create') {
                $org = Organization::create([
                    'name' => $validated['organization_name'],
                    'type' => $validated['organization_type'] ?? 'Ormawa',
                    'visibility_policy' => $validated['visibility_policy'] ?? 'summary',
                    'status' => 'active',
                ]);

                Membership::create([
                    'user_id' => $user->id,
                    'org_id' => $org->id,
                    'role' => 'admin_organisasi',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);

                $newInvitation = Invitation::create([
                    'org_id' => $org->id,
                    'code' => Invitation::generateUniqueCode(),
                    'default_role' => 'anggota',
                    'expires_at' => now()->addDays(7),
                    'created_by' => $user->id,
                ]);

                $defaultCategories = [
                    ['name' => 'Iuran Anggota', 'type' => 'income'],
                    ['name' => 'Dana Kemahasiswaan', 'type' => 'income'],
                    ['name' => 'Sponsor & Donasi', 'type' => 'income'],
                    ['name' => 'Konsumsi & Rapat', 'type' => 'expense'],
                    ['name' => 'Perlengkapan & Cetak', 'type' => 'expense'],
                    ['name' => 'Operasional Kegiatan', 'type' => 'expense'],
                ];

                foreach ($defaultCategories as $cat) {
                    Category::withoutGlobalScopes()->create([
                        'org_id' => $org->id,
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                    ]);
                }

                AuditLog::record(
                    action: 'create_organization',
                    entityType: 'Organization',
                    entityId: $org->id,
                    newValues: ['name' => $org->name, 'invitation_code' => $newInvitation->code],
                    orgId: $org->id,
                    userId: $user->id
                );

                Auth::login($user);

                return redirect()->route('onboarding.created');
            }

            JoinRequest::create([
                'user_id' => $user->id,
                'org_id' => $invitation->org_id,
                'invitation_id' => $invitation->id,
                'status' => 'pending',
            ]);

            Auth::login($user);

            return redirect()->route('onboarding.pending');
        });
    }

    public function showOnboardingCreated(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $membership = $user->membership()->with('organization')->first();

        if (!$membership || $membership->role !== 'admin_organisasi') {
            return $this->redirectAfterLogin($user);
        }

        $invitation = Invitation::where('org_id', $membership->org_id)
            ->whereNull('revoked_at')
            ->latest()
            ->first();

        return view('auth.onboarding-created', [
            'organization' => $membership->organization,
            'invitation' => $invitation,
        ]);
    }

    public function showPending(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->is_super_admin) {
            return redirect()->route('platform.organizations');
        }

        $membership = $user->membership()->with('organization')->first();

        if ($membership && $membership->status === 'active' && $membership->organization?->status === 'active') {
            return redirect()->route('dashboard');
        }

        $latestRequest = $user->joinRequests()->with('organization')->latest()->first();

        return view('auth.pending-approval', [
            'membership' => $membership,
            'joinRequest' => $latestRequest,
        ]);
    }

    public function submitJoinCode(Request $request): RedirectResponse
    {
        $request->validate([
            'invitation_code' => ['required', 'string', 'max:32'],
        ]);

        $codeKey = 'invite-attempt:' . $request->ip();
        if (RateLimiter::tooManyAttempts($codeKey, 5)) {
            $seconds = RateLimiter::availableIn($codeKey);
            throw ValidationException::withMessages([
                'invitation_code' => "Terlalu banyak percobaan kode undangan. Akun dikunci sementara selama {$seconds} detik.",
            ]);
        }

        $invitation = Invitation::with('organization')
            ->where('code', strtoupper(trim($request->input('invitation_code'))))
            ->first();

        if (!$invitation || !$invitation->isValid() || $invitation->organization?->status !== 'active') {
            RateLimiter::hit($codeKey, 120);
            throw ValidationException::withMessages([
                'invitation_code' => 'Kode undangan tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        RateLimiter::clear($codeKey);

        $user = $request->user();
        $user->joinRequests()->where('status', 'pending')->delete();

        JoinRequest::create([
            'user_id' => $user->id,
            'org_id' => $invitation->org_id,
            'invitation_id' => $invitation->id,
            'status' => 'pending',
        ]);

        return redirect()->route('onboarding.pending')->with('success', 'Permintaan bergabung berhasil dikirim.');
    }

    public function cancelJoinRequest(Request $request): RedirectResponse
    {
        $request->user()->joinRequests()->where('status', 'pending')->delete();

        return redirect()->route('onboarding.pending')->with('success', 'Permintaan bergabung telah dibatalkan.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->is_super_admin) {
            return redirect()->route('platform.organizations');
        }

        $membership = $user->membership()->with('organization')->first();

        if (!$membership || $membership->status !== 'active' || $membership->organization?->status !== 'active') {
            return redirect()->route('onboarding.pending');
        }

        return redirect()->route('dashboard');
    }
}
