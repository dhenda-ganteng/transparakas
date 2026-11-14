<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        $budgets = Budget::with([
            'creator',
            'transactions' => function ($q) {
                $q->where('type', 'expense')
                  ->where('status', 'active')
                  ->with(['category', 'creator'])
                  ->latest('transaction_date');
            },
        ])->latest()->get();

        return view('budgets.index', compact('budgets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_name' => ['required', 'string', 'max:255'],
            'limit_amount' => ['required', 'numeric', 'min:1000'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $budget = Budget::create([
            'org_id' => $request->user()->currentOrgId(),
            'activity_name' => $validated['activity_name'],
            'limit_amount' => $validated['limit_amount'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record(
            action: 'create_budget',
            entityType: 'Budget',
            entityId: $budget->id,
            newValues: [
                'kegiatan' => $budget->activity_name,
                'batas_anggaran' => (float) $budget->limit_amount,
            ]
        );

        return back()->with('success', 'Anggaran kegiatan berhasil ditambahkan.');
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $validated = $request->validate([
            'activity_name' => ['required', 'string', 'max:255'],
            'limit_amount' => ['required', 'numeric', 'min:1000'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = [
            'kegiatan' => $budget->activity_name,
            'batas_anggaran' => (float) $budget->limit_amount,
        ];

        $budget->update($validated);

        AuditLog::record(
            action: 'update_budget',
            entityType: 'Budget',
            entityId: $budget->id,
            oldValues: $oldValues,
            newValues: [
                'kegiatan' => $budget->activity_name,
                'batas_anggaran' => (float) $budget->limit_amount,
            ]
        );

        return back()->with('success', 'Anggaran kegiatan berhasil diperbarui.');
    }
}
