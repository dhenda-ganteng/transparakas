<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Due;
use App\Models\Invitation;
use App\Models\JoinRequest;
use App\Models\Membership;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $membership = $user->membership()->with('organization')->firstOrFail();
        $role = $membership->role;
        $organization = $membership->organization;

        // 1. Dashboard Versi Admin Organisasi (Tanpa angka keuangan rinci - PRD Bagian 7 & 8.4)
        if ($role === 'admin_organisasi') {
            $memberCount = Membership::where('org_id', $organization->id)->where('status', 'active')->count();
            $inactiveMemberCount = Membership::where('org_id', $organization->id)->where('status', 'inactive')->count();
            $pendingJoinCount = JoinRequest::where('org_id', $organization->id)->where('status', 'pending')->count();
            $activeInvitation = Invitation::where('org_id', $organization->id)
                ->whereNull('revoked_at')
                ->latest()
                ->first();
            $recentLogs = AuditLog::with('user')->latest('created_at')->take(8)->get();

            return view('dashboard.admin', compact(
                'organization',
                'membership',
                'memberCount',
                'inactiveMemberCount',
                'pendingJoinCount',
                'activeInvitation',
                'recentLogs'
            ));
        }

        // Hitung ringkasan kas aktif untuk Bendahara, Auditor, dan Anggota
        $totalIncome = (float) Transaction::where('type', 'income')->where('status', 'active')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->where('status', 'active')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $month = max(1, min(12, (int) $request->input('month', now()->month)));
        $year = (int) $request->input('year', now()->year);
        $selectedDate = \Carbon\Carbon::createFromDate($year, $month, 1);

        $monthlyIncome = (float) Transaction::where('type', 'income')
            ->where('status', 'active')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('type', 'expense')
            ->where('status', 'active')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->sum('amount');

        // 2. Dashboard Versi Anggota (PRD 10.11 + Rincian Alokasi Kegiatan)
        if ($role === 'anggota') {
            $myDues = Due::where('user_id', $user->id)->latest()->get();
            $unpaidCount = $myDues->whereIn('status', ['unpaid', 'rejected'])->count();
            $upcomingDue = $myDues->whereIn('status', ['unpaid', 'rejected'])->first();
            $recentTransactions = $organization->visibility_policy === 'detail'
                ? Transaction::with('category')->where('status', 'active')->latest('transaction_date')->take(5)->get()
                : collect();

            $budgets = Budget::with(['transactions' => function ($q) {
                $q->where('type', 'expense')->where('status', 'active')->with('category')->latest('transaction_date');
            }])->latest()->get();

            return view('dashboard.member', compact(
                'organization',
                'membership',
                'balance',
                'monthlyIncome',
                'monthlyExpense',
                'myDues',
                'unpaidCount',
                'upcomingDue',
                'recentTransactions',
                'budgets',
                'month',
                'year'
            ));
        }

        // 3. Dashboard Versi Bendahara & Auditor (PRD 10.6)
        $unpaidDuesAmount = (float) Due::whereIn('status', ['unpaid', 'rejected'])->sum('amount');
        $pendingDues = Due::with('user')->where('status', 'pending')->latest()->take(5)->get();
        $pendingDuesCount = Due::where('status', 'pending')->count();
        $latestTransactions = Transaction::with(['category', 'creator'])
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();

        // Data Grafik Garis Arus Kas 6 Bulan Terakhir (mengikuti bulan & tahun yang dipilih)
        $chartMonths = [];
        $chartIncome = [];
        $chartExpense = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $selectedDate->copy()->subMonths($i);
            $chartMonths[] = $monthDate->translatedFormat('M Y');
            $chartIncome[] = (float) Transaction::where('type', 'income')
                ->where('status', 'active')
                ->whereYear('transaction_date', $monthDate->year)
                ->whereMonth('transaction_date', $monthDate->month)
                ->sum('amount');
            $chartExpense[] = (float) Transaction::where('type', 'expense')
                ->where('status', 'active')
                ->whereYear('transaction_date', $monthDate->year)
                ->whereMonth('transaction_date', $monthDate->month)
                ->sum('amount');
        }

        // Data Grafik Donat Pengeluaran per Kategori (mengikuti bulan & tahun yang dipilih)
        $expenseCategories = Category::withSum(['transactions as total_spent' => function ($q) use ($year, $month) {
                $q->where('status', 'active')
                  ->where('type', 'expense')
                  ->whereYear('transaction_date', $year)
                  ->whereMonth('transaction_date', $month);
            }], 'amount')
            ->get()
            ->filter(fn ($c) => (float) $c->total_spent > 0)
            ->values();

        $donutLabels = $expenseCategories->pluck('name')->all();
        $donutValues = $expenseCategories->map(fn ($c) => (float) $c->total_spent)->all();

        // Panel Anggaran Hampir Habis (>= 70%)
        $budgets = Budget::all()->filter(fn (Budget $b) => $b->usagePercentage() >= 70)->values();

        return view('dashboard.treasurer', compact(
            'organization',
            'membership',
            'balance',
            'monthlyIncome',
            'monthlyExpense',
            'unpaidDuesAmount',
            'pendingDues',
            'pendingDuesCount',
            'latestTransactions',
            'chartMonths',
            'chartIncome',
            'chartExpense',
            'donutLabels',
            'donutValues',
            'budgets',
            'month',
            'year'
        ));
    }
}
