<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Consultation du journal d'audit des paiements (verbose, traçabilité légale).
 */
class PaymentAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentAuditLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('stripe_payment_intent_id')) {
            $query->where('stripe_payment_intent_id', 'like', '%' . $request->stripe_payment_intent_id . '%');
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(100)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.payment-audit-log.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => [
                'setup_intent_created',
                'save_pm_ok',
                'save_pm_fail',
                'charge_ok',
                'charge_fail',
                'charge_3ds',
                'confirm_status_ok',
                'confirm_status_fail',
            ],
        ]);
    }
}
