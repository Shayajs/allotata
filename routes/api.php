<?php

use App\Http\Controllers\Api\GoogleBookingController;
use Illuminate\Support\Facades\Route;

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
