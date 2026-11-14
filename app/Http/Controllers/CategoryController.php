<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount(['transactions' => function ($q) {
            $q->where('status', 'active');
        }])->orderBy('type')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
        ]);

        $category = Category::create([
            'org_id' => $request->user()->currentOrgId(),
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        AuditLog::record(
            action: 'create_category',
            entityType: 'Category',
            entityId: $category->id,
            newValues: ['nama' => $category->name, 'tipe' => $category->type]
        );

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
        ]);

        $oldValues = ['nama' => $category->name, 'tipe' => $category->type];
        $category->update($validated);

        AuditLog::record(
            action: 'update_category',
            entityType: 'Category',
            entityId: $category->id,
            oldValues: $oldValues,
            newValues: ['nama' => $category->name, 'tipe' => $category->type]
        );

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }
}
