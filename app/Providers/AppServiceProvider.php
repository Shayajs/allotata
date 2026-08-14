<?php

namespace App\Providers;

use App\Services\Payments\ProviderResolver;
use App\Services\Payments\Providers\StripeProvider;
use App\Models\Reservation;
use App\Models\RendezVous;
use App\Models\User;
use App\Models\Entreprise;
use App\Models\ErrorLog;
use App\Observers\ErrorLogObserver;
use App\Observers\ReservationObserver;
use App\Observers\RendezVousObserver;
use App\Observers\UserObserver;
use App\Observers\EntrepriseObserver;
use App\Listeners\LogEmailSent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\AccountAccessService;
use App\Support\SubdomainHost;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Mail\Events\MessageSent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderResolver::class, function () {
            return new ProviderResolver([
                new StripeProvider(),
            ]);
        });
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
        RendezVous::observe(RendezVousObserver::class);
        User::observe(UserObserver::class);
        Entreprise::observe(EntrepriseObserver::class);
        ErrorLog::observe(ErrorLogObserver::class);

        // Enregistrer les policies
        Gate::policy(\App\Models\CourseLesson::class, \App\Policies\CourseLessonPolicy::class);

        // Configurer l'adresse d'expéditeur par défaut pour tous les emails
        Mail::alwaysFrom(
            env('MAIL_FROM_ADDRESS', 'noreply@allotata.fr'),
            env('MAIL_FROM_NAME', 'Allo Tata')
        );

        // Enregistrer le listener pour logger les emails envoyés
        Event::listen(MessageSent::class, LogEmailSent::class);

        // Personnaliser la durée du cookie "remember me" pour qu'il corresponde à la durée de session
        // Par défaut, Laravel utilise 2 semaines (20160 minutes), on l'étend à 10 ans (5256000 minutes)
        Auth::extend('session', function ($app, $name, array $config) {
            $guard = new \App\Auth\CustomSessionGuard(
                $name,
                Auth::createUserProvider($config['provider'] ?? null),
                $app['session.store'],
                $app['request']
            );
            
            // IMPORTANT: Injecter le CookieJar pour que "remember me" fonctionne
            if (isset($app['cookie'])) {
                $guard->setCookieJar($app['cookie']);
            }
            
            // Injecter le dispatcher d'événements
            if (isset($app['events'])) {
                $guard->setDispatcher($app['events']);
            }
            
            // Injecter le request pour la détection du recaller
            if (isset($app['request'])) {
                $guard->setRequest($app['request']);
            }
            
            return $guard;
        });

        $urlGenerator = URL::getFacadeRoot();
        if (method_exists($urlGenerator, 'formatPathUsing') && method_exists($urlGenerator, 'formatHostUsing')) {
            // formatHostUsing est toujours appele juste avant formatPathUsing dans
            // UrlGenerator::format() : on laisse outboundUrl() reconstruire l'URL entiere
            // pour pouvoir renvoyer un lien vers un autre sous-domaine.
            URL::formatHostUsing(function (string $root) {
                SubdomainHost::rememberUrlRoot($root);

                return '';
            });

            URL::formatPathUsing(function (string $path, $route = null) {
                return SubdomainHost::outboundUrl($path);
            });
        }

        Authenticate::redirectUsing(fn ($request) => SubdomainHost::guestLoginUrl($request));

        View::composer('*', function ($view) {
            $accountAccess = app(AccountAccessService::class);

            $view->with('accountAccess', $accountAccess);
            $view->with('accountAccessQuery', $accountAccess->buildQuery());
            $view->with('subdomainHost', SubdomainHost::current());
        });

        View::composer('partials.favicon', function ($view) {
            $current = $view->offsetExists('entreprise') ? $view['entreprise'] : null;
            if ($current instanceof Entreprise) {
                return;
            }

            $resolved = \App\Helpers\SiteHelper::resolveEntrepriseFromRequest();
            if ($resolved) {
                $view->with('entreprise', $resolved);
            }
        });
    }
}
