<?php

namespace App\Services;

use App\Models\Avis;
use App\Models\CommandeProduit;
use App\Models\Conversation;
use App\Models\CustomPrice;
use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\Facture;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\GdprRequest;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\ServiceAvis;
use App\Models\ProduitAvis;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GdprService
{
    /**
     * Délai de grâce par défaut (en jours) si non configuré
     */
    const DEFAULT_DELETION_DELAY_DAYS = 30;

    /**
     * Durée de validité du lien de téléchargement (en jours)
     */
    const EXPORT_LINK_EXPIRY_DAYS = 7;

    // =========================================================================
    // EXPORT DES DONNÉES
    // =========================================================================

    /**
     * Crée une demande d'export et génère le ZIP
     */
    public function requestExport(User $user, ?User $requestedBy = null, ?string $reason = null): GdprRequest
    {
        $gdprRequest = GdprRequest::create([
            'user_id' => $user->id,
            'requested_by' => $requestedBy?->id,
            'type' => GdprRequest::TYPE_EXPORT,
            'status' => GdprRequest::STATUS_PROCESSING,
            'reason' => $reason,
        ]);

        try {
            $zipPath = $this->generateExportZip($user);

            $gdprRequest->update([
                'status' => GdprRequest::STATUS_COMPLETED,
                'export_path' => $zipPath,
                'processed_at' => now(),
                'expires_at' => now()->addDays(self::EXPORT_LINK_EXPIRY_DAYS),
                'metadata' => [
                    'file_size' => Storage::disk('local')->size($zipPath),
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('RGPD Export échoué', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $gdprRequest->update([
                'status' => GdprRequest::STATUS_FAILED,
                'processed_at' => now(),
                'metadata' => ['error' => $e->getMessage()],
            ]);
        }

        return $gdprRequest;
    }

    /**
     * Génère le fichier ZIP contenant toutes les données de l'utilisateur
     */
    public function generateExportZip(User $user): string
    {
        $exportDir = 'gdpr_exports';
        $fileName = "export_user_{$user->id}_" . now()->format('Y-m-d_His') . '.zip';
        $relativePath = "{$exportDir}/{$fileName}";
        $absolutePath = storage_path("app/{$relativePath}");

        // Créer le répertoire si nécessaire
        Storage::disk('local')->makeDirectory($exportDir);

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Impossible de créer le fichier ZIP : {$absolutePath}");
        }

        // Collecter toutes les données
        $data = $this->collectUserData($user);

        // Ajouter les fichiers JSON
        foreach ($data['json'] as $filename => $content) {
            $zip->addFromString($filename, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Ajouter les fichiers physiques (photos, logos, etc.)
        foreach ($data['files'] as $archiveName => $diskPath) {
            $fullPath = storage_path("app/public/{$diskPath}");
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, "fichiers/{$archiveName}");
            }
        }

        // Générer et ajouter le PDF récapitulatif
        try {
            $pdfContent = $this->generateExportPdf($user, $data['json']);
            $zip->addFromString('resume.pdf', $pdfContent);
        } catch (\Throwable $e) {
            Log::warning('RGPD : Impossible de générer le PDF récapitulatif', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // On continue sans le PDF, ce n'est pas bloquant
        }

        $zip->close();

        return $relativePath;
    }

    /**
     * Collecte toutes les données d'un utilisateur pour l'export
     */
    public function collectUserData(User $user): array
    {
        $json = [];
        $files = [];

        // --- Profil utilisateur ---
        $json['profil.json'] = [
            'id' => $user->id,
            'nom' => $user->name,
            'prenom' => $user->surname,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'bio' => $user->bio,
            'date_naissance' => $user->date_naissance?->toDateString(),
            'adresse' => $user->adresse,
            'ville' => $user->ville,
            'code_postal' => $user->code_postal,
            'est_client' => $user->est_client,
            'est_gerant' => $user->est_gerant,
            'photo_profil' => $user->photo_profil,
            'tracking_consent' => $user->tracking_consent,
            'email_verifie_le' => $user->email_verified_at?->toIso8601String(),
            'compte_cree_le' => $user->created_at?->toIso8601String(),
            'derniere_mise_a_jour' => $user->updated_at?->toIso8601String(),
        ];

        // Photo de profil
        if ($user->photo_profil) {
            $files['photo_profil_' . basename($user->photo_profil)] = $user->photo_profil;
        }

        // --- Réservations ---
        $reservations = Reservation::where('user_id', $user->id)
            ->with(['entreprise:id,nom', 'typeService:id,nom'])
            ->get();
        $json['reservations.json'] = $reservations->map(fn ($r) => [
            'id' => $r->id,
            'entreprise' => $r->entreprise?->nom,
            'service' => $r->typeService?->nom ?? $r->type_service,
            'date_reservation' => $r->date_reservation?->toIso8601String(),
            'date_fin' => $r->date_fin?->toIso8601String(),
            'lieu' => $r->lieu,
            'prix' => $r->prix,
            'statut' => $r->statut,
            'est_paye' => $r->est_paye,
            'notes' => $r->notes,
            'duree_minutes' => $r->duree_minutes,
            'cree_le' => $r->created_at?->toIso8601String(),
        ])->toArray();

        // --- Factures ---
        $factures = Facture::where('user_id', $user->id)
            ->with(['entreprise:id,nom'])
            ->get();
        $json['factures.json'] = $factures->map(fn ($f) => [
            'id' => $f->id,
            'numero_facture' => $f->numero_facture,
            'entreprise' => $f->entreprise?->nom,
            'type_facture' => $f->type_facture,
            'date_facture' => $f->date_facture?->toDateString(),
            'date_echeance' => $f->date_echeance?->toDateString(),
            'montant_ht' => $f->montant_ht,
            'taux_tva' => $f->taux_tva,
            'montant_tva' => $f->montant_tva,
            'montant_ttc' => $f->montant_ttc,
            'statut' => $f->statut,
            'notes' => $f->notes,
            'cree_le' => $f->created_at?->toIso8601String(),
        ])->toArray();

        // --- Conversations + Messages ---
        $conversations = Conversation::where('user_id', $user->id)
            ->with(['messages', 'entreprise:id,nom'])
            ->get();
        $json['conversations.json'] = $conversations->map(fn ($c) => [
            'id' => $c->id,
            'entreprise' => $c->entreprise?->nom,
            'cree_le' => $c->created_at?->toIso8601String(),
            'messages' => $c->messages->map(fn ($m) => [
                'id' => $m->id,
                'contenu' => $m->contenu,
                'est_de_moi' => $m->user_id === $user->id,
                'type_message' => $m->type_message,
                'date' => $m->created_at?->toIso8601String(),
            ])->toArray(),
        ])->toArray();

        // --- Avis ---
        $avisData = [];
        $avis = Avis::where('user_id', $user->id)->with(['entreprise:id,nom'])->get();
        foreach ($avis as $a) {
            $avisData[] = [
                'type' => 'entreprise',
                'entreprise' => $a->entreprise?->nom,
                'note' => $a->note,
                'commentaire' => $a->commentaire,
                'date' => $a->created_at?->toIso8601String(),
            ];
        }
        if (class_exists(ServiceAvis::class)) {
            $serviceAvis = ServiceAvis::where('user_id', $user->id)->get();
            foreach ($serviceAvis as $sa) {
                $avisData[] = [
                    'type' => 'service',
                    'note' => $sa->note ?? null,
                    'commentaire' => $sa->commentaire ?? null,
                    'date' => $sa->created_at?->toIso8601String(),
                ];
            }
        }
        if (class_exists(ProduitAvis::class)) {
            $produitAvis = ProduitAvis::where('user_id', $user->id)->get();
            foreach ($produitAvis as $pa) {
                $avisData[] = [
                    'type' => 'produit',
                    'note' => $pa->note ?? null,
                    'commentaire' => $pa->commentaire ?? null,
                    'date' => $pa->created_at?->toIso8601String(),
                ];
            }
        }
        $json['avis.json'] = $avisData;

        // --- Forum ---
        $forumPosts = ForumPost::withTrashed()->where('user_id', $user->id)->get();
        $forumComments = ForumComment::withTrashed()->where('user_id', $user->id)->get();
        $json['forum.json'] = [
            'posts' => $forumPosts->map(fn ($p) => [
                'id' => $p->id,
                'titre' => $p->titre,
                'contenu' => $p->contenu,
                'date' => $p->created_at?->toIso8601String(),
            ])->toArray(),
            'commentaires' => $forumComments->map(fn ($c) => [
                'id' => $c->id,
                'contenu' => $c->contenu,
                'post_id' => $c->forum_post_id,
                'date' => $c->created_at?->toIso8601String(),
            ])->toArray(),
        ];

        // --- Feedback ---
        $feedbacks = Feedback::withTrashed()->where('user_id', $user->id)->get();
        $feedbackComments = FeedbackComment::withTrashed()->where('user_id', $user->id)->get();
        $feedbackVotes = FeedbackVote::where('user_id', $user->id)->get();
        $json['feedback.json'] = [
            'feedbacks' => $feedbacks->map(fn ($f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'description' => $f->description,
                'categorie' => $f->categorie,
                'statut' => $f->statut,
                'date' => $f->created_at?->toIso8601String(),
            ])->toArray(),
            'commentaires' => $feedbackComments->map(fn ($c) => [
                'id' => $c->id,
                'contenu' => $c->contenu,
                'feedback_id' => $c->feedback_id,
                'date' => $c->created_at?->toIso8601String(),
            ])->toArray(),
            'votes' => $feedbackVotes->map(fn ($v) => [
                'feedback_id' => $v->feedback_id,
                'date' => $v->created_at?->toIso8601String(),
            ])->toArray(),
        ];

        // --- Commandes produits ---
        $commandes = CommandeProduit::where('user_id', $user->id)
            ->with(['entreprise:id,nom', 'produit:id,nom'])
            ->get();
        $json['commandes.json'] = $commandes->map(fn ($c) => [
            'id' => $c->id,
            'entreprise' => $c->entreprise?->nom,
            'produit' => $c->produit?->nom,
            'quantite' => $c->quantite,
            'prix_total' => $c->prix_total,
            'statut' => $c->statut,
            'mode_livraison' => $c->mode_livraison,
            'date_commande' => $c->date_commande?->toIso8601String(),
            'cree_le' => $c->created_at?->toIso8601String(),
        ])->toArray();

        // --- Échéances ---
        $echeances = Echeance::where('user_id', $user->id)->get();
        $json['echeances.json'] = $echeances->map(fn ($e) => [
            'id' => $e->id,
            'montant_du' => $e->montant_du ?? null,
            'montant_final' => $e->montant_final ?? null,
            'statut' => $e->statut,
            'date_echeance' => $e->date_echeance ?? null,
            'cree_le' => $e->created_at?->toIso8601String(),
        ])->toArray();

        // --- Notifications ---
        $notifications = Notification::where('user_id', $user->id)->get();
        $json['notifications.json'] = $notifications->map(fn ($n) => [
            'id' => $n->id,
            'titre' => $n->titre ?? null,
            'message' => $n->message ?? null,
            'type' => $n->type ?? null,
            'est_lue' => $n->est_lue ?? false,
            'date' => $n->created_at?->toIso8601String(),
        ])->toArray();

        // --- Entreprises (si gérant) ---
        if ($user->est_gerant) {
            $entreprises = Entreprise::withTrashed()
                ->where('user_id', $user->id)
                ->with(['typesServices', 'produits', 'horairesOuverture', 'realisationPhotos'])
                ->get();

            $json['entreprises.json'] = $entreprises->map(function ($e) use (&$files) {
                // Collecter les fichiers de l'entreprise
                if ($e->logo) {
                    $files["logos/logo_{$e->id}_" . basename($e->logo)] = $e->logo;
                }
                if ($e->image_fond) {
                    $files["logos/image_fond_{$e->id}_" . basename($e->image_fond)] = $e->image_fond;
                }
                foreach ($e->realisationPhotos as $photo) {
                    if ($photo->chemin) {
                        $files["realisations/photo_{$photo->id}_" . basename($photo->chemin)] = $photo->chemin;
                    }
                }

                return [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'slug' => $e->slug,
                    'type_activite' => $e->type_activite,
                    'siren' => $e->siren,
                    'email' => $e->email,
                    'telephone' => $e->telephone,
                    'description' => $e->description,
                    'ville' => $e->ville,
                    'adresse_rue' => $e->adresse_rue,
                    'code_postal' => $e->code_postal,
                    'est_verifiee' => $e->est_verifiee,
                    'cree_le' => $e->created_at?->toIso8601String(),
                    'services' => $e->typesServices->map(fn ($s) => [
                        'nom' => $s->nom,
                        'prix' => $s->prix ?? null,
                        'duree_minutes' => $s->duree_minutes ?? null,
                    ])->toArray(),
                    'produits' => $e->produits->map(fn ($p) => [
                        'nom' => $p->nom,
                        'prix' => $p->prix ?? null,
                        'description' => $p->description ?? null,
                    ])->toArray(),
                ];
            })->toArray();
        }

        // --- Prix personnalisés ---
        $customPrices = CustomPrice::where('user_id', $user->id)->get();
        if ($customPrices->isNotEmpty()) {
            $json['prix_personnalises.json'] = $customPrices->map(fn ($cp) => [
                'id' => $cp->id,
                'montant' => $cp->montant ?? null,
                'description' => $cp->description ?? null,
                'cree_le' => $cp->created_at?->toIso8601String(),
            ])->toArray();
        }

        return ['json' => $json, 'files' => $files];
    }

    /**
     * Génère le PDF récapitulatif de l'export
     */
    protected function generateExportPdf(User $user, array $jsonData): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('gdpr.export-pdf', [
            'user' => $user,
            'data' => $jsonData,
            'generatedAt' => now(),
        ]);

        return $pdf->output();
    }

    // =========================================================================
    // SUPPRESSION / ANONYMISATION
    // =========================================================================

    /**
     * Crée une demande de suppression avec délai de grâce
     */
    public function requestDeletion(User $user, ?User $requestedBy = null, ?string $reason = null): GdprRequest
    {
        // Vérifier qu'il n'y a pas déjà une demande de suppression en attente
        $existingRequest = GdprRequest::forUser($user->id)
            ->where('type', GdprRequest::TYPE_DELETION)
            ->whereIn('status', [GdprRequest::STATUS_PENDING, GdprRequest::STATUS_PROCESSING])
            ->first();

        if ($existingRequest) {
            return $existingRequest;
        }

        $delayDays = $this->getDeletionDelayDays();

        $gdprRequest = GdprRequest::create([
            'user_id' => $user->id,
            'requested_by' => $requestedBy?->id,
            'type' => GdprRequest::TYPE_DELETION,
            'status' => GdprRequest::STATUS_PENDING,
            'reason' => $reason,
            'scheduled_at' => now()->addDays($delayDays),
            'metadata' => [
                'delay_days' => $delayDays,
                'user_email_at_request' => $user->email,
                'user_name_at_request' => $user->name,
                'is_gerant' => $user->est_gerant,
                'is_client' => $user->est_client,
                'entreprises_count' => $user->entreprises()->count(),
            ],
        ]);

        try {
            app(\App\Services\AdminNotificationService::class)->notifyGdprDeletionRequest($gdprRequest);
        } catch (\Throwable $e) {
            Log::warning('Notification admin RGPD: '.$e->getMessage());
        }

        return $gdprRequest;
    }

    /**
     * Annule une demande de suppression en attente
     */
    public function cancelDeletion(GdprRequest $request): bool
    {
        if (!$request->canBeCancelled()) {
            return false;
        }

        $request->update([
            'status' => GdprRequest::STATUS_CANCELLED,
            'processed_at' => now(),
        ]);

        return true;
    }

    /**
     * Exécute la suppression/anonymisation pour une demande
     */
    public function executeDeletion(GdprRequest $request): bool
    {
        if (!$request->isDeletion()) {
            return false;
        }

        $user = $request->user;
        if (!$user) {
            $request->update([
                'status' => GdprRequest::STATUS_FAILED,
                'processed_at' => now(),
                'metadata' => array_merge($request->metadata ?? [], ['error' => 'Utilisateur introuvable']),
            ]);
            return false;
        }

        $request->update(['status' => GdprRequest::STATUS_PROCESSING]);

        try {
            DB::beginTransaction();

            $stats = $this->anonymizeUser($user);

            DB::commit();

            $request->update([
                'status' => GdprRequest::STATUS_COMPLETED,
                'processed_at' => now(),
                'metadata' => array_merge($request->metadata ?? [], ['anonymization_stats' => $stats]),
            ]);

            Log::info('RGPD : Suppression/anonymisation effectuée', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'stats' => $stats,
            ]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('RGPD : Erreur lors de la suppression', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $request->update([
                'status' => GdprRequest::STATUS_FAILED,
                'processed_at' => now(),
                'metadata' => array_merge($request->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            return false;
        }
    }

    /**
     * Anonymise un utilisateur et gère toutes ses données liées
     * Retourne des statistiques sur les opérations effectuées
     */
    public function anonymizeUser(User $user): array
    {
        $stats = [];
        $userId = $user->id;

        // ===== 1. Suppression définitive des données sensibles =====

        // Sécurité & authentification
        $stats['security_logs'] = $user->securityLogs()->delete();
        $stats['login_attempts'] = $user->loginAttempts()->delete();
        $stats['ip_history'] = $user->ipHistory()->delete();
        $stats['two_factor_codes'] = $user->twoFactorCodes()->delete();
        $stats['trusted_devices'] = $user->trustedDevices()->delete();
        $stats['account_lockout'] = $user->accountLockout()?->delete() ? 1 : 0;
        $stats['password_reset_codes'] = $user->passwordResetCodes()->delete();
        $stats['email_verifications'] = $user->emailVerifications()->delete();
        $stats['recovery_code_usages'] = $user->recoveryCodeUsages()->delete();

        // Progression cours
        $stats['lesson_progress'] = $user->lessonProgress()->delete();
        $stats['module_progress'] = $user->moduleProgress()->delete();

        // Présence
        $stats['presence'] = $user->presence()?->delete() ? 1 : 0;

        // Notifications
        $stats['notifications'] = $user->notifications()->delete();

        // Prix personnalisés
        $stats['custom_prices'] = $user->customPrices()->delete();

        // Notes client (notes écrites PAR les gérants SUR ce client)
        $stats['client_notes'] = $user->clientNotes()->delete();

        // Votes feedback
        $stats['feedback_votes'] = FeedbackVote::where('user_id', $userId)->delete();

        // ===== 2. Anonymisation des données à conserver =====

        // Réservations : anonymiser les infos client mais garder pour l'entreprise
        $reservationsUpdated = Reservation::where('user_id', $userId)->update([
            'nom_client' => 'Client supprimé',
            'email_client' => null,
            'telephone_client' => null,
            'telephone_client_non_inscrit' => null,
            'notes' => null,
        ]);
        $stats['reservations_anonymized'] = $reservationsUpdated;

        // Commandes produits : anonymiser les infos client
        $commandesUpdated = CommandeProduit::where('user_id', $userId)->update([
            'nom_client' => 'Client supprimé',
            'email_client' => null,
            'telephone_client' => null,
            'telephone_client_non_inscrit' => null,
            'adresse_livraison' => null,
            'code_postal_livraison' => null,
            'ville_livraison' => null,
            'notes' => null,
        ]);
        $stats['commandes_anonymized'] = $commandesUpdated;

        // Avis : garder le contenu mais le user_id pointe vers l'user anonymisé
        // (pas besoin d'update car le user lui-même sera anonymisé)
        $stats['avis_count'] = Avis::where('user_id', $userId)->count();

        // Forum posts & comments : garder le contenu, auteur sera anonymisé via la relation
        $stats['forum_posts'] = ForumPost::withTrashed()->where('user_id', $userId)->count();
        $stats['forum_comments'] = ForumComment::withTrashed()->where('user_id', $userId)->count();

        // Feedback & comments : idem
        $stats['feedbacks'] = Feedback::withTrashed()->where('user_id', $userId)->count();
        $stats['feedback_comments'] = FeedbackComment::withTrashed()->where('user_id', $userId)->count();

        // Messages : contenu conservé, user_id pointe vers l'user anonymisé
        $stats['messages'] = Message::where('user_id', $userId)->count();

        // ===== 3. Gestion des entreprises (si gérant) =====

        if ($user->est_gerant) {
            $entreprises = Entreprise::withTrashed()->where('user_id', $userId)->get();
            $stats['entreprises_archived'] = 0;

            foreach ($entreprises as $entreprise) {
                // Soft delete l'entreprise si pas déjà fait
                if (!$entreprise->trashed()) {
                    $entreprise->delete();
                    $stats['entreprises_archived']++;
                }
            }
        }

        // ===== 4. Suppression des fichiers physiques =====

        $filesDeleted = 0;

        // Photo de profil
        if ($user->photo_profil) {
            $this->deleteStorageFile($user->photo_profil);
            $filesDeleted++;
        }

        // Fichiers d'entreprise (logos, photos de réalisations)
        if ($user->est_gerant) {
            $entreprises = Entreprise::withTrashed()->where('user_id', $userId)->get();
            foreach ($entreprises as $entreprise) {
                if ($entreprise->logo) {
                    $this->deleteStorageFile($entreprise->logo);
                    $filesDeleted++;
                }
                if ($entreprise->image_fond) {
                    $this->deleteStorageFile($entreprise->image_fond);
                    $filesDeleted++;
                }
                foreach ($entreprise->realisationPhotos as $photo) {
                    if ($photo->chemin) {
                        $this->deleteStorageFile($photo->chemin);
                        $filesDeleted++;
                    }
                }
            }
        }

        $stats['files_deleted'] = $filesDeleted;

        // ===== 5. Anonymisation du profil utilisateur =====

        $user->forceFill([
            'name' => 'Utilisateur supprimé',
            'surname' => null,
            'email' => "deleted_{$userId}@anonymized.local",
            'password' => bcrypt(Str::random(64)),
            'telephone' => null,
            'bio' => null,
            'date_naissance' => null,
            'adresse' => null,
            'ville' => null,
            'code_postal' => null,
            'photo_profil' => null,
            'statut_compte' => 'supprime',
            'tracking_consent' => false,
            'a2f_enabled' => false,
            'a2f_method' => null,
            'a2f_method_email' => false,
            'a2f_method_sms' => false,
            'recovery_method_email' => false,
            'recovery_method_sms' => false,
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
            'google2fa_recovery_codes' => null,
            'remember_token' => null,
            'stripe_payment_method_id' => null,
            'pm_type' => null,
            'pm_last_four' => null,
            // On conserve stripe_id et les données d'abonnement pour les factures
        ])->save();

        $stats['user_anonymized'] = true;

        return $stats;
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Récupère le délai de grâce configuré (en jours)
     */
    public function getDeletionDelayDays(): int
    {
        return (int) Setting::get('gdpr_deletion_delay_days', self::DEFAULT_DELETION_DELAY_DAYS);
    }

    /**
     * Met à jour le délai de grâce
     */
    public function setDeletionDelayDays(int $days): void
    {
        Setting::set('gdpr_deletion_delay_days', (string) max(0, $days), 'integer');
    }

    // =========================================================================
    // NETTOYAGE
    // =========================================================================

    /**
     * Supprime les exports expirés du disque
     */
    public function cleanupExpiredExports(): int
    {
        $expired = GdprRequest::where('type', GdprRequest::TYPE_EXPORT)
            ->where('status', GdprRequest::STATUS_COMPLETED)
            ->whereNotNull('export_path')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $request) {
            if ($request->export_path && Storage::disk('local')->exists($request->export_path)) {
                Storage::disk('local')->delete($request->export_path);
                $count++;
            }
            $request->update(['export_path' => null]);
        }

        return $count;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Supprime un fichier du storage public
     */
    protected function deleteStorageFile(string $path): void
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning("RGPD : Impossible de supprimer le fichier {$path}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
