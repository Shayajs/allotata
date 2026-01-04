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
use App\Http\Controllers\LegalController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/a-propos', [\App\Http\Controllers\PageController::class, 'about'])->name('pages.about');

// Pages Légales
Route::get('/legal/mentions-legales', [LegalController::class, 'mentionsLegales'])->name('legal.mentions');
Route::get('/legal/confidentialite', [LegalController::class, 'politiqueConfidentialite'])->name('legal.confidentialite');
Route::get('/legal/cgu', [LegalController::class, 'cgu'])->name('legal.cgu');
Route::get('/legal/cgv', [LegalController::class, 'cgv'])->name('legal.cgv');
Route::get('/legal/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');
use App\Http\Controllers\FactureController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\TempAdminController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MessagerieController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\EntrepriseDashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\SiteWebController;
use App\Http\Controllers\EntrepriseSubscriptionController;
use App\Http\Controllers\EntrepriseMembreController;
use App\Http\Controllers\MembreGestionController;
use App\Http\Controllers\InvitationController;


Route::get('/a-propos', [\App\Http\Controllers\PageController::class, 'about'])->name('pages.about');

// Route pour servir les fichiers storage via un contrôleur
// SOLUTION TEMPORAIRE : Utiliser /media/ au lieu de /storage/ car /storage/ est bloqué par le serveur web
// TODO: Résoudre le problème de blocage de /storage/ par le serveur web
Route::get('/media/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

// Ancienne route /storage/ - désactivée car bloquée par le serveur web
// Route::get('/storage/{path}', [StorageController::class, 'serve'])
//     ->where('path', '.*')
//     ->name('storage.serve');

// Route de test pour vérifier que Laravel répond
Route::get('/test-storage', function() {
    return response()->json([
        'storage_path' => storage_path('app/public'),
        'base_path' => base_path(),
        'test_file' => base_path('storage/app/public/profils/1767200267_yfZuEju0mV.png'),
        'exists' => file_exists(base_path('storage/app/public/profils/1767200267_yfZuEju0mV.png')),
    ]);
});

// Route de test directe pour servir une image
Route::get('/test-image', function() {
    $filePath = base_path('storage/app/public/profils/1767200267_yfZuEju0mV.png');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'image/png']);
    }
    return response()->json(['error' => 'File not found', 'path' => $filePath], 404);
});

// Webhook Stripe (doit être en dehors du middleware auth et sans CSRF)
Route::post(
    '/stripe/webhook',
    [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']
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

// API Adresse (autocomplétion et géocodage)
Route::prefix('api/address')->name('api.address.')->group(function () {
    Route::get('/search', [\App\Http\Controllers\Api\AddressController::class, 'search'])->name('search');
    Route::get('/cities', [\App\Http\Controllers\Api\AddressController::class, 'searchCities'])->name('cities');
    Route::get('/geocode', [\App\Http\Controllers\Api\AddressController::class, 'geocode'])->name('geocode');
});

// Inscription (Signup)
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'register'])->name('register');

// Connexion (Signin)
Route::get('/signin', [AuthController::class, 'showSignin'])->name('login');
Route::post('/signin', [AuthController::class, 'login']);

// Invitations (public et authentifié)
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accepter', [InvitationController::class, 'accepter'])->name('invitations.accepter');
Route::post('/invitations/{token}/refuser', [InvitationController::class, 'refuser'])->name('invitations.refuser');

// Déconnexion
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Entreprise (Public)
Route::get("/p/{slug}", [PublicController::class, 'show'])->name('public.entreprise');
Route::get("/p/{slug}/agenda", [PublicController::class, 'agenda'])->name('public.agenda');
Route::get("/p/{slug}/agenda/reservations", [PublicController::class, 'getReservations'])->name('public.agenda.reservations');
Route::post("/p/{slug}/reservation", [PublicController::class, 'storeReservation'])->name('public.reservation.store');

// Sites web vitrine (Public)
Route::get("/w/{slug}", [SiteWebController::class, 'show'])->name('site-web.show');

