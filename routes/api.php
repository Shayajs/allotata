<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ApiHomeController;
use App\Http\Controllers\Api\GoogleBookingController;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// =============================================
// PAGE DE GARDE — https://api.allotata.fr/
// =============================================

Route::get('/', [ApiHomeController::class, 'show'])->name('api.home');

// =============================================
// API PUBLIQUE — https://api.allotata.fr/v1/
// =============================================
//
// Lecture seule, sans authentification, limitee par IP.
// Les chemins historiques /api/... restent valides pour l'application elle-meme :
// la v1 les expose, elle ne les remplace pas.
//
// Il n'y a pas de v2 prevue : tant que la v1 suffit, elle reste la seule version
// publique. La v3 ci-dessous n'est pas une suite, c'est la version imposee par
// la specification Google.
//

Route::prefix('v1')->name('api.v1.')->middleware('throttle:12,1')->group(function () {
    Route::post('/auth/login', [V1\NativeAuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/2fa', [V1\NativeAuthController::class, 'twoFactor'])->name('auth.2fa');
    Route::post('/auth/2fa/renvoyer', [V1\NativeAuthController::class, 'resendTwoFactor'])->name('auth.2fa.renvoyer');
});

Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {

    // Index machine des endpoints (meme catalogue que la page de garde)
    Route::get('/', [ApiHomeController::class, 'index'])->name('index');

    // Annuaire des entreprises publiees
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])
        ->name('search.autocomplete');

    // Adresses francaises : autocompletion, communes, geocodage
    Route::get('/address/search', [AddressController::class, 'search'])->name('address.search');
    Route::get('/address/cities', [AddressController::class, 'searchCities'])->name('address.cities');
    Route::get('/address/geocode', [AddressController::class, 'geocode'])->name('address.geocode');
});

// =============================================
// API DE GESTION — https://api.allotata.fr/v1/
// =============================================
//
// Meme version, mais derriere un jeton personnel : ici on lit les donnees d'un
// compte et de ses entreprises. Les jetons se creent dans les reglages
// (dash.allotata.fr/settings/api) et voyagent dans Authorization: Bearer.
//
// Lecture + accept/refus Pocket : les transitions passent par
// ReservationStatusService (memes e-mails / notifs que le web).
//

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['api.token', 'throttle:120,1'])
    ->group(function () {

        // Le compte derriere le jeton, et les entreprises qu'il ouvre
        Route::get('/moi', [V1\CompteController::class, 'show'])->name('moi');
        Route::get('/sync', [V1\SyncController::class, 'show'])->name('sync');
        Route::get('/mes-reservations', [V1\MesReservationsController::class, 'index'])->name('mes-reservations');
        Route::get('/messagerie/conversations', [V1\MessagerieController::class, 'conversations'])->name('messagerie.conversations');
        Route::get('/messagerie/conversations/{conversation}/messages', [V1\MessagerieController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('messagerie.messages');
        Route::get('/factures', [V1\FactureApiController::class, 'index'])->name('factures.index');
        Route::get('/factures/{facture}/pdf', [V1\FactureApiController::class, 'pdf'])
            ->whereNumber('facture')
            ->name('factures.pdf');
        Route::post('/device/fcm', [V1\DeviceFcmController::class, 'store'])->name('device.fcm');

        Route::get('/entreprises', [V1\EntrepriseController::class, 'index'])->name('entreprises.index');

        Route::prefix('entreprises/{slug}')->name('entreprises.')->group(function () {
            Route::get('/', [V1\EntrepriseController::class, 'show'])->name('show');
            Route::get('/statistiques', [V1\EntrepriseController::class, 'statistiques'])->name('statistiques');

            // Catalogue
            Route::get('/services', [V1\CatalogueController::class, 'services'])->name('services');
            Route::get('/produits', [V1\CatalogueController::class, 'produits'])->name('produits');

            // Activite
            Route::get('/reservations', [V1\ReservationController::class, 'index'])->name('reservations.index');
            Route::get('/reservations/{reservation}', [V1\ReservationController::class, 'show'])
                ->whereNumber('reservation')
                ->name('reservations.show');
            Route::post('/reservations/{reservation}/accepter', [V1\ReservationWriteController::class, 'accepter'])
                ->whereNumber('reservation')
                ->name('reservations.accepter');
            Route::post('/reservations/{reservation}/refuser', [V1\ReservationWriteController::class, 'refuser'])
                ->whereNumber('reservation')
                ->name('reservations.refuser');
            Route::get('/disponibilites', [V1\ReservationController::class, 'disponibilites'])->name('disponibilites');
            Route::get('/clients', [V1\ClientController::class, 'index'])->name('clients');
            Route::get('/finances', [V1\FinanceController::class, 'index'])->name('finances');
        });
    });

// =============================================
// RESERVE WITH GOOGLE — Booking Server API v3
// =============================================
//
// Ces endpoints sont appelés par les serveurs Google.
// Protégés par un middleware vérifiant le header Authorization.
//
// URL de base : https://allotata.fr/api/v3/
//
// Clé d'API à configurer dans .env : GOOGLE_RWG_API_KEY
// Google envoie : Authorization: Bearer <GOOGLE_RWG_API_KEY>
//

Route::prefix('v3')->middleware('google.rwg.auth')->group(function () {

    // HealthCheck — Google vérifie que le serveur est opérationnel
    Route::get('/HealthCheck', [GoogleBookingController::class, 'healthCheck'])
        ->name('rwg.health-check');

    // BatchAvailabilityLookup — Confirmation temps réel des créneaux
    Route::post('/BatchAvailabilityLookup', [GoogleBookingController::class, 'batchAvailabilityLookup'])
        ->name('rwg.batch-availability');

    // CreateBooking — Création d'une réservation depuis Google
    Route::post('/CreateBooking', [GoogleBookingController::class, 'createBooking'])
        ->name('rwg.create-booking');

    // UpdateBooking — Mise à jour / annulation d'une réservation
    Route::post('/UpdateBooking', [GoogleBookingController::class, 'updateBooking'])
        ->name('rwg.update-booking');

    // GetBookingStatus — Récupération du statut d'une réservation
    Route::post('/GetBookingStatus', [GoogleBookingController::class, 'getBookingStatus'])
        ->name('rwg.get-booking-status');
});
