<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\CourseController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/a-propos', [\App\Http\Controllers\PageController::class, 'about'])->name('pages.about');
Route::get('/fonctionnalites', [\App\Http\Controllers\PageController::class, 'fonctionnalites'])->name('pages.fonctionnalites');

// Routes publiques pour les cours
Route::get('/apprendre', [CourseController::class, 'index'])->name('courses.index');
Route::get('/apprendre/module/{module}', [CourseController::class, 'showModule'])->name('courses.module');
Route::get('/apprendre/module/{module}/lecon/{lesson}', [CourseController::class, 'showLesson'])
    ->middleware([\App\Http\Middleware\EnsureLessonAccessible::class])
    ->name('courses.lesson');

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

// Récupération de mot de passe
Route::get('/password/reset', [\App\Http\Controllers\PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/password/reset', [\App\Http\Controllers\PasswordResetController::class, 'sendCode'])->name('password.send-code');
Route::get('/password/reset/verify', [\App\Http\Controllers\PasswordResetController::class, 'showVerifyForm'])->name('password.reset.verify');
Route::post('/password/reset/verify', [\App\Http\Controllers\PasswordResetController::class, 'verifyCode'])->name('password.verify-code');
Route::get('/password/reset/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset/confirm', [\App\Http\Controllers\PasswordResetController::class, 'resetPassword'])->name('password.reset');

// Vérification d'email
Route::get('/verification/required', [\App\Http\Controllers\EmailVerificationController::class, 'show'])->name('verification.required');
Route::post('/verification/resend', [\App\Http\Controllers\EmailVerificationController::class, 'resend'])->middleware('auth')->name('verification.resend');
Route::get('/security/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])->name('verification.verify');

// Authentification à deux facteurs (A2F)
Route::get('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/two-factor/request', [\App\Http\Controllers\TwoFactorController::class, 'requestCode'])->name('two-factor.request');
Route::post('/two-factor/verify', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('two-factor.verify');

// Entreprise (Public)
Route::get("/p/{slug}", [PublicController::class, 'show'])->name('public.entreprise');
Route::get("/p/{slug}/agenda", [PublicController::class, 'agenda'])->name('public.agenda');
Route::get("/p/{slug}/agenda/reservations", [PublicController::class, 'getReservations'])->name('public.agenda.reservations');
Route::post("/p/{slug}/reservation", [PublicController::class, 'storeReservation'])->name('public.reservation.store');
Route::get("/p/{slug}/store", [PublicController::class, 'store'])->name('public.store');
Route::get("/p/{slug}/services", [PublicController::class, 'services'])->name('public.services');
Route::get("/p/{slug}/produits", [PublicController::class, 'produits'])->name('public.produits');

// API de tracking (accessible sans authentification)
Route::prefix('api/tracking/visite')->group(function() {
    Route::post('/duree', [\App\Http\Controllers\TrackingController::class, 'mettreAJourDuree'])->name('api.tracking.visite.duree');
    Route::post('/clic', [\App\Http\Controllers\TrackingController::class, 'enregistrerClic'])->name('api.tracking.visite.clic');
});

// Réservation publique (accessible via lien partagé)
Route::get("/r/{hash}", [PublicController::class, 'showReservation'])->name('public.reservation.show');
Route::post("/r/{hash}/annuler", [PublicController::class, 'annulerReservation'])->name('public.reservation.annuler');

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
    
    // Routes API pour la progression des cours
    Route::post('/api/courses/complete-lesson', [CourseController::class, 'completeLesson'])->name('api.courses.complete-lesson');
    Route::post('/api/courses/quiz-submit', [CourseController::class, 'submitQuiz'])->name('api.courses.quiz-submit');
});

// Routes protégées - nécessitent authentification et email vérifié
Route::middleware(['auth', 'verified', 'check.trusted.device'])->group(function () {
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
    
    // Gestion des stocks et produits
    Route::get('/m/{slug}/stock', [\App\Http\Controllers\StockController::class, 'index'])->name('stock.index');
    Route::post('/m/{slug}/stock/produit', [\App\Http\Controllers\StockController::class, 'storeProduit'])->name('stock.produit.store');
    Route::delete('/m/{slug}/stock/produit/{produitId}', [\App\Http\Controllers\StockController::class, 'deleteProduit'])->name('stock.produit.delete');
    Route::post('/m/{slug}/stock/produit/{produitId}/image', [\App\Http\Controllers\StockController::class, 'uploadProduitImage'])->name('stock.produit.image.upload');
    Route::post('/m/{slug}/stock/produit/{produitId}/image/{imageId}/cover', [\App\Http\Controllers\StockController::class, 'setProduitImageCover'])->name('stock.produit.image.cover');
    Route::delete('/m/{slug}/stock/produit/{produitId}/image/{imageId}', [\App\Http\Controllers\StockController::class, 'deleteProduitImage'])->name('stock.produit.image.delete');
    Route::post('/m/{slug}/stock/produit/{produitId}/stock', [\App\Http\Controllers\StockController::class, 'updateStock'])->name('stock.update');
    Route::post('/m/{slug}/stock/produit/{produitId}/promotion', [\App\Http\Controllers\StockController::class, 'storePromotion'])->name('stock.promotion.store');
    Route::delete('/m/{slug}/stock/produit/{produitId}/promotion/{promotionId}', [\App\Http\Controllers\StockController::class, 'deletePromotion'])->name('stock.promotion.delete');
    
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
    
    // Statistiques des visites
    Route::prefix('m/{slug}')->name('entreprise.statistiques.')->group(function() {
        Route::get('/statistiques', [\App\Http\Controllers\EntrepriseStatistiqueController::class, 'index'])->name('index');
        Route::get('/statistiques/api', [\App\Http\Controllers\EntrepriseStatistiqueController::class, 'apiStats'])->name('api');
        Route::post('/statistiques/contacter', [\App\Http\Controllers\EntrepriseStatistiqueController::class, 'contacterVisiteur'])->name('contacter');
        Route::post('/statistiques/proposer-prix', [\App\Http\Controllers\EntrepriseStatistiqueController::class, 'proposerPrixPersonnalise'])->name('proposer-prix');
    });
    
    // Gestion des réservations (pour les gérants)
    Route::get('/m/{slug}/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    // Routes spécifiques AVANT la route {id} pour éviter les conflits
    Route::get('/m/{slug}/reservations/search-clients', [ReservationController::class, 'searchClients'])->name('reservations.search-clients');
    Route::post('/m/{slug}/reservations/manuelle', [ReservationController::class, 'storeManuelle'])->name('reservations.store-manuelle');
    // Route {id} en dernier
    Route::get('/m/{slug}/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/m/{slug}/reservations/{id}/start-conversation', [ReservationController::class, 'startConversation'])->name('reservations.start-conversation');
    Route::post('/m/{slug}/reservations/{id}/accept', [ReservationController::class, 'accept'])->name('reservations.accept');
    Route::post('/m/{slug}/reservations/{id}/reject', [ReservationController::class, 'reject'])->name('reservations.reject');
    Route::post('/m/{slug}/reservations/{id}/notes', [ReservationController::class, 'addNotes'])->name('reservations.notes');
    Route::post('/m/{slug}/reservations/{id}/marquer-payee', [ReservationController::class, 'marquerPayee'])->name('reservations.marquer-payee');
    
    // Export de rapports
    Route::get('/m/{slug}/reports/reservations', [\App\Http\Controllers\ReportController::class, 'exportReservations'])->name('reports.export-reservations');
    Route::get('/m/{slug}/reports/financial', [\App\Http\Controllers\ReportController::class, 'exportFinancialReport'])->name('reports.export-financial');
    
    // Programme de fidélité
    Route::get('/m/{slug}/loyalty', [\App\Http\Controllers\LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('/m/{slug}/loyalty/{userId}', [\App\Http\Controllers\LoyaltyController::class, 'show'])->name('loyalty.show');
    
    // Notes clients
    Route::get('/m/{slug}/client-notes/{userId}', [\App\Http\Controllers\ClientNoteController::class, 'index'])->name('client-notes.index');
    Route::get('/m/{slug}/client-notes', [\App\Http\Controllers\ClientNoteController::class, 'all'])->name('client-notes.all');
    Route::post('/m/{slug}/client-notes/{userId}', [\App\Http\Controllers\ClientNoteController::class, 'store'])->name('client-notes.store');
    Route::put('/m/{slug}/client-notes/{noteId}', [\App\Http\Controllers\ClientNoteController::class, 'update'])->name('client-notes.update');
    Route::delete('/m/{slug}/client-notes/{noteId}', [\App\Http\Controllers\ClientNoteController::class, 'destroy'])->name('client-notes.destroy');
    
    // Paramètres
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/error-notifications', [SettingsController::class, 'updateErrorNotifications'])->name('settings.error-notifications.update');
    Route::post('/settings/confidentialite', [SettingsController::class, 'updateConfidentialite'])->name('settings.confidentialite.update');
    
    // Sécurité
    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
    Route::post('/security/recovery-method', [SecurityController::class, 'updateRecoveryMethod'])->name('security.recovery-method.update');
    Route::post('/security/a2f', [SecurityController::class, 'updateA2F'])->name('security.a2f.update');
    
    // Google 2FA TOTP
    Route::post('/security/google2fa/generate', [SecurityController::class, 'generateGoogle2fa'])->name('security.google2fa.generate');
    Route::post('/security/google2fa/enable', [SecurityController::class, 'enableGoogle2fa'])->name('security.google2fa.enable');
    Route::post('/security/google2fa/disable', [SecurityController::class, 'disableGoogle2fa'])->name('security.google2fa.disable');
    Route::post('/security/google2fa/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('security.google2fa.recovery-codes');
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
    Route::get('/m/{slug}/comptabilite', [FactureController::class, 'comptabilite'])->name('factures.comptabilite');
    // Routes spécifiques AVANT la route {id} pour éviter les conflits
    Route::get('/m/{slug}/factures/create-groupee', [FactureController::class, 'createGroupee'])->name('factures.create-groupee');
    Route::get('/m/{slug}/factures/reservations', [FactureController::class, 'getReservationsPourFactureGroupee'])->name('factures.reservations');
    Route::post('/m/{slug}/factures/groupee', [FactureController::class, 'storeGroupee'])->name('factures.store-groupee');
    // Routes avec {id} en dernier
    Route::get('/m/{slug}/factures/{id}', [FactureController::class, 'showEntreprise'])->name('factures.entreprise.show');
    Route::get('/m/{slug}/factures/{id}/download', [FactureController::class, 'downloadEntreprise'])->name('factures.entreprise.download');
    
    // Messagerie
    Route::get('/messagerie', [MessagerieController::class, 'index'])->name('messagerie.index');
    Route::get('/messagerie/{slug}', [MessagerieController::class, 'show'])->name('messagerie.show');
    Route::get('/messagerie/{slug}/commander-produit/{produitId}', [MessagerieController::class, 'commanderProduit'])->name('messagerie.commander-produit');
    Route::get('/messagerie/{slug}/demander-service/{serviceId}', [MessagerieController::class, 'demanderService'])->name('messagerie.demander-service');
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
        Route::post('/abonnement/purge/{id}', [SubscriptionController::class, 'purge'])->name('subscription.purge');
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
    
    // Statistiques détaillées admin
    Route::get('/statistiques', [\App\Http\Controllers\AdminStatistiqueController::class, 'index'])->name('statistiques.index');
    Route::get('/api/statistiques', [\App\Http\Controllers\AdminStatistiqueController::class, 'api'])->name('statistiques.api');
    Route::get('/statistiques/export', [\App\Http\Controllers\AdminStatistiqueController::class, 'export'])->name('statistiques.export');
    
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/deleted', [AdminController::class, 'usersDeleted'])->name('users.deleted');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.status.update');
    Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/users/{user}/generate-password', [AdminController::class, 'generatePasswordForUser'])->name('users.generate-password');
    Route::post('/users/{user}/update-email', [AdminController::class, 'updateUserEmail'])->name('users.update-email');
    Route::post('/users/{user}/block', [AdminController::class, 'blockUser'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser'])->name('users.unblock');
    Route::post('/users/{user}/archive', [AdminController::class, 'archiveUser'])->name('users.archive');
    
    // Gestion des entreprises
    Route::get('/entreprises', [AdminController::class, 'entreprises'])->name('entreprises.index');
    Route::get('/entreprises/{entreprise}', [AdminController::class, 'showEntreprise'])->name('entreprises.show');
    Route::post('/entreprises/{entreprise}/verify', [AdminController::class, 'verifyEntreprise'])->name('entreprises.verify');
    Route::post('/entreprises/{entreprise}/unverify', [AdminController::class, 'unverifyEntreprise'])->name('entreprises.unverify');
    Route::post('/entreprises/{entreprise}/update-email', [AdminController::class, 'updateEntrepriseEmail'])->name('entreprises.update-email');
    Route::post('/entreprises/{entreprise}/archive', [AdminController::class, 'archiveEntreprise'])->name('entreprises.archive');
    
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
    
    // Gestion des médias (médiathèque)
    Route::get('/media', [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('media.index');
    Route::get('/api/media/list', [\App\Http\Controllers\Admin\MediaController::class, 'list'])->name('media.list');
    Route::post('/api/media/upload', [\App\Http\Controllers\Admin\MediaController::class, 'upload'])->name('media.upload');
    Route::post('/api/media/folders', [\App\Http\Controllers\Admin\MediaController::class, 'createFolder'])->name('media.folders.create');
    Route::get('/api/media/{mediaFile}', [\App\Http\Controllers\Admin\MediaController::class, 'show'])->name('media.show');
    Route::put('/api/media/{mediaFile}/rename', [\App\Http\Controllers\Admin\MediaController::class, 'rename'])->name('media.rename');
    Route::put('/api/media/{mediaFile}/move', [\App\Http\Controllers\Admin\MediaController::class, 'move'])->name('media.move');
    Route::post('/api/media/{mediaFile}/thumbnail', [\App\Http\Controllers\Admin\MediaController::class, 'uploadThumbnail'])->name('media.thumbnail.upload');
    Route::delete('/api/media/{mediaFile}/thumbnail', [\App\Http\Controllers\Admin\MediaController::class, 'deleteThumbnail'])->name('media.thumbnail.delete');
    Route::delete('/api/media/{mediaFile}', [\App\Http\Controllers\Admin\MediaController::class, 'delete'])->name('media.delete');
    
    // Gestion des cours (mode édition)
    Route::get('/courses', [\App\Http\Controllers\Admin\CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses/modules', [\App\Http\Controllers\Admin\CourseController::class, 'storeModule'])->name('courses.modules.store');
    Route::put('/courses/modules/{module}', [\App\Http\Controllers\Admin\CourseController::class, 'updateModule'])->name('courses.modules.update');
    Route::delete('/courses/modules/{module}', [\App\Http\Controllers\Admin\CourseController::class, 'destroyModule'])->name('courses.modules.destroy');
    Route::post('/courses/modules/order', [\App\Http\Controllers\Admin\CourseController::class, 'updateModuleOrder'])->name('courses.modules.order');
    Route::get('/courses/modules/{module}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'editModule'])->name('courses.module.edit');
    
    Route::post('/courses/modules/{module}/lessons', [\App\Http\Controllers\Admin\CourseController::class, 'storeLesson'])->name('courses.lessons.store');
    Route::put('/courses/modules/{module}/lessons/{lesson}', [\App\Http\Controllers\Admin\CourseController::class, 'updateLesson'])->name('courses.lessons.update');
    Route::delete('/courses/modules/{module}/lessons/{lesson}', [\App\Http\Controllers\Admin\CourseController::class, 'destroyLesson'])->name('courses.lessons.destroy');
    Route::post('/courses/modules/{module}/lessons/order', [\App\Http\Controllers\Admin\CourseController::class, 'updateLessonOrder'])->name('courses.lessons.order');
    
    // Édition complète d'une leçon
    Route::get('/courses/lessons/{lesson}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'editLesson'])->name('courses.lessons.edit');
    Route::post('/courses/lessons/{lesson}/save-draft', [\App\Http\Controllers\Admin\CourseController::class, 'saveDraft'])->name('courses.lessons.save-draft');
    Route::post('/courses/lessons/{lesson}/publish', [\App\Http\Controllers\Admin\CourseController::class, 'publish'])->name('courses.lessons.publish');
    Route::post('/courses/lessons/{lesson}/render-block', [\App\Http\Controllers\Admin\CourseController::class, 'renderBlock'])->name('courses.lessons.render-block');
    Route::post('/courses/lessons/{lesson}/upload-image', [\App\Http\Controllers\Admin\CourseController::class, 'uploadImageForLesson'])->name('courses.lessons.upload-image');
    Route::post('/courses/lessons/{lesson}/upload-video', [\App\Http\Controllers\Admin\CourseController::class, 'uploadVideoForLesson'])->name('courses.lessons.upload-video');
    
    Route::post('/courses/modules/{module}/lessons/{lesson}/questions', [\App\Http\Controllers\Admin\CourseController::class, 'storeQuizQuestion'])->name('courses.questions.store');
    Route::put('/courses/modules/{module}/lessons/{lesson}/questions/{question}', [\App\Http\Controllers\Admin\CourseController::class, 'updateQuizQuestion'])->name('courses.questions.update');
    Route::delete('/courses/modules/{module}/lessons/{lesson}/questions/{question}', [\App\Http\Controllers\Admin\CourseController::class, 'destroyQuizQuestion'])->name('courses.questions.destroy');
    
    Route::post('/courses/upload-image', [\App\Http\Controllers\Admin\CourseController::class, 'uploadImage'])->name('courses.upload-image');
    
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
    Route::post('/settings/logos/light', [\App\Http\Controllers\Admin\SettingController::class, 'uploadLogoLight'])->name('settings.upload-logo-light');
    Route::post('/settings/logos/dark', [\App\Http\Controllers\Admin\SettingController::class, 'uploadLogoDark'])->name('settings.upload-logo-dark');
    Route::post('/settings/logos/transparent', [\App\Http\Controllers\Admin\SettingController::class, 'uploadLogoTransparent'])->name('settings.upload-logo-transparent');
    Route::delete('/settings/logos/{type}', [\App\Http\Controllers\Admin\SettingController::class, 'deleteLogo'])->name('settings.delete-logo');
    
    // Annonces
    Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);
    
    // Codes promo
    Route::resource('promo-codes', \App\Http\Controllers\Admin\PromoCodeController::class);
    
    // Templates d'emails
    Route::get('/email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('/email-templates/create', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'create'])->name('email-templates.create');
    Route::post('/email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::get('/email-templates/compose', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'compose'])->name('email-templates.compose');
    Route::post('/email-templates/compose/preview', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'previewCompose'])->name('email-templates.preview-compose');
    Route::post('/email-templates/send', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'send'])->name('email-templates.send');
    Route::get('/email-templates/{emailTemplate}/edit', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('/email-templates/{emailTemplate}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::get('/email-templates/{emailTemplate}/preview', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::post('/email-templates/{emailTemplate}/test', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'test'])->name('email-templates.test');
    Route::delete('/email-templates/{emailTemplate}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
    
    // Logs SMS
    Route::get('/sms-logs', [\App\Http\Controllers\Admin\SmsLogController::class, 'index'])->name('sms-logs.index');
    Route::post('/sms-logs/test', [\App\Http\Controllers\Admin\SmsLogController::class, 'sendTestSms'])->name('sms-logs.test');
    Route::post('/sms-logs/mode', [\App\Http\Controllers\Admin\SmsLogController::class, 'updateMode'])->name('sms-logs.mode.update');
    
    // Logs Emails
    Route::get('/email-logs', [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->name('email-logs.index');
    Route::post('/email-logs/verify-user/{user}', [\App\Http\Controllers\Admin\EmailLogController::class, 'verifyUserEmail'])->name('email-logs.verify-user');
    
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
    
    // Gestion des webhooks Stripe
    Route::get('/stripe-webhooks', [AdminController::class, 'stripeWebhooks'])->name('stripe-webhooks.index');
    Route::get('/stripe-webhooks/{transaction}/details', [AdminController::class, 'stripeWebhookDetails'])->name('stripe-webhooks.details');
    
    // Gestion des abonnements
    Route::get('/subscriptions', [\App\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/sync', [\App\Http\Controllers\AdminSubscriptionController::class, 'syncAll'])->name('subscriptions.sync');
    Route::post('/subscriptions/force-manual', [\App\Http\Controllers\AdminSubscriptionController::class, 'forceManual'])->name('subscriptions.force_manual');
    Route::post('/subscriptions/stop-manual/{id}', [\App\Http\Controllers\AdminSubscriptionController::class, 'stopManual'])->name('subscriptions.stop_manual');
    Route::post('/subscriptions/update-manual/{id}', [\App\Http\Controllers\AdminSubscriptionController::class, 'updateManual'])->name('subscriptions.update_manual');
    
    // Gestion des factures
    Route::get('/factures', [AdminController::class, 'factures'])->name('factures.index');
    Route::get('/factures/create', [AdminController::class, 'createFacture'])->name('factures.create');
    Route::post('/factures', [AdminController::class, 'storeFacture'])->name('factures.store');
    Route::get('/factures/{facture}', [AdminController::class, 'showFacture'])->name('factures.show');
    Route::post('/factures/generate-subscription', [AdminController::class, 'generateSubscriptionInvoices'])->name('factures.generate-subscription');
    
    // Legacy / Specific Sync Actions (Redirigés ou gérés par le nouveau controller si implémentés, sinon garder AdminController pour l'instant pour la rétrocompatibilité des actions spécifiques utilisateur/entreprise si je ne les ai pas toutes migrées)
    // J'ai implémenté forceManual, mais pas syncUserSubscription ni cancel... 
    // Attends, mon AdminSubscriptionController n'est pas complet ! Il manque les méthodes sync/cancel individuelles !
    // Je dois les ajouter au controller avant de changer les routes, OU rediriger vers AdminController pour celles-là.
    // MAIS AdminController utilse l'ancienne logique ? Non, il appelle StripeSubscriptionSyncService qui est à jour.
    // Donc je peux garder AdminController pour les actions sync/cancel individuelles si je ne les ai pas copiées.
    
    Route::post('/subscriptions/user/{subscription}/sync', [\App\Http\Controllers\AdminController::class, 'syncUserSubscription'])->name('subscriptions.user.sync');
    Route::post('/subscriptions/user/{subscription}/cancel', [\App\Http\Controllers\AdminController::class, 'cancelUserSubscription'])->name('subscriptions.user.cancel');
    Route::post('/subscriptions/user/{user}/purge/{id}', [\App\Http\Controllers\AdminController::class, 'purgeSubscription'])->name('subscriptions.user.purge');
    Route::post('/subscriptions/entreprise/{subscription}/sync', [\App\Http\Controllers\AdminController::class, 'syncEntrepriseSubscription'])->name('subscriptions.entreprise.sync');
    Route::post('/subscriptions/entreprise/{subscription}/cancel', [\App\Http\Controllers\AdminController::class, 'cancelEntrepriseSubscription'])->name('subscriptions.entreprise.cancel');
    
    // Gestion des essais gratuits
    Route::get('/essais-gratuits', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'index'])->name('essais-gratuits.index');
    Route::post('/essais-gratuits/accorder', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'accorder'])->name('essais-gratuits.accorder');
    Route::post('/essais-gratuits/{essai}/revoquer', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'revoquer'])->name('essais-gratuits.revoquer');
    Route::post('/essais-gratuits/{essai}/prolonger', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'prolonger'])->name('essais-gratuits.prolonger');
    Route::get('/essais-gratuits/export', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'export'])->name('essais-gratuits.export');
    Route::get('/essais-gratuits/stats', [\App\Http\Controllers\Admin\EssaiGratuitController::class, 'statsApi'])->name('essais-gratuits.stats');
    
    // Gestion des sauvegardes de base de données
    Route::get('/database', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'index'])->name('database.index');
    Route::post('/database/backup', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'create'])->name('database.backup');
    Route::get('/database/backup/{filename}/download', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'download'])->name('database.download');
    Route::post('/database/backup/{filename}/restore', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'restore'])->name('database.restore');
    Route::delete('/database/backup/{filename}', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'destroy'])->name('database.destroy');
    Route::get('/database/info', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'getDatabaseInfo'])->name('database.info');
    Route::get('/database/table-data', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'getDatabaseInfo'])->name('database.table-data');
    Route::post('/database/clean', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'clean'])->name('database.clean');
    Route::post('/database/import', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'import'])->name('database.import');
});

// Route publique pour sauvegarde automatique (protégée par token)
// Utilisable depuis Docker/cron externe
Route::get('/autosave', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'autoBackup'])->name('database.autosave');
Route::post('/autosave', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'autoBackup'])->name('database.autosave.post');

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




// Route de test email (à supprimer en production)
Route::get('/test-email', function() {
    if (!auth()->check() || !auth()->user()->is_admin) {
        abort(403, 'Accès refusé');
    }
    
    try {
        $testEmail = request()->get('email', auth()->user()->email);
        
        \Illuminate\Support\Facades\Mail::raw('Test email depuis Allo Tata - Configuration SMTP', function ($message) use ($testEmail) {
            $message->to($testEmail)
                    ->subject('Test de configuration email - Allo Tata');
        });
        
        return response()->json([
            'success' => true,
            'message' => "Email de test envoyé à {$testEmail}",
            'config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
})->middleware('auth');

// Route de debug temporaire
require __DIR__ . '/debug_temp.php';

// ⚠️ ROUTE DE SECOURS D'URGENCE - À GARDER SECRÈTE
// Chemin aléatoire pour éviter la découverte accidentelle
// Format: /emergency-recovery-[hash-aléatoire]?token=[token-secret]
// Le hash est généré à partir de APP_KEY pour être unique à chaque installation
$emergencyHash = substr(md5(config('app.key') . 'emergency-recovery-allotata'), 0, 16);
Route::get("/emergency-recovery-{$emergencyHash}", [\App\Http\Controllers\EmergencyRecoveryController::class, 'index'])->name('emergency.recovery');
Route::post("/emergency-recovery-{$emergencyHash}", function(Request $request) {
    $controller = app(\App\Http\Controllers\EmergencyRecoveryController::class);
    
    $action = $request->input('action');
    $userId = $request->input('user_id');
    $filename = $request->input('filename');
    
    // Vérifier si c'est une requête AJAX/JSON (pour les actions qui nécessitent du JSON)
    $isJsonRequest = $request->wantsJson() || $request->expectsJson() || 
                     in_array($action, ['import_backup', 'restore_backup']);
    
    if ($action === 'create_admin') {
        return $controller->createAdmin($request);
    } elseif ($action === 'promote' && $userId) {
        return $controller->promoteToAdmin($request, $userId);
    } elseif ($action === 'login_as' && $userId) {
        return $controller->loginAs($request, $userId);
    } elseif ($action === 'import_backup') {
        return $controller->importBackup($request);
    } elseif ($action === 'restore_backup' && $filename) {
        return $controller->restoreBackup($request, $filename);
    }
    
    // Retourner du JSON si c'est une requête JSON, sinon HTML
    if ($isJsonRequest) {
        return response()->json([
            'success' => false,
            'message' => 'Action invalide',
        ], 400);
    }
    
    return back()->with('error', 'Action invalide');
});

// Route pour obtenir la progression de la restauration
Route::get("/emergency-recovery-{$emergencyHash}/progress/{progressId}", [\App\Http\Controllers\EmergencyRecoveryController::class, 'getRestoreProgress'])->name('emergency.recovery.progress');
