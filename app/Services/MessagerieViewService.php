<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MessagerieViewService
{
    /**
     * Charge le même payload que l’ancienne page show (messages, RDV, catalogue).
     */
    public function hydrate(Conversation $conversation, User $user, bool $isGerant): array
    {
        $conversation->loadMissing(['user', 'entreprise']);

        $entreprise = $conversation->entreprise;

        if (Schema::hasColumn('conversations', 'reservation_id') && $conversation->reservation_id) {
            $conversation->load(['reservation.typeService', 'reservation.user']);
        }
        if ($conversation->produit_id) {
            $conversation->load(['produit.stock', 'produit.images', 'produit.imageCouverture', 'produit.promotionActive']);
        }
        if ($conversation->type_service_id) {
            $conversation->load(['typeService.images', 'typeService.imageCouverture']);
        }

        $messages = $conversation->messages()
            ->with(['user', 'propositionRdv.auteur', 'propositionRdv.entreprise', 'propositionRdv.reservation'])
            ->orderBy('created_at', 'asc')
            ->get();

        $propositionActive = $conversation->propositionRendezVousActive();
        if ($propositionActive) {
            $propositionActive->load(['user', 'entreprise.user', 'conversation.user', 'reservation']);
        }

        $prestations = $entreprise->typesServices()
            ->where('est_actif', true)
            ->with(['images', 'imageCouverture'])
            ->orderBy('nom')
            ->get();

        $produits = $entreprise->produits()
            ->where('est_actif', true)
            ->with(['stock', 'images', 'imageCouverture', 'promotionActive'])
            ->get()
            ->filter(fn ($produit) => $produit->estDisponible());

        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return [
            'conversation' => $conversation,
            'entreprise' => $entreprise,
            'messages' => $messages,
            'isGerant' => $isGerant,
            'propositionActive' => $propositionActive,
            'prestations' => $prestations,
            'produits' => $produits,
        ];
    }

    public function clientConversations(User $user, ?string $search = null): Collection
    {
        $query = Conversation::where('user_id', $user->id)
            ->where('est_archivee', false)
            ->with(['entreprise', 'dernierMessage.user']);

        if ($search) {
            $query->whereHas('entreprise', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('type_activite', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('dernier_message_at', 'desc')->get();
    }

    public function entrepriseConversations(Entreprise $entreprise, ?string $search = null): Collection
    {
        $query = Conversation::where('entreprise_id', $entreprise->id)
            ->where('est_archivee', false)
            ->with(['user', 'entreprise', 'dernierMessage.user', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($search) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('dernier_message_at')->orderByDesc('updated_at')->get();
    }
}
