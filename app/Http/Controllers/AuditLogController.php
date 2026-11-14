<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->currentOrgId();

        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $logs = $query->latest('created_at')->latest('id')->paginate(15)->withQueryString();

        $members = Membership::with('user')
            ->where('org_id', $orgId)
            ->get();

        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('audit-logs.index', compact('logs', 'members', 'actions'));
    }
}
