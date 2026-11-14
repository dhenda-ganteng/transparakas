<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Due;
use App\Models\Membership;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DueController extends Controller
{
    // Layar 12: Iuran Saya (Anggota)
    public function myDues(Request $request): View
    {
        $dues = Due::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('dues.my', compact('dues'));
    }

    // Anggota mengunggah bukti bayar iuran
    public function uploadProof(Request $request, Due $due): RedirectResponse
    {
        if ($due->user_id !== $request->user()->id) {
            abort(403, 'Anda hanya dapat mengunggah bukti untuk tagihan milik sendiri.');
        }

        if ($due->status === 'paid') {
            return back()->with('error', 'Tagihan ini sudah lunas.');
        }

        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $orgId = $request->user()->currentOrgId();
        $path = $request->file('proof')->store("org_{$orgId}/dues", 'local');

        $due->update([
            'proof_path' => $path,
            'status' => 'pending',
            'rejection_reason' => null,
        ]);

        AuditLog::record(
            action: 'upload_due_proof',
            entityType: 'Due',
            entityId: $due->id,
            newValues: ['periode' => $due->period, 'status' => 'Menunggu Verifikasi']
        );

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi Bendahara.');
    }

    // Layar 13: Daftar & Antrean Verifikasi Iuran (Bendahara & Auditor)
    public function index(Request $request): View
    {
        $orgId = $request->user()->currentOrgId();

        $pendingDues = Due::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $allDues = Due::with(['user', 'verifier'])
            ->latest()
            ->paginate(12);

        $members = Membership::with('user')
            ->where('org_id', $orgId)
            ->where('status', 'active')
            ->get();

        return view('dues.index', compact('pendingDues', 'allDues', 'members'));
    }

    // Bendahara membuat tagihan iuran (untuk 1 anggota atau seluruh anggota aktif)
    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();

        $validated = $request->validate([
            'target' => ['required', 'string'], // 'all' atau user_id
            'period' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'due_date' => ['nullable', 'date'],
        ]);

        if ($validated['target'] === 'all') {
            $memberUserIds = Membership::where('org_id', $orgId)
                ->where('status', 'active')
                ->pluck('user_id');
        } else {
            $memberUserIds = Membership::where('org_id', $orgId)
                ->where('user_id', $validated['target'])
                ->pluck('user_id');
        }

        $createdCount = 0;
        foreach ($memberUserIds as $uid) {
            $exists = Due::where('user_id', $uid)->where('period', $validated['period'])->exists();
            if (!$exists) {
                Due::create([
                    'org_id' => $orgId,
                    'user_id' => $uid,
                    'period' => $validated['period'],
                    'amount' => $validated['amount'],
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => 'unpaid',
                ]);
                $createdCount++;
            }
        }

        AuditLog::record(
            action: 'create_dues',
            entityType: 'Due',
            newValues: [
                'periode' => $validated['period'],
                'nominal' => (float) $validated['amount'],
                'jumlah_tagihan_dibuat' => $createdCount,
            ]
        );

        return back()->with('success', "{$createdCount} tagihan iuran periode {$validated['period']} berhasil dibuat.");
    }

    // Bendahara menyetujui pembayaran iuran (baik dari bukti unggahan maupun bayar tunai langsung)
    public function verify(Request $request, Due $due): RedirectResponse
    {
        if ($due->status === 'paid') {
            return back()->with('error', 'Iuran ini sudah diverifikasi sebelumnya.');
        }

        $oldStatus = $due->status;
        $isCashDirect = empty($due->proof_path);

        $due->update([
            'status' => 'paid',
            'rejection_reason' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Otomatis catat ke pemasukan kas organisasi
        $orgId = $request->user()->currentOrgId();
        $category = Category::firstOrCreate(
            ['org_id' => $orgId, 'name' => 'Iuran Anggota', 'type' => 'income']
        );

        $methodLabel = $isCashDirect ? 'Tunai Langsung' : 'Bukti Transfer';

        Transaction::create([
            'org_id' => $orgId,
            'category_id' => $category->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'amount' => $due->amount,
            'description' => "Pembayaran Iuran ({$methodLabel}) {$due->period} - {$due->user?->name}",
            'receipt_path' => $due->proof_path,
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record(
            action: $isCashDirect ? 'pay_due_cash' : 'verify_due',
            entityType: 'Due',
            entityId: $due->id,
            oldValues: ['status' => $oldStatus],
            newValues: [
                'anggota' => $due->user?->name,
                'periode' => $due->period,
                'nominal' => (float) $due->amount,
                'metode' => $methodLabel,
                'status' => 'Lunas',
            ]
        );

        return back()->with('success', "Iuran {$due->user?->name} ({$due->period}) ditandai Lunas ({$methodLabel}) dan otomatis masuk ke kas.");
    }

    // Aturan S-03: Menolak iuran wajib mengisi alasan penolakan
    public function reject(Request $request, Due $due): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:4', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan iuran wajib diisi (Aturan S-03).',
        ]);

        $oldStatus = $due->status;

        $due->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        AuditLog::record(
            action: 'reject_due',
            entityType: 'Due',
            entityId: $due->id,
            oldValues: ['status' => $oldStatus],
            newValues: [
                'anggota' => $due->user?->name,
                'periode' => $due->period,
                'status' => 'Ditolak',
                'alasan' => $validated['rejection_reason'],
            ]
        );

        return back()->with('success', 'Pembayaran iuran telah ditolak beserta alasannya.');
    }

    // Batalkan status Lunas jika Bendahara salah klik/verifikasi (tanpa menghapus transaksi permanen T-01)
    public function cancelPaid(Request $request, Due $due): RedirectResponse
    {
        if ($due->status !== 'paid') {
            return back()->with('error', 'Hanya iuran berstatus Lunas yang dapat dibatalkan verifikasinya.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:4', 'max:500'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan verifikasi iuran wajib diisi.',
        ]);

        // Batalkan transaksi pemasukan yang otomatis terbuat dari iuran ini (bukan dihapus permanen)
        $relatedTx = Transaction::where('type', 'income')
            ->where('status', 'active')
            ->where('amount', $due->amount)
            ->where('description', 'like', "%{$due->period} - {$due->user?->name}%")
            ->latest('id')
            ->first();

        if ($relatedTx) {
            $relatedTx->update([
                'status' => 'cancelled',
                'cancellation_reason' => "Pembatalan verifikasi iuran: {$validated['cancellation_reason']}",
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => now(),
            ]);

            AuditLog::record(
                action: 'cancel',
                entityType: 'Transaction',
                entityId: $relatedTx->id,
                oldValues: ['status' => 'Aktif'],
                newValues: [
                    'status' => 'Dibatalkan',
                    'alasan' => $relatedTx->cancellation_reason,
                ]
            );
        }

        $due->update([
            'status' => 'unpaid',
            'verified_by' => null,
            'verified_at' => null,
        ]);

        AuditLog::record(
            action: 'cancel_due_verification',
            entityType: 'Due',
            entityId: $due->id,
            oldValues: ['status' => 'Lunas'],
            newValues: [
                'anggota' => $due->user?->name,
                'periode' => $due->period,
                'status' => 'Belum Lunas',
                'alasan' => $validated['cancellation_reason'],
            ]
        );

        return back()->with('success', "Verifikasi iuran {$due->user?->name} ({$due->period}) dibatalkan. Status kembali Belum Lunas dan saldo kas otomatis disesuaikan.");
    }

    // Lihat file bukti bayar iuran secara aman
    public function proof(Request $request, Due $due): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $due->user_id === $user->id;
        $isFinanceOrAuditor = $user->hasRole(['bendahara', 'auditor']);

        if (!$isOwner && !$isFinanceOrAuditor) {
            abort(403, 'Anda tidak berhak melihat bukti pembayaran ini.');
        }

        if (!$due->proof_path || !Storage::disk('local')->exists($due->proof_path)) {
            abort(404, 'File bukti bayar tidak ditemukan.');
        }

        return Storage::disk('local')->response($due->proof_path);
    }
}