// API Site Web Vitrine (Authentifié - Propriétaire uniquement)
Route::middleware('auth')->prefix('/w/{slug}')->name('site-web.')->group(function () {
    Route::put('/', [SiteWebController::class, 'update'])->name('update');
    Route::put('/content', [SiteWebController::class, 'saveContent'])->name('content.save');
    Route::post('/upload', [SiteWebController::class, 'uploadImage'])->name('upload');
    Route::post('/template', [SiteWebController::class, 'loadTemplate'])->name('template.load');
    Route::post('/render-block', [SiteWebController::class, 'renderBlock'])->name('render-block');
    Route::get('/versions', [SiteWebController::class, 'getVersions'])->name('versions');
    Route::post('/restore/{version}', [SiteWebController::class, 'restoreVersion'])->name('restore');
});

// Contact (public - depuis le footer)
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Tickets (public - depuis l'accueil et dashboards)
Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

// Avis (nécessite authentification)
Route::middleware('auth')->group(function () {
    Route::get("/p/{slug}/avis/create", [AvisController::class, 'create'])->name('avis.create');
    Route::post("/p/{slug}/avis", [AvisController::class, 'store'])->name('avis.store');
    Route::put("/p/{slug}/avis/{id}", [AvisController::class, 'update'])->name('avis.update');
    
    // Tickets utilisateur (authentifié)
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/message', [TicketController::class, 'addMessage'])->name('tickets.add-message');
});

// Routes protégées
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/entreprises-autres', [DashboardController::class, 'entreprisesAutres'])->name('dashboard.entreprises-autres');
    Route::post('/dashboard/reservation/{reservation}/cancel', [DashboardController::class, 'cancel'])->name('dashboard.reservation.cancel');
    Route::patch('/dashboard/reservation/{reservation}/modify', [DashboardController::class, 'modify'])->name('dashboard.reservation.modify');
    Route::post('/stop-impersonating', [AdminController::class, 'stopImpersonating'])->name('stop-impersonating');
    
    // Création d'entreprise
    Route::get('/entreprise/create', [EntrepriseController::class, 'create'])->name('entreprise.create');
    Route::post('/entreprise', [EntrepriseController::class, 'store'])->name('entreprise.store');
    
    // Dashboard entreprise (centralisé)
    Route::get('/m/{slug}', [EntrepriseDashboardController::class, 'index'])->name('entreprise.dashboard');
    
    // Finances d'entreprise
    Route::get('/m/{slug}/finances', [\App\Http\Controllers\EntrepriseFinanceController::class, 'index'])->name('entreprise.finances.index');
    Route::post('/m/{slug}/finances', [\App\Http\Controllers\EntrepriseFinanceController::class, 'store'])->name('entreprise.finances.store');
    Route::put('/m/{slug}/finances/{finance}', [\App\Http\Controllers\EntrepriseFinanceController::class, 'update'])->name('entreprise.finances.update');
    Route::delete('/m/{slug}/finances/{finance}', [\App\Http\Controllers\EntrepriseFinanceController::class, 'destroy'])->name('entreprise.finances.destroy');
    Route::post('/m/{slug}/fiscal-settings', [\App\Http\Controllers\EntrepriseFinanceController::class, 'saveFiscalSettings'])->name('entreprise.fiscal-settings.save');
    
    // Gestion de l'agenda (pour les gérants)
    Route::get('/m/{slug}/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/m/{slug}/agenda/service', [AgendaController::class, 'index'])->name('agenda.service.index');
    Route::get('/m/{slug}/agenda/reservations', [AgendaController::class, 'getReservations'])->name('agenda.reservations');
    Route::post('/m/{slug}/agenda/horaires', [AgendaController::class, 'storeHoraires'])->name('agenda.horaires.store');
    Route::post('/m/{slug}/agenda/service', [AgendaController::class, 'storeTypeService'])->name('agenda.service.store');
    Route::delete('/m/{slug}/agenda/service/{typeServiceId}', [AgendaController::class, 'deleteTypeService'])->name('agenda.service.delete');
    Route::post('/m/{slug}/agenda/service/{typeServiceId}/image', [AgendaController::class, 'uploadServiceImage'])->name('agenda.service.image.upload');
    Route::post('/m/{slug}/agenda/service/{typeServiceId}/image/{imageId}/cover', [AgendaController::class, 'setServiceImageCover'])->name('agenda.service.image.cover');
    Route::delete('/m/{slug}/agenda/service/{typeServiceId}/image/{imageId}', [AgendaController::class, 'deleteServiceImage'])->name('agenda.service.image.delete');
    Route::post('/m/{slug}/agenda/jour-exceptionnel', [AgendaController::class, 'storeJourExceptionnel'])->name('agenda.jour-exceptionnel.store');
    Route::delete('/m/{slug}/agenda/jour-exceptionnel/{horaireId}', [AgendaController::class, 'deleteJourExceptionnel'])->name('agenda.jour-exceptionnel.delete');
    
    // Gestion de l'équipe (multi-personnes)
    Route::prefix('m/{slug}/equipe')->name('entreprise.equipe.')->group(function() {
        Route::get('/', [MembreGestionController::class, 'index'])->name('index');
        Route::get('/{membre}', [MembreGestionController::class, 'show'])->name('show');
        Route::post('/{membre}/disponibilites', [MembreGestionController::class, 'updateDisponibilites'])->name('disponibilites.update');
        Route::post('/{membre}/indisponibilites', [MembreGestionController::class, 'storeIndisponibilite'])->name('indisponibilites.store');
        Route::delete('/{membre}/indisponibilites/{indisponibilite}', [MembreGestionController::class, 'deleteIndisponibilite'])->name('indisponibilites.delete');
        Route::get('/{membre}/agenda', [MembreGestionController::class, 'getAgenda'])->name('agenda');
        Route::get('/{membre}/statistiques', [MembreGestionController::class, 'getStatistiques'])->name('statistiques');
    });
    
    // Gestion des réservations (pour les gérants)
    Route::get('/m/{slug}/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/m/{slug}/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/m/{slug}/reservations/{id}/start-conversation', [ReservationController::class, 'startConversation'])->name('reservations.start-conversation');
    Route::post('/m/{slug}/reservations/{id}/accept', [ReservationController::class, 'accept'])->name('reservations.accept');
    Route::post('/m/{slug}/reservations/{id}/reject', [ReservationController::class, 'reject'])->name('reservations.reject');
    Route::post('/m/{slug}/reservations/{id}/notes', [ReservationController::class, 'addNotes'])->name('reservations.notes');
    Route::post('/m/{slug}/reservations/{id}/marquer-payee', [ReservationController::class, 'marquerPayee'])->name('reservations.marquer-payee');
    
    // Paramètres
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/error-notifications', [SettingsController::class, 'updateErrorNotifications'])->name('settings.error-notifications.update');
    Route::post('/settings/entreprise/{slug}', [SettingsController::class, 'updateEntreprise'])->name('settings.entreprise.update');
    Route::post('/settings/entreprise/{slug}/logo/upload', [SettingsController::class, 'uploadLogo'])->name('settings.entreprise.logo.upload');
    Route::post('/settings/entreprise/{slug}/image-fond/upload', [SettingsController::class, 'uploadImageFond'])->name('settings.entreprise.image-fond.upload');
    Route::delete('/settings/entreprise/{slug}/logo', [SettingsController::class, 'deleteLogo'])->name('settings.entreprise.logo.delete');
    Route::delete('/settings/entreprise/{slug}/image-fond', [SettingsController::class, 'deleteImageFond'])->name('settings.entreprise.image-fond.delete');
    Route::post('/settings/entreprise/{slug}/photo', [SettingsController::class, 'addRealisationPhoto'])->name('settings.entreprise.photo.add');
    Route::delete('/settings/entreprise/{slug}/photo/{photoId}', [SettingsController::class, 'deleteRealisationPhoto'])->name('settings.entreprise.photo.delete');
    Route::delete('/settings/entreprise/{slug}', [SettingsController::class, 'deleteEntreprise'])->name('settings.entreprise.delete');
    Route::post('/settings/entreprise/{slug}/restore', [SettingsController::class, 'restoreEntreprise'])->name('settings.entreprise.restore');
    
    // Abonnements d'entreprise
    Route::get('/m/{slug}/abonnements', [EntrepriseSubscriptionController::class, 'index'])->name('entreprise.subscriptions.index');
    Route::get('/m/{slug}/abonnements/modal', [EntrepriseSubscriptionController::class, 'modal'])->name('entreprise.subscriptions.modal');
    Route::post('/m/{slug}/abonnements/checkout', [EntrepriseSubscriptionController::class, 'checkout'])->name('entreprise.subscriptions.checkout');
    Route::get('/m/{slug}/abonnements/success/{type}', [EntrepriseSubscriptionController::class, 'success'])->name('entreprise.subscriptions.success');
    Route::post('/m/{slug}/abonnements/{type}/cancel', [EntrepriseSubscriptionController::class, 'cancel'])->name('entreprise.subscriptions.cancel');
    Route::post('/m/{slug}/abonnements/{type}/cancel-direct', [EntrepriseSubscriptionController::class, 'cancelSubscription'])->name('entreprise.subscriptions.cancel-direct');
    Route::post('/m/{slug}/abonnements/{type}/resume', [EntrepriseSubscriptionController::class, 'resumeSubscription'])->name('entreprise.subscriptions.resume');
    
    // Essais gratuits
    Route::post('/essai-gratuit/utilisateur', [\App\Http\Controllers\EssaiGratuitController::class, 'demarrerEssaiUtilisateur'])->name('essai-gratuit.utilisateur');
    Route::post('/m/{entreprise}/essai-gratuit', [\App\Http\Controllers\EssaiGratuitController::class, 'demarrerEssaiEntreprise'])->name('essai-gratuit.entreprise');
    Route::post('/essai-gratuit/{essai}/annuler', [\App\Http\Controllers\EssaiGratuitController::class, 'annulerEssai'])->name('essai-gratuit.annuler');
    Route::post('/essai-gratuit/{essai}/feedback', [\App\Http\Controllers\EssaiGratuitController::class, 'feedback'])->name('essai-gratuit.feedback');
    
    // Gestion des membres d'entreprise
    Route::get('/m/{slug}/membres', [EntrepriseMembreController::class, 'index'])->name('entreprise.membres.index');
    Route::post('/m/{slug}/membres', [EntrepriseMembreController::class, 'store'])->name('entreprise.membres.store');
    Route::put('/m/{slug}/membres/{membre}', [EntrepriseMembreController::class, 'update'])->name('entreprise.membres.update');
    Route::delete('/m/{slug}/membres/{membre}', [EntrepriseMembreController::class, 'destroy'])->name('entreprise.membres.destroy');
    
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
    Route::post('/m/{slug}/messagerie/{conversationId}/refuser-proposition/{propositionId}', [MessagerieController::class, 'refuserPropositionGerant'])->name('messagerie.refuser-proposition-gerant');
    Route::post('/messagerie/{slug}/modify-proposition', [MessagerieController::class, 'modifyPropositionClient'])->name('messagerie.modify-proposition-client');
    Route::post('/m/{slug}/messagerie/{conversationId}/modify-proposition', [MessagerieController::class, 'modifyPropositionGerant'])->name('messagerie.modify-proposition-gerant');
    Route::get('/messagerie/{slug}/agenda', [MessagerieController::class, 'getAgendaForDate'])->name('messagerie.agenda');
    Route::post('/messagerie/{slug}/check-conflict', [MessagerieController::class, 'checkConflict'])->name('messagerie.check-conflict');
    
    // Abonnements
        Route::get('/abonnement', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/abonnement/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::get('/abonnement/success', [SubscriptionController::class, 'success'])->name('subscription.success');
        Route::post('/abonnement/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('/abonnement/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
        Route::post('/abonnement/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
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
    
    // Gestion des finances globales
    Route::get('/finances', [AdminController::class, 'finances'])->name('finances.index');
    
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
    
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
    
    // Gestion des options d'entreprise
    Route::get('/entreprises/{entreprise}/options', [AdminController::class, 'optionsEntreprise'])->name('entreprises.options');
    Route::post('/entreprises/{entreprise}/options/activer', [AdminController::class, 'activerOptionEntreprise'])->name('entreprises.options.activer');
    Route::post('/entreprises/{entreprise}/options/{type}/desactiver', [AdminController::class, 'desactiverOptionEntreprise'])->name('entreprises.options.desactiver');
    
    // Gestion des membres d'entreprise (admin)
    Route::post('/entreprises/{entreprise}/membres', [AdminController::class, 'ajouterMembreEntreprise'])->name('entreprises.membres.store');
    Route::put('/entreprises/{entreprise}/membres/{membre}', [AdminController::class, 'mettreAJourRoleMembre'])->name('entreprises.membres.update');
    Route::delete('/entreprises/{entreprise}/membres/{membre}', [AdminController::class, 'supprimerMembreEntreprise'])->name('entreprises.membres.destroy');
    
    // Gestion des abonnements utilisateurs
    Route::get('/users/{user}/subscription', [AdminController::class, 'showSubscription'])->name('users.subscription.show');
    Route::post('/users/{user}/subscription/manual', [AdminController::class, 'toggleManualSubscription'])->name('users.subscription.toggle-manual');
    Route::post('/users/{user}/subscription/cancel-stripe', [AdminController::class, 'cancelStripeSubscription'])->name('users.subscription.cancel-stripe');
    
    // Gestion des abonnements entreprises
    Route::get('/entreprises/{entreprise}/subscription', [AdminController::class, 'showEntrepriseSubscription'])->name('entreprises.subscription.show');
    Route::post('/entreprises/{entreprise}/activate-subscription', [AdminController::class, 'activateEntrepriseSubscription'])->name('entreprises.activate-subscription');
    
    // Gestion des contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::post('/contacts/{contact}/toggle-read', [ContactController::class, 'toggleRead'])->name('contacts.toggle-read');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    
    // Gestion des tickets
    Route::get('/tickets', [TicketController::class, 'adminIndex'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'adminShow'])->name('tickets.show');
    Route::post('/tickets/{ticket}', [TicketController::class, 'adminUpdate'])->name('tickets.update');
    Route::post('/tickets/{ticket}/message', [TicketController::class, 'addMessage'])->name('tickets.message');
    
    // Gestion des FAQs
    Route::get('/faqs', [FaqController::class, 'adminIndex'])->name('faqs.index');
    Route::get('/faqs/create', [FaqController::class, 'adminCreate'])->name('faqs.create');
    Route::post('/faqs', [FaqController::class, 'adminStore'])->name('faqs.store');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'adminEdit'])->name('faqs.edit');
    Route::put('/faqs/{faq}', [FaqController::class, 'adminUpdate'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'adminDestroy'])->name('faqs.destroy');
    
    // Recherche globale
    Route::get('/search', [\App\Http\Controllers\Admin\SearchController::class, 'index'])->name('search');
    
    // Logs d'activité
    Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
    // Route de secours pour l'erreur bizarre

    
    // Exports
    Route::get('/exports', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('exports.index');
    Route::get('/exports/users', [\App\Http\Controllers\Admin\ExportController::class, 'exportUsers'])->name('exports.users');
    Route::get('/exports/entreprises', [\App\Http\Controllers\Admin\ExportController::class, 'exportEntreprises'])->name('exports.entreprises');
    Route::get('/exports/reservations', [\App\Http\Controllers\Admin\ExportController::class, 'exportReservations'])->name('exports.reservations');
    
    // Paramètres système
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/store', [\App\Http\Controllers\Admin\SettingController::class, 'store'])->name('settings.store');
    Route::delete('/settings/{setting}', [\App\Http\Controllers\Admin\SettingController::class, 'destroy'])->name('settings.destroy');
    
    // Annonces
    Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);
    
    // Codes promo
    Route::resource('promo-codes', \App\Http\Controllers\Admin\PromoCodeController::class);
    
    // Gestion des prix Stripe
    Route::get('/stripe-prices', [AdminController::class, 'stripePrices'])->name('stripe-prices.index');
    Route::post('/stripe-prices/create', [AdminController::class, 'createStripePrice'])->name('stripe-prices.create');
    Route::post('/stripe-prices/{type}/update', [AdminController::class, 'updateStripePrice'])->name('stripe-prices.update');
    Route::post('/stripe-prices/{type}/create-missing', [AdminController::class, 'createMissingPrice'])->name('stripe-prices.create-missing');
    
    // Gestion des prix personnalisés
    Route::get('/custom-prices', [AdminController::class, 'customPrices'])->name('custom-prices.index');
    Route::post('/custom-prices/create', [AdminController::class, 'createCustomPrice'])->name('custom-prices.create');
    Route::post('/custom-prices/{customPrice}/toggle', [AdminController::class, 'toggleCustomPrice'])->name('custom-prices.toggle');
    Route::delete('/custom-prices/{customPrice}', [AdminController::class, 'deleteCustomPrice'])->name('custom-prices.delete');
    
    // Gestion des abonnements
    Route::get('/subscriptions', [\App\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/sync', [\App\Http\Controllers\AdminSubscriptionController::class, 'syncAll'])->name('subscriptions.sync');
    Route::post('/subscriptions/force-manual', [\App\Http\Controllers\AdminSubscriptionController::class, 'forceManual'])->name('subscriptions.force_manual');
    Route::post('/subscriptions/stop-manual/{id}', [\App\Http\Controllers\AdminSubscriptionController::class, 'stopManual'])->name('subscriptions.stop_manual');
    Route::post('/subscriptions/update-manual/{id}', [\App\Http\Controllers\AdminSubscriptionController::class, 'updateManual'])->name('subscriptions.update_manual');
    
    // Legacy / Specific Sync Actions (Redirigés ou gérés par le nouveau controller si implémentés, sinon garder AdminController pour l'instant pour la rétrocompatibilité des actions spécifiques utilisateur/entreprise si je ne les ai pas toutes migrées)
    // J'ai implémenté forceManual, mais pas syncUserSubscription ni cancel... 
    // Attends, mon AdminSubscriptionController n'est pas complet ! Il manque les méthodes sync/cancel individuelles !
    // Je dois les ajouter au controller avant de changer les routes, OU rediriger vers AdminController pour celles-là.
    // MAIS AdminController utilse l'ancienne logique ? Non, il appelle StripeSubscriptionSyncService qui est à jour.
    // Donc je peux garder AdminController pour les actions sync/cancel individuelles si je ne les ai pas copiées.
    
    Route::post('/subscriptions/user/{subscription}/sync', [\App\Http\Controllers\AdminController::class, 'syncUserSubscription'])->name('subscriptions.user.sync');
    Route::post('/subscriptions/user/{subscription}/cancel', [\App\Http\Controllers\AdminController::class, 'cancelUserSubscription'])->name('subscriptions.user.cancel');
    Route::post('/subscriptions/entreprise/{subscription}/sync', [\App\Http\Controllers\AdminController::class, 'syncEntrepriseSubscription'])->name('subscriptions.entreprise.sync');
    Route::post('/subscriptions/entreprise/{subscription}/cancel', [\App\Http\Controllers\AdminController::class, 'cancelEntrepriseSubscription'])->name('subscriptions.entreprise.cancel');
    
    // Gestion des essais gratuits
    Route::get('/essais-gratuits', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'index'])->name('essais-gratuits.index');
    Route::post('/essais-gratuits/accorder', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'accorder'])->name('essais-gratuits.accorder');
    Route::post('/essais-gratuits/{essai}/revoquer', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'revoquer'])->name('essais-gratuits.revoquer');
    Route::post('/essais-gratuits/{essai}/prolonger', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'prolonger'])->name('essais-gratuits.prolonger');
    Route::get('/essais-gratuits/export', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'export'])->name('essais-gratuits.export');
    Route::get('/essais-gratuits/stats', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'statsApi'])->name('essais-gratuits.stats');
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




// Route de debug temporaire
require __DIR__ . '/debug_temp.php';
