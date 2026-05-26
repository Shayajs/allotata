<?php

namespace App\Services\Audit;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Route;

class RouteChecker extends BaseChecker
{
    public function key(): string
    {
        return 'routes';
    }

    public function label(): string
    {
        return 'Routes & Navigation';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Erreurs 404 fréquentes (7 derniers jours)
        $notFoundErrors = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->where(function ($q) {
                $q->where('message', 'like', '%404%')
                    ->orWhere('message', 'like', '%NotFoundHttpException%')
                    ->orWhere('message', 'like', '%No query results%');
            })
            ->count();

        $items[] = ['label' => 'Erreurs 404 (7j)', 'value' => $notFoundErrors, 'severity' => $notFoundErrors > 50 ? 'critical' : ($notFoundErrors > 20 ? 'warning' : 'ok')];
        $score -= min(15, intdiv($notFoundErrors, 10));

        // URLs les plus en erreur 404
        $top404Urls = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->where(function ($q) {
                $q->where('message', 'like', '%404%')
                    ->orWhere('message', 'like', '%NotFoundHttpException%');
            })
            ->whereNotNull('url')
            ->selectRaw('url, COUNT(*) as count')
            ->groupBy('url')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        foreach ($top404Urls as $url) {
            $items[] = ['label' => '404: ' . \Str::limit($url->url, 50), 'value' => $url->count . ' fois', 'severity' => $url->count > 10 ? 'warning' : 'info'];
        }

        if ($notFoundErrors > 50) {
            $recommendations[] = 'Beaucoup de 404 — vérifier les liens cassés et ajouter des redirections.';
        }

        // Erreurs 500 (7 derniers jours)
        $serverErrors = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->where('level', 'error')
            ->where(function ($q) {
                $q->where('message', 'not like', '%404%')
                    ->where('message', 'not like', '%NotFoundHttpException%');
            })
            ->count();

        $items[] = ['label' => 'Erreurs serveur 500 (7j)', 'value' => $serverErrors, 'severity' => $serverErrors > 20 ? 'critical' : ($serverErrors > 5 ? 'warning' : 'ok')];
        $score -= min(20, $serverErrors * 2);

        if ($serverErrors > 10) {
            $recommendations[] = "Beaucoup d'erreurs serveur ({$serverErrors}) — investiguer les causes.";
        }

        // Nombre de routes enregistrées
        $routes = Route::getRoutes();
        $routeCount = count($routes);
        $items[] = ['label' => 'Routes enregistrées', 'value' => $routeCount, 'severity' => 'info'];

        // Routes sans middleware
        $unprotectedRoutes = 0;
        foreach ($routes as $route) {
            $middlewares = $route->middleware();
            if (empty($middlewares) && !in_array($route->uri(), ['/', 'up', 'sanctum/csrf-cookie'])) {
                $unprotectedRoutes++;
            }
        }
        $items[] = ['label' => 'Routes sans middleware', 'value' => $unprotectedRoutes, 'severity' => $unprotectedRoutes > 20 ? 'warning' : 'ok'];

        // Erreurs par méthode HTTP
        $methodErrors = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('method')
            ->selectRaw('method, COUNT(*) as count')
            ->groupBy('method')
            ->orderByDesc('count')
            ->get();

        foreach ($methodErrors as $method) {
            $items[] = ['label' => "Erreurs {$method->method}", 'value' => $method->count, 'severity' => 'info'];
        }

        return $this->result($score, $items, $recommendations);
    }
}
