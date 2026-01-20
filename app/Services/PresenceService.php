<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPresence;
use App\Events\UserPresenceChanged;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PresenceService
{
    /**
     * Met à jour l'activité d'un utilisateur
     */
    public function updateActivity(User $user): UserPresence
    {
        $lastActivityAt = now();
        $oldPresence = UserPresence::where('user_id', $user->id)->first();
        $oldStatus = $oldPresence?->status;

        $presence = UserPresence::updateOrCreateForUser($user->id, $lastActivityAt);
        
        // Si le statut a changé, émettre un événement
        if ($oldStatus !== $presence->status) {
            try {
                event(new UserPresenceChanged($user, $presence->status, $lastActivityAt));
            } catch (\Exception $e) {
                // Si le broadcasting échoue (Reverb non démarré, etc.), on continue quand même
                Log::warning('Erreur lors du broadcasting de la présence : ' . $e->getMessage());
            }
        }

        return $presence;
    }

    /**
     * Retourne le statut actuel d'un utilisateur
     */
    public function getStatus(User $user): string
    {
        $presence = UserPresence::where('user_id', $user->id)->first();
        
        if (!$presence) {
            return 'offline';
        }

        // Recalculer le statut basé sur la dernière activité
        $status = UserPresence::determineStatus($presence->last_activity_at);
        
        // Si le statut calculé est différent, mettre à jour
        if ($status !== $presence->status) {
            $oldStatus = $presence->status;
            $presence->update(['status' => $status]);
            
            if ($oldStatus !== $status) {
                try {
                    event(new UserPresenceChanged($user, $status, $presence->last_activity_at));
                } catch (\Exception $e) {
                    // Si le broadcasting échoue (Reverb non démarré, etc.), on continue quand même
                    Log::warning('Erreur lors du broadcasting de la présence : ' . $e->getMessage());
                }
            }
        }

        return $status;
    }

    /**
     * Marque un utilisateur comme en ligne
     */
    public function markAsOnline(User $user): UserPresence
    {
        $oldPresence = UserPresence::where('user_id', $user->id)->first();
        $oldStatus = $oldPresence?->status;

        $presence = UserPresence::markAsOnline($user->id);
        
        if ($oldStatus !== 'online') {
            try {
                event(new UserPresenceChanged($user, 'online', $presence->last_activity_at));
            } catch (\Exception $e) {
                Log::warning('Erreur lors du broadcasting de la présence : ' . $e->getMessage());
            }
        }

        return $presence;
    }

    /**
     * Marque un utilisateur comme inactif
     */
    public function markAsIdle(User $user): UserPresence
    {
        $oldPresence = UserPresence::where('user_id', $user->id)->first();
        $oldStatus = $oldPresence?->status;

        $presence = UserPresence::markAsIdle($user->id);
        
        if ($oldStatus !== 'idle') {
            try {
                event(new UserPresenceChanged($user, 'idle', $presence->last_activity_at));
            } catch (\Exception $e) {
                Log::warning('Erreur lors du broadcasting de la présence : ' . $e->getMessage());
            }
        }

        return $presence;
    }

    /**
     * Marque un utilisateur comme déconnecté
     */
    public function markAsOffline(User $user): UserPresence
    {
        $oldPresence = UserPresence::where('user_id', $user->id)->first();
        $oldStatus = $oldPresence?->status;

        $presence = UserPresence::markAsOffline($user->id);
        
        if ($oldStatus !== 'offline') {
            try {
                event(new UserPresenceChanged($user, 'offline', $presence->last_activity_at));
            } catch (\Exception $e) {
                Log::warning('Erreur lors du broadcasting de la présence : ' . $e->getMessage());
            }
        }

        return $presence;
    }

    /**
     * Récupère les statuts de plusieurs utilisateurs
     */
    public function getStatusesForUsers(array $userIds): array
    {
        $presences = UserPresence::whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $statuses = [];

        foreach ($userIds as $userId) {
            $presence = $presences->get($userId);
            if ($presence) {
                // Recalculer le statut
                $status = UserPresence::determineStatus($presence->last_activity_at);
                $statuses[$userId] = [
                    'status' => $status,
                    'last_activity_at' => $presence->last_activity_at?->toIso8601String(),
                ];
            } else {
                $statuses[$userId] = [
                    'status' => 'offline',
                    'last_activity_at' => null,
                ];
            }
        }

        return $statuses;
    }

    /**
     * Nettoie les entrées de présence obsolètes
     */
    public function cleanup(int $minutesOld = 10): int
    {
        $cutoff = now()->subMinutes($minutesOld);
        
        return UserPresence::where('status', 'offline')
            ->where('last_activity_at', '<', $cutoff)
            ->delete();
    }
}
