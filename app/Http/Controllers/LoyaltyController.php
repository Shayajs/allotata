<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyProgram;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoyaltyController extends Controller
{
    /**
     * Afficher le programme de fidélité d'un client
     */
    public function show($slug, $userId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin && $user->id != $userId) {
            abort(403, 'Vous n\'avez pas accès à cette information.');
        }

        $loyaltyProgram = LoyaltyProgram::where('entreprise_id', $entreprise->id)
            ->where('user_id', $userId)
            ->with(['user', 'entreprise'])
            ->first();

        if (!$loyaltyProgram) {
            $loyaltyProgram = LoyaltyProgram::getOrCreate($entreprise->id, $userId);
        }

        $transactions = $loyaltyProgram->transactions()
            ->with('reservation')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'loyalty_program' => $loyaltyProgram,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Liste des clients avec leur programme de fidélité
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $loyaltyPrograms = LoyaltyProgram::where('entreprise_id', $entreprise->id)
            ->with('user')
            ->orderBy('total_points_earned', 'desc')
            ->get();

        return response()->json([
            'loyalty_programs' => $loyaltyPrograms,
        ]);
    }
}
