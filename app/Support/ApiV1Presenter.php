<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Entreprise;
use App\Models\EntrepriseFinance;
use App\Models\Facture;
use App\Models\Message;
use App\Models\Produit;
use App\Models\Reservation;
use App\Models\TypeService;

/**
 * Forme des objets renvoyes par l'API v1.
 *
 * Tout est regroupe ici pour une raison simple : c'est un contrat public. Une
 * cle qui disparait casse les integrations, donc les formes doivent se lire
 * cote a cote et bouger le moins possible.
 */
final class ApiV1Presenter
{
    /**
     * @return array<string, mixed>
     */
    public static function entreprise(Entreprise $entreprise): array
    {
        return [
            'id' => $entreprise->id,
            'slug' => $entreprise->slug,
            'nom' => $entreprise->nom,
            'type_activite' => $entreprise->type_activite,
            'ville' => $entreprise->ville,
            'code_postal' => $entreprise->code_postal,
            'est_verifiee' => (bool) $entreprise->est_verifiee,
            'url_publique' => route('public.entreprise', $entreprise->slug),
            'creee_le' => $entreprise->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function entrepriseDetaillee(Entreprise $entreprise): array
    {
        return array_merge(self::entreprise($entreprise), [
            'description' => $entreprise->description,
            'phrase_accroche' => $entreprise->phrase_accroche,
            'contact' => [
                'email' => $entreprise->email,
                'telephone' => $entreprise->telephone,
                'site_web_externe' => $entreprise->site_web_externe,
            ],
            'adresse' => [
                'rue' => $entreprise->adresse_rue,
                'code_postal' => $entreprise->code_postal,
                'ville' => $entreprise->ville,
                'latitude' => $entreprise->latitude !== null ? (float) $entreprise->latitude : null,
                'longitude' => $entreprise->longitude !== null ? (float) $entreprise->longitude : null,
                'type_localisation' => $entreprise->type_localisation,
                'rayon_deplacement' => $entreprise->rayon_deplacement,
            ],
            'reservation' => [
                'acceptation_automatique' => (bool) $entreprise->accepter_reservations_auto,
                'intervalle_creneaux_minutes' => $entreprise->resolveIntervalleCreneauxMinutes(),
                'uniquement_par_messagerie' => (bool) $entreprise->rdv_uniquement_messagerie,
                'prix_negociables' => (bool) $entreprise->prix_negociables,
            ],
            'facturation' => [
                'status_juridique' => $entreprise->status_juridique,
                'siret' => $entreprise->siret,
                'assujetti_tva' => (bool) $entreprise->assujetti_tva,
                'taux_tva_defaut' => $entreprise->taux_tva_defaut !== null ? (float) $entreprise->taux_tva_defaut : null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function reservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'entreprise_id' => $reservation->entreprise_id,
            'entreprise_slug' => $reservation->entreprise?->slug,
            'entreprise_nom' => $reservation->entreprise?->nom,
            'reference' => $reservation->hash_alias,
            'statut' => $reservation->statut,
            'date_debut' => $reservation->date_reservation?->toIso8601String(),
            'date_fin' => $reservation->date_fin?->toIso8601String(),
            'date_butoire' => $reservation->date_butoire?->toDateString(),
            'duree_minutes' => $reservation->duree_minutes,
            'lieu' => $reservation->lieu,
            'notes' => $reservation->notes,
            'creee_manuellement' => (bool) $reservation->creee_manuellement,
            'service' => [
                'id' => $reservation->type_service_id,
                'nom' => $reservation->type_service,
            ],
            'client' => [
                'utilisateur_id' => $reservation->user_id,
                'nom' => $reservation->nom_client_complet,
                'email' => $reservation->email_client_complet,
                'telephone' => $reservation->telephone_client ?: $reservation->telephone_client_non_inscrit,
                'inscrit' => $reservation->user_id !== null,
            ],
            'membre_id' => $reservation->membre_id,
            'paiement' => [
                'prix' => (float) $reservation->prix,
                'est_paye' => (bool) $reservation->est_paye,
                'date_paiement' => $reservation->date_paiement?->toIso8601String(),
            ],
            'creee_le' => $reservation->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function service(TypeService $service): array
    {
        return [
            'id' => $service->id,
            'nom' => $service->nom,
            'description' => $service->description,
            'prix' => $service->prix !== null ? (float) $service->prix : null,
            'prix_par_personne' => (bool) $service->est_prix_par_personne,
            'duree_minutes' => $service->duree_minutes,
            'type_structure' => $service->type_structure,
            'capacite_max' => $service->capacite_max,
            'est_actif' => (bool) $service->est_actif,
            'ordre_affichage' => $service->ordre_affichage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function produit(Produit $produit): array
    {
        return [
            'id' => $produit->id,
            'nom' => $produit->nom,
            'description' => $produit->description,
            'prix' => $produit->prix !== null ? (float) $produit->prix : null,
            'est_actif' => (bool) $produit->est_actif,
            'gestion_stock' => $produit->gestion_stock,
            'livraison_disponible' => (bool) $produit->livraison_disponible,
            'vente_sur_place_disponible' => (bool) $produit->vente_sur_place_disponible,
            'ordre_affichage' => $produit->ordre_affichage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function finance(EntrepriseFinance $ecriture): array
    {
        return [
            'id' => $ecriture->id,
            'type' => $ecriture->type,
            'categorie' => $ecriture->category,
            'montant' => (float) $ecriture->amount,
            'description' => $ecriture->description,
            'date' => $ecriture->date_record?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function facture(Facture $facture): array
    {
        return [
            'id' => $facture->id,
            'numero' => $facture->numero_facture,
            'type' => $facture->type_facture,
            'statut' => $facture->statut,
            'montant_ttc' => $facture->montant_ttc !== null ? (float) $facture->montant_ttc : null,
            'date_facture' => $facture->date_facture?->toDateString(),
            'entreprise_id' => $facture->entreprise_id,
            'entreprise_nom' => $facture->entreprise?->nom,
            'visible_client' => $facture->estVisibleParClient(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conversation(Conversation $conversation, int $viewerId): array
    {
        $dernier = $conversation->dernierMessage;

        return [
            'id' => $conversation->id,
            'entreprise_id' => $conversation->entreprise_id,
            'entreprise_nom' => $conversation->entreprise?->nom,
            'client_nom' => $conversation->user?->name,
            'updated_at' => ($conversation->dernier_message_at ?? $conversation->updated_at)?->toIso8601String(),
            'dernier_message' => $dernier?->contenu,
            'non_lus' => $conversation->messages
                ? $conversation->messages->where('user_id', '!=', $viewerId)->where('est_lu', false)->count()
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function message(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'auteur' => $message->user?->name,
            'contenu' => $message->contenu,
            'image' => $message->image,
            'est_lu' => (bool) $message->est_lu,
            'cree_le' => $message->created_at?->toIso8601String(),
        ];
    }
}
