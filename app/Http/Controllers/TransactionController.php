<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    private function checkViewPermission(Request $request): void
    {
        $membership = $request->user()->membership()->with('organization')->firstOrFail();

        if (in_array($membership->role, ['bendahara', 'auditor'], true)) {
            return;
        }

        // Aturan S-05: Anggota hanya boleh melihat detail transaksi bila kebijakan organisasi = 'detail'
        if ($membership->role === 'anggota' && $membership->organization?->visibility_policy === 'detail') {
            return;
        }

        abort(403, 'Kebijakan organisasi tidak mengizinkan anggota melihat detail transaksi per baris (Aturan S-05).');
    }

    public function index(Request $request): View
    {
        $this->checkViewPermission($request);

        $query = Transaction::with(['category', 'budget', 'creator']);

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }
        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'cancelled'], true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%");
        }

        $transactions = $query->latest('transaction_date')->latest('id')->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $budgets = Budget::orderBy('activity_name')->get();

        return view('transactions.form', [
            'transaction' => null,
            'categories' => $categories,
            'budgets' => $budgets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->currentOrgId();

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'description' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        // Pastikan kategori & anggaran milik organisasi sendiri (T-03)
        $category = Category::findOrFail($validated['category_id']);
        $budget = !empty($validated['budget_id']) ? Budget::findOrFail($validated['budget_id']) : null;

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store("org_{$orgId}/receipts", 'local');
        }

        $transaction = Transaction::create([
            'org_id' => $orgId,
            'category_id' => $category->id,
            'budget_id' => $budget?->id,
            'transaction_date' => $validated['transaction_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'receipt_path' => $receiptPath,
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record(
            action: 'create',
            entityType: 'Transaction',
            entityId: $transaction->id,
            newValues: [
                'tanggal' => $transaction->transaction_date->format('Y-m-d'),
                'tipe' => $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                'nominal' => (float) $transaction->amount,
                'kategori' => $category->name,
                'keterangan' => $transaction->description,
            ]
        );

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dicatat dan masuk ke audit log.');
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $this->checkViewPermission($request);

        $transaction->load(['category', 'budget', 'creator', 'canceller', 'flagger']);
        $auditHistory = AuditLog::with('user')
            ->where('entity_type', 'Transaction')
            ->where('entity_id', $transaction->id)
            ->oldest('created_at')
            ->get();

        return view('transactions.show', compact('transaction', 'auditHistory'));
    }

    public function edit(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status === 'cancelled') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Transaksi yang sudah dibatalkan tidak dapat diubah.');
        }

        $categories = Category::orderBy('name')->get();
        $budgets = Budget::orderBy('activity_name')->get();

        return view('transactions.form', compact('transaction', 'categories', 'budgets'));
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'cancelled') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Transaksi yang sudah dibatalkan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'description' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $newCategory = Category::findOrFail($validated['category_id']);
        $newBudget = !empty($validated['budget_id']) ? Budget::findOrFail($validated['budget_id']) : null;

        // Simpan nilai lama untuk Audit Log (Aturan S-02)
        $oldValues = [
            'tanggal' => $transaction->transaction_date->format('Y-m-d'),
            'tipe' => $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            'nominal' => (float) $transaction->amount,
            'kategori' => $transaction->category?->name,
            'anggaran' => $transaction->budget?->activity_name ?? '-',
            'keterangan' => $transaction->description,
        ];

        $receiptPath = $transaction->receipt_path;
        if ($request->hasFile('receipt')) {
            $orgId = $request->user()->currentOrgId();
            $receiptPath = $request->file('receipt')->store("org_{$orgId}/receipts", 'local');
        }

        $transaction->update([
            'category_id' => $newCategory->id,
            'budget_id' => $newBudget?->id,
            'transaction_date' => $validated['transaction_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'receipt_path' => $receiptPath,
        ]);

        $newValues = [
            'tanggal' => $transaction->transaction_date->format('Y-m-d'),
            'tipe' => $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            'nominal' => (float) $transaction->amount,
            'kategori' => $newCategory->name,
            'anggaran' => $newBudget?->activity_name ?? '-',
            'keterangan' => $transaction->description,
        ];

        AuditLog::record(
            action: 'update',
            entityType: 'Transaction',
            entityId: $transaction->id,
            oldValues: $oldValues,
            newValues: $newValues
        );

        return redirect()->route('transactions.show', $transaction)->with('success', 'Perubahan transaksi berhasil disimpan dan tercatat di audit log.');
    }

    // Aturan S-01: Membatalkan transaksi wajib menyertakan alasan (tidak dihapus permanen T-01)
    public function cancel(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->status === 'cancelled') {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi (Aturan S-01).',
        ]);

        $transaction->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
        ]);

        AuditLog::record(
            action: 'cancel',
            entityType: 'Transaction',
            entityId: $transaction->id,
            oldValues: ['status' => 'Aktif'],
            newValues: [
                'status' => 'Dibatalkan',
                'alasan' => $validated['cancellation_reason'],
            ]
        );

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi telah dibatalkan (tidak dihapus permanen) dan tercatat di audit log.');
    }

    // Aksi Auditor: Tandai Mencurigakan
    public function toggleFlag(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'flag_note' => ['nullable', 'string', 'max:500'],
        ]);

        $newFlagState = !$transaction->is_flagged;

        $transaction->update([
            'is_flagged' => $newFlagState,
            'flag_note' => $newFlagState ? ($validated['flag_note'] ?? 'Perlu pemeriksaan bukti lebih lanjut.') : null,
            'flagged_by' => $newFlagState ? $request->user()->id : null,
            'flagged_at' => $newFlagState ? now() : null,
        ]);

        AuditLog::record(
            action: $newFlagState ? 'flag_transaction' : 'unflag_transaction',
            entityType: 'Transaction',
            entityId: $transaction->id,
            oldValues: ['ditandai' => !$newFlagState],
            newValues: [
                'ditandai' => $newFlagState,
                'catatan_auditor' => $transaction->flag_note,
            ]
        );

        $msg = $newFlagState ? 'Transaksi ditandai mencurigakan.' : 'Tanda mencurigakan pada transaksi dicabut.';
        return redirect()->route('transactions.show', $transaction)->with('success', $msg);
    }

    // Unduh/Lihat Bukti Nota (Terproteksi hak akses & isolasi organisasi - PRD 3.4)
    public function receipt(Request $request, Transaction $transaction): StreamedResponse
    {
        $this->checkViewPermission($request);

        if (!$transaction->receipt_path || !Storage::disk('local')->exists($transaction->receipt_path)) {
            abort(404, 'File bukti tidak ditemukan.');
        }

        return Storage::disk('local')->response($transaction->receipt_path);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->checkViewPermission($request);

        $transactions = Transaction::with(['category', 'creator'])->latest('transaction_date')->get();
        $filename = 'transaksi_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM + pemisah titik koma (;) agar langsung rapi per kolom di Excel Indonesia
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Tanggal', 'Keterangan', 'Kategori', 'Tipe', 'Nominal (Rp)', 'Status', 'Dibuat Oleh'], ';');

            foreach ($transactions as $tx) {
                fputcsv($handle, $this->sanitizeCsvRow([
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->description,
                    $tx->category?->name ?? '-',
                    $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    (int) $tx->amount,
                    $tx->status === 'active' ? 'Aktif' : 'Dibatalkan',
                    $tx->creator?->name ?? '-',
                ]), ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
