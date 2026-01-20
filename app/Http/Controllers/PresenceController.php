<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    protected $presenceService;

    public function __construct(PresenceService $presenceService)
    {
        $this->presenceService = $presenceService;
    }

    /**
     * Endpoint pour les heartbeats périodiques depuis le frontend
     */
    public function heartbeat(Request $request)
    {
        $user = Auth::user();
        $presence = $this->presenceService->updateActivity($user);

        return response()->json([
            'status' => $presence->status,
            'last_activity_at' => $presence->last_activity_at?->toIso8601String(),
        ]);
    }

    /**
     * Liste des utilisateurs avec leur statut
     * Accessible pour les admins ou pour les membres d'une équipe
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Si admin, retourner tous les utilisateurs
        if ($user->is_admin) {
            $users = User::where('statut_compte', '!=', 'supprime')
                ->orderBy('name')
                ->get();
        } else {
            // Sinon, retourner seulement les utilisateurs de l'équipe de l'entreprise
            // ou les utilisateurs avec qui l'utilisateur a des conversations
            $userIds = collect([$user->id]);
            
            // Ajouter les membres de l'équipe si l'utilisateur est gérant
            if ($user->est_gerant) {
                $entrepriseIds = $user->entreprises()->pluck('id');
                $membreIds = \App\Models\EntrepriseMembre::whereIn('entreprise_id', $entrepriseIds)
                    ->pluck('user_id');
                $userIds = $userIds->merge($membreIds);
            }
            
            // Ajouter les utilisateurs avec qui l'utilisateur a des conversations
            $conversationUserIds = \App\Models\Conversation::where('user_id', $user->id)
                ->orWhereHas('messages', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->pluck('user_id');
            $userIds = $userIds->merge($conversationUserIds);
            
            $users = User::whereIn('id', $userIds->unique())
                ->where('statut_compte', '!=', 'supprime')
                ->orderBy('name')
                ->get();
        }

        $userIds = $users->pluck('id')->toArray();
        $statuses = $this->presenceService->getStatusesForUsers($userIds);

        $usersWithStatus = $users->map(function($user) use ($statuses) {
            $status = $statuses[$user->id] ?? ['status' => 'offline', 'last_activity_at' => null];
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_profil' => $user->photo_profil,
                'status' => $status['status'],
                'last_activity_at' => $status['last_activity_at'],
            ];
        });

        return response()->json($usersWithStatus);
    }

    /**
     * Statut d'un utilisateur spécifique
     */
    public function show($userId)
    {
        $user = User::findOrFail($userId);
        $status = $this->presenceService->getStatus($user);
        $presence = $user->presence;

        return response()->json([
            'user_id' => $user->id,
            'status' => $status,
            'last_activity_at' => $presence?->last_activity_at?->toIso8601String(),
        ]);
    }
}
