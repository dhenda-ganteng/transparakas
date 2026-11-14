<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $membership = $request->user()->membership()->with('organization')->firstOrFail();
        $organization = $membership->organization;

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

        $netPeriod = $monthlyIncome - $monthlyExpense;

        $totalAllIncome = (float) Transaction::where('type', 'income')->where('status', 'active')->sum('amount');
        $totalAllExpense = (float) Transaction::where('type', 'expense')->where('status', 'active')->sum('amount');
        $currentBalance = $totalAllIncome - $totalAllExpense;

        // Rekap per kategori pada bulan & tahun terpilih
        $categoryRecap = Category::withSum(['transactions as period_total' => function ($q) use ($month, $year) {
            $q->where('status', 'active')
              ->whereYear('transaction_date', $year)
              ->whereMonth('transaction_date', $month);
        }], 'amount')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => (float) $c->period_total > 0)
            ->values();

        // Rincian arus kas tiap bulan (Jan-Des) pada tahun terpilih
        $monthlyBreakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $inc = (float) Transaction::where('type', 'income')
                ->where('status', 'active')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $m)
                ->sum('amount');
            $exp = (float) Transaction::where('type', 'expense')
                ->where('status', 'active')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $m)
                ->sum('amount');

            $monthlyBreakdown[] = [
                'month_num' => $m,
                'income' => $inc,
                'expense' => $exp,
                'net' => $inc - $exp,
            ];
        }

        // Alokasi dana per kegiatan / acara (keseluruhan & rincian pos pengeluarannya)
        $budgetAllocations = Budget::with(['transactions' => function ($q) {
            $q->where('type', 'expense')->where('status', 'active')->with('category')->latest('transaction_date');
        }])->latest()->get();

        return view('reports.index', compact(
            'organization',
            'membership',
            'month',
            'year',
            'monthlyIncome',
            'monthlyExpense',
            'netPeriod',
            'currentBalance',
            'categoryRecap',
            'monthlyBreakdown',
            'budgetAllocations'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $transactions = Transaction::with(['category', 'creator'])
            ->where('status', 'active')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date')
            ->get();

        $filename = "laporan_kas_{$year}_{$month}.csv";

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM + pemisah titik koma (;) agar langsung rapi per kolom di Excel Indonesia
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Tanggal', 'Keterangan', 'Kategori', 'Tipe', 'Nominal (Rp)', 'Dicatat Oleh'], ';');

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->description,
                    $tx->category?->name ?? '-',
                    $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    (int) $tx->amount,
                    $tx->creator?->name ?? '-',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
