<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Observers\ReservationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);

        // Configurer l'adresse d'expéditeur par défaut pour tous les emails
        Mail::alwaysFrom(
            env('MAIL_FROM_ADDRESS', 'noreply@allotata.fr'),
            env('MAIL_FROM_NAME', 'Allo Tata')
        );
    }
}
