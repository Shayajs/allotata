<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class DiagnosticEmailCommand extends Command
{
    protected $signature = 'email:diagnostic {--send-test : Envoyer un email de test} {--to= : Adresse email de test}';
    protected $description = 'Diagnostiquer la configuration email et les templates';

    public function handle(): int
    {
        $this->info('=== DIAGNOSTIC EMAIL ALLOTATA ===');
        $this->newLine();

        $hasErrors = false;

        // 1. Configuration SMTP
        $this->info('1. Configuration SMTP');
        $config = config('mail');
        $mailer = $config['default'];
        $smtp = $config['mailers'][$mailer] ?? [];

        $this->table(['Paramètre', 'Valeur'], [
            ['Mailer', $mailer],
            ['Host', $smtp['host'] ?? 'N/A'],
            ['Port', $smtp['port'] ?? 'N/A'],
            ['Encryption', $smtp['encryption'] ?? 'aucune'],
            ['Username', $smtp['username'] ? '✓ défini' : '✗ NULL'],
            ['Password', $smtp['password'] ? '✓ défini' : '✗ NULL'],
            ['From address', $config['from']['address'] ?? 'N/A'],
            ['From name', $config['from']['name'] ?? 'N/A'],
        ]);

        if ($mailer === 'smtp' && empty($smtp['username'])) {
            $this->error('SMTP username est NULL ! Les emails ne peuvent pas être authentifiés.');
            $this->warn('→ Vérifiez MAIL_USERNAME dans le .env (ne pas mettre "null", supprimer la ligne ou mettre la vraie valeur)');
            $hasErrors = true;
        }
        if ($mailer === 'smtp' && empty($smtp['password'])) {
            $this->error('SMTP password est NULL ! Les emails ne peuvent pas être authentifiés.');
            $this->warn('→ Vérifiez MAIL_PASSWORD dans le .env');
            $hasErrors = true;
        }
        if ($mailer === 'log') {
            $this->warn('Le mailer est "log" — les emails sont écrits dans les logs, pas envoyés.');
        }

        $this->newLine();

        // 2. Templates email
        $this->info('2. Templates email en base de données');

        try {
            $totalTemplates = EmailTemplate::count();
            $activeTemplates = EmailTemplate::where('is_active', true)->count();

            if ($totalTemplates === 0) {
                $this->error('AUCUN template email en base ! Tous les emails via EmailHelper échoueront silencieusement.');
                $this->warn('→ Exécutez : php artisan db:seed --class=EmailTemplateSeeder');
                $hasErrors = true;
            } else {
                $this->info("Templates totaux: {$totalTemplates} | Actifs: {$activeTemplates}");

                $requiredTypes = [
                    'welcome', 'reservation_confirmation_client', 'reservation_confirmation_gerant',
                    'reservation_reminder', 'payment_received', 'new_message',
                    'reservation_cancelled_client', 'weekly_report', 'email_verification',
                    'payment_authentication_required',
                ];

                $existing = EmailTemplate::pluck('type')->toArray();
                $missing = array_diff($requiredTypes, $existing);

                if (count($missing) > 0) {
                    $this->warn('Templates manquants : ' . implode(', ', $missing));
                    $this->warn('→ Exécutez : php artisan db:seed --class=EmailTemplateSeeder');
                    $hasErrors = true;
                }

                $inactive = EmailTemplate::where('is_active', false)->pluck('type')->toArray();
                if (count($inactive) > 0) {
                    $this->warn('Templates désactivés : ' . implode(', ', $inactive));
                }
            }
        } catch (\Exception $e) {
            $this->error('Impossible de lire la table email_templates : ' . $e->getMessage());
            $hasErrors = true;
        }

        $this->newLine();

        // 3. Tables de queue
        $this->info('3. État des queues');
        try {
            $jobsCount = DB::table('jobs')->count();
            $failedCount = DB::table('failed_jobs')->count();
            $this->line("Jobs en attente: {$jobsCount} | Jobs échoués: {$failedCount}");
            if ($jobsCount > 0) {
                $this->warn("Il y a {$jobsCount} jobs en attente — le queue worker tourne-t-il ?");
            }
            if ($failedCount > 0) {
                $this->warn("Il y a {$failedCount} jobs échoués — consultez-les avec : php artisan queue:failed");
            }
        } catch (\Exception $e) {
            $this->warn('Tables de queue inaccessibles : ' . $e->getMessage());
        }

        $this->newLine();

        // 4. Email logs
        $this->info('4. Logs d\'emails récents');
        try {
            $logCount = DB::table('email_logs')->count();
            $this->line("Total email_logs: {$logCount}");
            if ($logCount > 0) {
                $recent = DB::table('email_logs')->orderByDesc('id')->limit(5)->get();
                $rows = $recent->map(fn ($l) => [
                    $l->recipient_email,
                    $l->subject ?? 'N/A',
                    $l->status ?? 'N/A',
                    $l->created_at ?? 'N/A',
                ])->toArray();
                $this->table(['Destinataire', 'Sujet', 'Statut', 'Date'], $rows);
            }
        } catch (\Exception $e) {
            $this->warn('Table email_logs inaccessible : ' . $e->getMessage());
        }

        $this->newLine();

        // 5. Test d'envoi
        if ($this->option('send-test')) {
            $to = $this->option('to') ?? 'test@example.com';
            $this->info("5. Test d'envoi vers {$to}");

            try {
                Mail::raw('Ceci est un email de test envoyé par la commande email:diagnostic.', function ($message) use ($to) {
                    $message->to($to)->subject('Test diagnostic email - Allo Tata');
                });
                $this->info("Email de test envoyé avec succès à {$to}");
            } catch (\Exception $e) {
                $this->error("ÉCHEC de l'envoi : " . $e->getMessage());
                $hasErrors = true;
            }
        }

        $this->newLine();
        $this->info('=== FIN DU DIAGNOSTIC ===');

        if ($hasErrors) {
            $this->error('Des problèmes ont été détectés. Consultez les détails ci-dessus.');
            return 1;
        }

        $this->info('Aucun problème détecté.');
        return 0;
    }
}
