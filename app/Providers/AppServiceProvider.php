<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Entreprise;
use App\Observers\ReservationObserver;
use App\Observers\UserObserver;
use App\Observers\EntrepriseObserver;
use App\Listeners\LogEmailSent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Mail\Events\MessageSent;

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
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\CourseLesson::class => \App\Policies\CourseLessonPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        User::observe(UserObserver::class);
        Entreprise::observe(EntrepriseObserver::class);

        // Enregistrer les policies
        Gate::policy(\App\Models\CourseLesson::class, \App\Policies\CourseLessonPolicy::class);

        // Configurer l'adresse d'expéditeur par défaut pour tous les emails
        Mail::alwaysFrom(
            env('MAIL_FROM_ADDRESS', 'noreply@allotata.fr'),
            env('MAIL_FROM_NAME', 'Allo Tata')
        );

        // Enregistrer le listener pour logger les emails envoyés
        Event::listen(MessageSent::class, LogEmailSent::class);
    }
}
