<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DueController;
use App\Http\Controllers\OrganizationAdminController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Route Tamu (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Route Sudah Login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Onboarding & Status Menunggu Persetujuan (Layar 3, 4, 5)
    Route::get('/onboarding/created', [AuthController::class, 'showOnboardingCreated'])->name('onboarding.created');
    Route::get('/onboarding/pending', [AuthController::class, 'showPending'])->name('onboarding.pending');
    Route::post('/onboarding/join', [AuthController::class, 'submitJoinCode'])->name('onboarding.join');
    Route::delete('/onboarding/cancel', [AuthController::class, 'cancelJoinRequest'])->name('onboarding.cancel');

    // Profil & Ubah Password (Layar 20 - Semua Pengguna)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Halaman Super Admin Platform (Layar 23 P3 - Aturan T-04: Tanpa akses data keuangan)
    Route::middleware('member:super_admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/organizations', [PlatformController::class, 'organizations'])->name('organizations');
        Route::patch('/organizations/{organization}/toggle', [PlatformController::class, 'toggleOrganization'])->name('organizations.toggle');
        Route::patch('/organizations/{organization}/reject-deactivation', [PlatformController::class, 'rejectDeactivation'])->name('organizations.reject-deactivation');
    });

    // Area Anggota Organisasi Aktif
    Route::middleware('member')->group(function () {
        // Dashboard sesuai peran (Layar 6, 11, dan versi Admin Org)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Transaksi: Tambah & Ubah & Batalkan khusus Bendahara (Harus di atas /transactions/{transaction})
        Route::middleware('member:bendahara')->group(function () {
            Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
            Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
            Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
            Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
            Route::patch('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
        });

        // Transaksi: Tandai Mencurigakan khusus Auditor
        Route::middleware('member:auditor')->group(function () {
            Route::patch('/transactions/{transaction}/flag', [TransactionController::class, 'toggleFlag'])->name('transactions.flag');
        });

        // Transaksi: Baca Daftar, Detail, Bukti, Ekspor CSV (Bendahara, Auditor, atau Anggota jika kebijakan 'detail' S-05)
        Route::middleware('member:bendahara,auditor,anggota')->group(function () {
            Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export.csv');
            Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
            Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
        });

        // Iuran Saya (Layar 12 - Anggota)
        Route::get('/my-dues', [DueController::class, 'myDues'])->name('dues.my');
        Route::post('/dues/{due}/upload', [DueController::class, 'uploadProof'])->name('dues.upload');
        Route::get('/dues/{due}/proof', [DueController::class, 'proof'])->name('dues.proof');

        // Verifikasi & Kelola Iuran (Layar 13 - Bendahara & Auditor baca)
        Route::middleware('member:bendahara,auditor')->group(function () {
            Route::get('/dues', [DueController::class, 'index'])->name('dues.index');
        });
        Route::middleware('member:bendahara')->group(function () {
            Route::post('/dues', [DueController::class, 'store'])->name('dues.store');
            Route::patch('/dues/{due}/verify', [DueController::class, 'verify'])->name('dues.verify');
            Route::patch('/dues/{due}/reject', [DueController::class, 'reject'])->name('dues.reject');
            Route::patch('/dues/{due}/cancel', [DueController::class, 'cancelPaid'])->name('dues.cancel');
        });

        // Anggaran Kegiatan (Layar 14 - Bendahara CRUD, Auditor & Anggota baca alokasi acara)
        Route::middleware('member:bendahara,auditor,anggota')->group(function () {
            Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        });
        Route::middleware('member:bendahara')->group(function () {
            Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
            Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        });

        // Laporan & Ringkasan Publik (Layar 10 - Bendahara, Auditor, Anggota)
        Route::middleware('member:bendahara,auditor,anggota')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        });
        Route::middleware('member:bendahara,auditor')->group(function () {
            Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
        });

        // Audit Log (Layar 15 - Hanya-baca T-02 untuk Auditor & Admin Organisasi)
        Route::middleware('member:auditor,admin_organisasi')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

        // Kategori (Layar 16 - Bendahara)
        Route::middleware('member:bendahara')->group(function () {
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        });

        // Menu Admin Organisasi (Layar 17, 18, 19)
        Route::middleware('member:admin_organisasi')->prefix('organization')->name('organization.')->group(function () {
            Route::get('/members', [OrganizationAdminController::class, 'members'])->name('members');
            Route::patch('/members/{membership}/role', [OrganizationAdminController::class, 'updateRole'])->name('members.role');
            Route::patch('/members/{membership}/status', [OrganizationAdminController::class, 'toggleStatus'])->name('members.status');

            Route::get('/settings', [OrganizationAdminController::class, 'settings'])->name('settings');
            Route::put('/settings', [OrganizationAdminController::class, 'updateSettings'])->name('settings.update');
            Route::post('/request-deactivation', [OrganizationAdminController::class, 'requestDeactivation'])->name('request-deactivation');
            Route::delete('/request-deactivation', [OrganizationAdminController::class, 'cancelDeactivationRequest'])->name('cancel-deactivation');
            Route::post('/invitations/regenerate', [OrganizationAdminController::class, 'regenerateInvitation'])->name('invitations.regenerate');
            Route::patch('/invitations/{invitation}/revoke', [OrganizationAdminController::class, 'revokeInvitation'])->name('invitations.revoke');

            Route::get('/join-requests', [OrganizationAdminController::class, 'joinRequests'])->name('join-requests');
            Route::patch('/join-requests/{joinRequest}/approve', [OrganizationAdminController::class, 'approveRequest'])->name('join-requests.approve');
            Route::patch('/join-requests/{joinRequest}/reject', [OrganizationAdminController::class, 'rejectRequest'])->name('join-requests.reject');
        });
    });
});
