<?php

namespace App\Http\Controllers;

use App\Services\ManualSubscriptionService;
use Illuminate\Http\Request;

class ManualSubscriptionController extends Controller
{
    public function declarePaid(Request $request, ManualSubscriptionService $subscriptions)
    {
        $result = $subscriptions->declarePaid($request->user());

        return back()->with(
            $result === 'none' || $result === 'inactive' ? 'error' : 'success',
            match ($result) {
                'reactivated' => 'Votre paiement a été signalé. Votre abonnement manuel est de nouveau actif. L\'administration va le vérifier.',
                'declared' => 'Votre paiement a été signalé. Votre accès reste actif, l\'administration va vérifier cette échéance.',
                'already' => 'Votre déclaration est déjà enregistrée. Votre accès reste actif.',
                'inactive' => 'Cet abonnement n\'est pas actif. Contactez l\'administration si vous pensez qu\'il s\'agit d\'une erreur.',
                default => 'Aucune échéance manuelle à signaler pour le moment.',
            }
        );
    }
}