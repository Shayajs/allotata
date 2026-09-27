<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbonnementManuelEvenement;
use App\Models\AbonnementManuelPeriode;
use App\Services\ManualSubscriptionService;
use Illuminate\Http\Request;

class ManualSubscriptionController extends Controller
{
    public function index(ManualSubscriptionService $subscriptions)
    {
        $subscriptions->syncAllActive(true);

        $periodes = AbonnementManuelPeriode::query()
            ->needingReview()
            ->with(['user', 'confirmedBy', 'refusedBy'])
            ->orderBy('echeance_at')
            ->get();

        $events = AbonnementManuelEvenement::query()
            ->whereIn('user_id', $periodes->pluck('user_id')->unique())
            ->with('actor')
            ->latest()
            ->get()
            ->groupBy('user_id');

        return view('admin.manual-subscriptions.index', [
            'periodes' => $periodes,
            'events' => $events,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function confirm(Request $request, AbonnementManuelPeriode $periode, ManualSubscriptionService $subscriptions)
    {
        $result = $subscriptions->confirm($periode, $request->user());

        return back()->with(
            $result === 'confirmed' || $result === 'already' ? 'success' : 'error',
            match ($result) {
                'confirmed' => 'Paiement confirmé. L\'abonnement manuel reste actif.',
                'already' => 'Ce paiement était déjà confirmé.',
                'inactive' => 'Le membre n\'a plus d\'abonnement manuel actif. Le paiement n\'a pas été modifié.',
                default => 'Cette échéance ne peut pas être confirmée.',
            }
        );
    }

    public function refuse(Request $request, AbonnementManuelPeriode $periode, ManualSubscriptionService $subscriptions)
    {
        $result = $subscriptions->refuse($periode, $request->user());

        return back()->with(
            $result === 'refused' || $result === 'already' ? 'success' : 'error',
            match ($result) {
                'refused' => 'Paiement marqué non réalisé. L\'abonnement manuel est annulé et les avantages sont retirés.',
                'already' => 'Ce paiement était déjà marqué non réalisé.',
                default => 'Cette échéance ne peut pas être refusée.',
            }
        );
    }
}