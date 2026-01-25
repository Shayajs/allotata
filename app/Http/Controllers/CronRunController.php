<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Route HTTP pour déclencher les tâches planifiées (échéances, réconciliation, essais).
 * Protégée par token secret. Utilisable manuellement (lien) ou en cron externe (curl/wget).
 */
class CronRunController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = env('CRON_SECRET', 'change-me-in-production');
        $provided = $request->get('token') ?? $request->header('X-Cron-Token');

        if (empty($secret) || $secret === 'change-me-in-production') {
            Log::warning('CRON_SECRET non configuré dans .env');
            return response()->json([
                'success' => false,
                'message' => 'CRON_SECRET non configuré. Ajoutez CRON_SECRET dans .env.',
            ], 500);
        }

        if ($provided !== $secret) {
            Log::warning('Tentative d\'accès /cron-run avec token invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
            ], 403);
        }

        $results = [];

        try {
            Artisan::call('subscriptions:check-echeances');
            $results['check_echeances'] = ['exit' => 0, 'output' => trim(Artisan::output())];
        } catch (\Throwable $e) {
            Log::error('CronRun check-echeances failed', ['error' => $e->getMessage()]);
            $results['check_echeances'] = ['exit' => 1, 'error' => $e->getMessage()];
        }

        try {
            Artisan::call('subscriptions:reconcile-echeances');
            $results['reconcile_echeances'] = ['exit' => 0, 'output' => trim(Artisan::output())];
        } catch (\Throwable $e) {
            Log::error('CronRun reconcile-echeances failed', ['error' => $e->getMessage()]);
            $results['reconcile_echeances'] = ['exit' => 1, 'error' => $e->getMessage()];
        }

        try {
            Artisan::call('essais:check-expiration');
            $results['essais_check'] = ['exit' => 0, 'output' => trim(Artisan::output())];
        } catch (\Throwable $e) {
            Log::error('CronRun essais:check-expiration failed', ['error' => $e->getMessage()]);
            $results['essais_check'] = ['exit' => 1, 'error' => $e->getMessage()];
        }

        $hasError = collect($results)->contains(fn ($r) => ($r['exit'] ?? 1) !== 0);

        Log::info('CronRun exécuté via HTTP', ['results' => array_keys($results), 'ip' => $request->ip()]);

        return response()->json([
            'success' => !$hasError,
            'message' => $hasError ? 'Une ou plusieurs tâches ont échoué.' : 'Tâches exécutées.',
            'results' => $results,
            'at' => now()->toIso8601String(),
        ], $hasError ? 500 : 200);
    }
}
