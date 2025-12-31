<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\TempAdminController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MessagerieController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StorageController;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Route pour servir les fichiers storage via un contrôleur
// Cela permet un meilleur contrôle et évite les problèmes de permissions du serveur web
Route::get('/storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

// Webhook Stripe (doit être en dehors du middleware auth et sans CSRF)
Route::post(
    '/stripe/webhook',
    '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook'
)->name('cashier.webhook');

// ⚠️ PAGE TEMPORAIRE - ADMINISTRATION (À SUPPRIMER EN PRODUCTION)
Route::prefix('temp-admin')->name('temp-admin.')->group(function () {
    Route::get('/', [TempAdminController::class, 'index'])->name('index');
    Route::post('/create-admin', [TempAdminController::class, 'createAdmin'])->name('create-admin');
    Route::post('/promote/{user}', [TempAdminController::class, 'promoteToAdmin'])->name('promote');
    Route::post('/demote/{user}', [TempAdminController::class, 'demoteFromAdmin'])->name('demote');
    Route::post('/login-as/{user}', [TempAdminController::class, 'loginAs'])->name('login-as');
});

// Recherche
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/api/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');

// Inscription (Signup)
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('register');

// Connexion (Signin)
Route::get('/signin', [AuthController::class, 'showSignin'])->name('login');
Route::post('/signin', [AuthController::class, 'login']);

// Déconnexion
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Entreprise (Public)
Route::get("/p/{slug}", [PublicController::class, 'show'])->name('public.entreprise');
Route::get("/p/{slug}/agenda", [PublicController::class, 'agenda'])->name('public.agenda');
Route::get("/p/{slug}/agenda/reservations", [PublicController::class, 'getReservations'])->name('public.agenda.reservations');
Route::post("/p/{slug}/reservation", [PublicController::class, 'storeReservation'])->name('public.reservation.store');

// Avis (nécessite authentification)
Route::middleware('auth')->group(function () {
    Route::get("/p/{slug}/avis/create", [AvisController::class, 'create'])->name('avis.create');
    Route::post("/p/{slug}/avis", [AvisController::class, 'store'])->name('avis.store');
    Route::put("/p/{slug}/avis/{id}", [AvisController::class, 'update'])->name('avis.update');
});

// Routes protégées
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/reservation/{reservation}/marquer-payee', [DashboardController::class, 'marquerPayee'])->name('dashboard.reservation.marquer-payee');
    
    // Création d'entreprise
    Route::get('/entreprise/create', [EntrepriseController::class, 'create'])->name('entreprise.create');
    Route::post('/entreprise', [EntrepriseController::class, 'store'])->name('entreprise.store');
    
    // Gestion de l'agenda (pour les gérants)
    Route::get('/m/{slug}/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/m/{slug}/agenda/service', [AgendaController::class, 'index'])->name('agenda.service.index');
    Route::get('/m/{slug}/agenda/reservations', [AgendaController::class, 'getReservations'])->name('agenda.reservations');
    Route::post('/m/{slug}/agenda/horaires', [AgendaController::class, 'storeHoraires'])->name('agenda.horaires.store');
    Route::post('/m/{slug}/agenda/service', [AgendaController::class, 'storeTypeService'])->name('agenda.service.store');
    Route::delete('/m/{slug}/agenda/service/{typeServiceId}', [AgendaController::class, 'deleteTypeService'])->name('agenda.service.delete');
    Route::post('/m/{slug}/agenda/jour-exceptionnel', [AgendaController::class, 'storeJourExceptionnel'])->name('agenda.jour-exceptionnel.store');
    Route::delete('/m/{slug}/agenda/jour-exceptionnel/{horaireId}', [AgendaController::class, 'deleteJourExceptionnel'])->name('agenda.jour-exceptionnel.delete');
    
    // Gestion des réservations (pour les gérants)
    Route::get('/m/{slug}/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/m/{slug}/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/m/{slug}/reservations/{id}/accept', [ReservationController::class, 'accept'])->name('reservations.accept');
    Route::post('/m/{slug}/reservations/{id}/reject', [ReservationController::class, 'reject'])->name('reservations.reject');
    Route::post('/m/{slug}/reservations/{id}/notes', [ReservationController::class, 'addNotes'])->name('reservations.notes');
    Route::post('/m/{slug}/reservations/{id}/marquer-payee', [ReservationController::class, 'marquerPayee'])->name('reservations.marquer-payee');
    
    // Paramètres
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::post('/settings/error-notifications', [SettingsController::class, 'updateErrorNotifications'])->name('settings.error-notifications.update');
    Route::post('/settings/entreprise/{slug}', [SettingsController::class, 'updateEntreprise'])->name('settings.entreprise.update');
    Route::post('/settings/entreprise/{slug}/logo/upload', [SettingsController::class, 'uploadLogo'])->name('settings.entreprise.logo.upload');
    Route::post('/settings/entreprise/{slug}/image-fond/upload', [SettingsController::class, 'uploadImageFond'])->name('settings.entreprise.image-fond.upload');
    Route::delete('/settings/entreprise/{slug}/logo', [SettingsController::class, 'deleteLogo'])->name('settings.entreprise.logo.delete');
    Route::delete('/settings/entreprise/{slug}/image-fond', [SettingsController::class, 'deleteImageFond'])->name('settings.entreprise.image-fond.delete');
    Route::post('/settings/entreprise/{slug}/photo', [SettingsController::class, 'addRealisationPhoto'])->name('settings.entreprise.photo.add');
    Route::delete('/settings/entreprise/{slug}/photo/{photoId}', [SettingsController::class, 'deleteRealisationPhoto'])->name('settings.entreprise.photo.delete');
    
    // Factures
    Route::get('/factures', [FactureController::class, 'index'])->name('factures.index');
    Route::get('/factures/{id}', [FactureController::class, 'show'])->name('factures.show');
    Route::get('/factures/{id}/download', [FactureController::class, 'download'])->name('factures.download');
    Route::get('/m/{slug}/factures', [FactureController::class, 'indexEntreprise'])->name('factures.entreprise');
    Route::get('/m/{slug}/factures/{id}', [FactureController::class, 'showEntreprise'])->name('factures.entreprise.show');
    Route::get('/m/{slug}/factures/{id}/download', [FactureController::class, 'downloadEntreprise'])->name('factures.entreprise.download');
    Route::get('/m/{slug}/comptabilite', [FactureController::class, 'comptabilite'])->name('factures.comptabilite');
    Route::get('/m/{slug}/factures/create-groupee', [FactureController::class, 'createGroupee'])->name('factures.create-groupee');
    Route::get('/m/{slug}/factures/reservations', [FactureController::class, 'getReservationsPourFactureGroupee'])->name('factures.reservations');
    Route::post('/m/{slug}/factures/groupee', [FactureController::class, 'storeGroupee'])->name('factures.store-groupee');
    
    // Messagerie
    Route::get('/messagerie', [MessagerieController::class, 'index'])->name('messagerie.index');
    Route::get('/messagerie/{slug}', [MessagerieController::class, 'show'])->name('messagerie.show');
    Route::post('/messagerie/{slug}', [MessagerieController::class, 'sendMessage'])->name('messagerie.send');
    Route::get('/m/{slug}/messagerie/{conversationId}', [MessagerieController::class, 'showGerant'])->name('messagerie.show-gerant');
    Route::post('/m/{slug}/messagerie/{conversationId}', [MessagerieController::class, 'sendMessageGerant'])->name('messagerie.send-gerant');
    
    // API Messagerie
    Route::get('/api/messagerie/check-new', [MessagerieController::class, 'checkNewMessages'])->name('messagerie.api.check-new');
    
    // Propositions de rendez-vous
    Route::post('/messagerie/{slug}/proposer-rdv', [MessagerieController::class, 'proposerRendezVousClient'])->name('messagerie.proposer-rdv-client');
    Route::post('/m/{slug}/messagerie/{conversationId}/proposer-rdv', [MessagerieController::class, 'proposerRendezVous'])->name('messagerie.proposer-rdv');
    Route::post('/messagerie/{slug}/negocier-prix/{propositionId}', [MessagerieController::class, 'negocierPrix'])->name('messagerie.negocier-prix');
    Route::post('/messagerie/{slug}/accepter-proposition/{propositionId}', [MessagerieController::class, 'accepterProposition'])->name('messagerie.accepter-proposition');
    Route::post('/messagerie/{slug}/refuser-proposition/{propositionId}', [MessagerieController::class, 'refuserProposition'])->name('messagerie.refuser-proposition');
    Route::post('/m/{slug}/messagerie/{conversationId}/accepter-proposition/{propositionId}', [MessagerieController::class, 'accepterProposition'])->name('messagerie.accepter-proposition-gerant');
    Route::post('/m/{slug}/messagerie/{conversationId}/refuser-proposition/{propositionId}', [MessagerieController::class, 'refuserProposition'])->name('messagerie.refuser-proposition-gerant');
    
    // Abonnements
        Route::get('/abonnement', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/abonnement/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::get('/abonnement/success', [SubscriptionController::class, 'success'])->name('subscription.success');
        Route::post('/abonnement/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('/abonnement/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
        Route::get('/abonnement/facture/{invoiceId}/download', [SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice.download');
        Route::get('/abonnement/invoice/{invoiceId}/download', [SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice.download');
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/{id}/lue', [NotificationController::class, 'marquerLue'])->name('notifications.marquer-lue');
        Route::post('/notifications/toutes-lues', [NotificationController::class, 'marquerToutesLues'])->name('notifications.marquer-toutes-lues');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// Routes administrateur
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Routes pour les erreurs (notifications en temps réel)
    Route::get('/errors', [\App\Http\Controllers\ErrorLogController::class, 'index'])->name('errors.index');
    Route::post('/errors/{id}/read', [\App\Http\Controllers\ErrorLogController::class, 'markAsRead'])->name('errors.mark-read');
    Route::post('/errors/mark-all-read', [\App\Http\Controllers\ErrorLogController::class, 'markAllAsRead'])->name('errors.mark-all-read');
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    
    // Gestion des entreprises
    Route::get('/entreprises', [AdminController::class, 'entreprises'])->name('entreprises.index');
    Route::get('/entreprises/{entreprise}', [AdminController::class, 'showEntreprise'])->name('entreprises.show');
    Route::post('/entreprises/{entreprise}/verify', [AdminController::class, 'verifyEntreprise'])->name('entreprises.verify');
    Route::post('/entreprises/{entreprise}/unverify', [AdminController::class, 'unverifyEntreprise'])->name('entreprises.unverify');
    
    // Gestion des réservations
    Route::get('/reservations', [AdminController::class, 'reservations'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [AdminController::class, 'showReservation'])->name('reservations.show');
    Route::post('/reservations/{reservation}/paid', [AdminController::class, 'markReservationPaid'])->name('reservations.mark-paid');
    
    // Vérification SIREN
    Route::post('/entreprises/{entreprise}/verify-siren', [AdminController::class, 'verifySiren'])->name('entreprises.verify-siren');
    Route::post('/entreprises/{entreprise}/unverify-siren', [AdminController::class, 'unverifySiren'])->name('entreprises.unverify-siren');
    Route::post('/entreprises/{entreprise}/validate-nom', [AdminController::class, 'validateNom'])->name('entreprises.validate-nom');
    Route::post('/entreprises/{entreprise}/reject-nom', [AdminController::class, 'rejectNom'])->name('entreprises.reject-nom');
    Route::post('/entreprises/{entreprise}/validate-siren', [AdminController::class, 'validateSiren'])->name('entreprises.validate-siren');
    Route::post('/entreprises/{entreprise}/reject-siren', [AdminController::class, 'rejectSiren'])->name('entreprises.reject-siren');
    Route::post('/entreprises/{entreprise}/validate', [AdminController::class, 'validateEntreprise'])->name('entreprises.validate');
    Route::post('/entreprises/{entreprise}/reject', [AdminController::class, 'rejectEntreprise'])->name('entreprises.reject');
    Route::post('/entreprises/{entreprise}/renvoyer', [AdminController::class, 'renvoyerEntreprise'])->name('entreprises.renvoyer');
    
    // Gestion des abonnements via les entreprises (redirige vers l'utilisateur)
    Route::get('/entreprises/{entreprise}/subscription', [AdminController::class, 'manageSubscription'])->name('entreprises.manage-subscription');
    Route::post('/entreprises/{entreprise}/subscription/deactivate', [AdminController::class, 'deactivateSubscription'])->name('entreprises.deactivate-subscription');
    
    // Gestion des abonnements utilisateurs
    Route::get('/users/{user}/subscription', [AdminController::class, 'showSubscription'])->name('users.subscription.show');
    Route::post('/users/{user}/subscription/manual', [AdminController::class, 'toggleManualSubscription'])->name('users.subscription.toggle-manual');
    Route::post('/users/{user}/subscription/cancel-stripe', [AdminController::class, 'cancelStripeSubscription'])->name('users.subscription.cancel-stripe');
});

// Route temporaire pour exécuter les migrations (À SUPPRIMER APRÈS UTILISATION)
Route::get('/run-error-notifications-migration', function () {
    // Sécurité basique : vérifier que c'est bien l'admin
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Accès refusé');
    }
    
    try {
        $results = [];
        
        // Vérifier si la colonne existe déjà
        $hasColumn = Schema::hasColumn('users', 'notifications_erreurs_actives');
        $hasTable = Schema::hasTable('error_logs');
        
        if ($hasColumn && $hasTable) {
            return response()->json([
                'success' => true,
                'message' => 'Les migrations ont déjà été exécutées. Tout est à jour !',
                'hasColumn' => true,
                'hasTable' => true,
            ]);
        }
        
        // Ajouter la colonne notifications_erreurs_actives
        if (!$hasColumn) {
            try {
                DB::statement('ALTER TABLE `users` ADD COLUMN `notifications_erreurs_actives` BOOLEAN DEFAULT FALSE AFTER `is_admin`');
                $results[] = '✓ Colonne notifications_erreurs_actives ajoutée à la table users';
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de l\'ajout de la colonne : ' . $e->getMessage(),
                ], 500);
            }
        } else {
            $results[] = '→ La colonne notifications_erreurs_actives existe déjà';
        }
        
        // Créer la table error_logs
        if (!$hasTable) {
            try {
                DB::statement("
                    CREATE TABLE IF NOT EXISTS `error_logs` (
                      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                      `level` VARCHAR(255) NOT NULL,
                      `message` VARCHAR(255) NOT NULL,
                      `context` TEXT NULL,
                      `file` VARCHAR(255) NULL,
                      `line` INT NULL,
                      `trace` TEXT NULL,
                      `url` VARCHAR(255) NULL,
                      `method` VARCHAR(255) NULL,
                      `ip` VARCHAR(255) NULL,
                      `user_agent` VARCHAR(255) NULL,
                      `user_id` BIGINT UNSIGNED NULL,
                      `est_vue` BOOLEAN DEFAULT FALSE,
                      `vu_at` TIMESTAMP NULL,
                      `created_at` TIMESTAMP NULL,
                      `updated_at` TIMESTAMP NULL,
                      PRIMARY KEY (`id`),
                      INDEX `idx_level` (`level`),
                      INDEX `idx_est_vue` (`est_vue`),
                      INDEX `idx_created_at` (`created_at`),
                      CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $results[] = '✓ Table error_logs créée';
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de la création de la table : ' . $e->getMessage(),
                ], 500);
            }
        } else {
            $results[] = '→ La table error_logs existe déjà';
        }
        
        // Vérification finale
        $hasColumn = Schema::hasColumn('users', 'notifications_erreurs_actives');
        $hasTable = Schema::hasTable('error_logs');
        
        return response()->json([
            'success' => true,
            'message' => 'Migrations terminées avec succès !',
            'results' => $results,
            'hasColumn' => $hasColumn,
            'hasTable' => $hasTable,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Erreur : ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->middleware('auth');

// Route de diagnostic pour vérifier les autorisations (à supprimer en production)
Route::get('/diagnostic-auth', function () {
    if (!Auth::check()) {
        return response()->json([
            'error' => 'Non authentifié',
            'auth_check' => false,
        ], 401);
    }

    $user = Auth::user();
    $diagnostic = [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'is_admin' => $user->is_admin ?? false,
        'est_gerant' => $user->est_gerant ?? false,
        'entreprises' => [],
    ];

    // Vérifier les entreprises de l'utilisateur
    $entreprises = \App\Models\Entreprise::where('user_id', $user->id)->get();
    
    foreach ($entreprises as $entreprise) {
        $diagnostic['entreprises'][] = [
            'id' => $entreprise->id,
            'nom' => $entreprise->nom,
            'slug' => $entreprise->slug,
            'user_id' => $entreprise->user_id,
            'user_id_matches' => $entreprise->user_id === $user->id,
            'est_verifiee' => $entreprise->est_verifiee,
            'a_abonnement_actif' => $entreprise->aAbonnementActif(),
        ];
    }

    // Vérifier si l'utilisateur peut accéder à une entreprise spécifique (si slug fourni)
    if (request()->has('slug')) {
        $slug = request()->get('slug');
        $entreprise = \App\Models\Entreprise::where('slug', $slug)->first();
        
        if ($entreprise) {
            $diagnostic['entreprise_test'] = [
                'slug' => $slug,
                'found' => true,
                'entreprise_id' => $entreprise->id,
                'entreprise_user_id' => $entreprise->user_id,
                'user_id' => $user->id,
                'is_owner' => $entreprise->user_id === $user->id,
                'can_access' => $entreprise->user_id === $user->id || $user->is_admin,
            ];
        } else {
            $diagnostic['entreprise_test'] = [
                'slug' => $slug,
                'found' => false,
            ];
        }
    }

    return response()->json($diagnostic, 200);
})->middleware('auth');