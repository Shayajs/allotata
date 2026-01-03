<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schema;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance à tous les proxies pour le HTTPS
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
        
        // Exception CSRF pour les webhooks Stripe
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Capturer les erreurs et les stocker dans la base de données
        $exceptions->report(function (\Throwable $e) {
            // Ne capturer que les erreurs de niveau error et plus
            if (in_array($e->getCode(), [0, 500, 404, 403, 401]) || $e instanceof \Error || $e instanceof \Exception) {
                try {
                    // Vérifier que la table existe avant d'essayer d'insérer
                    if (Schema::hasTable('error_logs')) {
                        \App\Models\ErrorLog::create([
                            'level' => $e instanceof \Error ? 'error' : 'exception',
                            'message' => $e->getMessage(),
                            'context' => [
                                'exception' => get_class($e),
                                'code' => $e->getCode(),
                            ],
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => substr($e->getTraceAsString(), 0, 5000), // Limiter la taille
                            'url' => request()->fullUrl(),
                            'method' => request()->method(),
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'user_id' => auth()->id(),
                        ]);
                    }
                } catch (\Exception $logException) {
                    // Si l'enregistrement de l'erreur échoue, on ne fait rien pour éviter une boucle infinie
                }
            }
        });
    })->create();
