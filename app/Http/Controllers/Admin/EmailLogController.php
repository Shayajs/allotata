<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailLogController extends Controller
{
    /**
     * Afficher la liste des logs emails
     */
    public function index(Request $request)
    {
        $query = EmailLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('recipient_email')) {
            $query->where('recipient_email', 'like', '%' . $request->recipient_email . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $logs = $query->paginate(50);

        // Statistiques
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::where('status', 'sent')->count(),
            'failed' => EmailLog::where('status', 'failed')->count(),
            'pending' => EmailLog::where('status', 'pending')->count(),
            'verification' => EmailLog::where('type', 'verification')->count(),
            'password_reset' => EmailLog::where('type', 'password_reset')->count(),
        ];

        return view('admin.email-logs.index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['status', 'recipient_email', 'type', 'date_debut', 'date_fin']),
        ]);
    }

    /**
     * Vérifier manuellement l'email d'un utilisateur
     */
    public function verifyUserEmail(Request $request, User $user)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($user->hasVerifiedEmail()) {
            return back()->with('error', 'Cet utilisateur a déjà son email vérifié.');
        }

        DB::transaction(function () use ($user, $request) {
            // Marquer l'email comme vérifié
            $user->markEmailAsVerified();

            // Marquer tous les hash de vérification comme utilisés
            \App\Models\EmailVerification::where('user_id', $user->id)
                ->where('is_used', false)
                ->update(['is_used' => true]);

            // Logger l'action
            \App\Models\SecurityLog::log(
                $user->id,
                'email_verified_manually',
                $request->ip(),
                $request->userAgent(),
                null,
                [
                    'verified_by' => auth()->id(),
                    'verified_by_name' => auth()->user()->name,
                    'reason' => $request->reason,
                ],
                'medium',
                false,
                'Email vérifié manuellement par un administrateur' . ($request->reason ? ' : ' . $request->reason : '')
            );
        });

        return back()->with('success', "L'email de {$user->email} a été vérifié manuellement avec succès.");
    }
}
