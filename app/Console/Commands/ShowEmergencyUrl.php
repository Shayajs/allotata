<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowEmergencyUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emergency:url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Afficher l\'URL de récupération d\'urgence (route de secours admin)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hash = substr(md5(config('app.key') . 'emergency-recovery-allotata'), 0, 16);
        $token = env('EMERGENCY_RECOVERY_TOKEN', 'NON-CONFIGURÉ');
        
        if ($token === 'NON-CONFIGURÉ') {
            $this->error('⚠️  Le token EMERGENCY_RECOVERY_TOKEN n\'est pas configuré dans .env');
            $this->info('');
            $this->info('Ajoutez cette ligne dans votre .env :');
            $this->comment('EMERGENCY_RECOVERY_TOKEN=votre-token-super-secret-ici');
            $this->info('');
            $this->info('Générez un token avec :');
            $this->comment('php artisan tinker');
            $this->comment('echo \Illuminate\Support\Str::random(64);');
            return Command::FAILURE;
        }
        
        $url = url("/emergency-recovery-{$hash}?token={$token}");
        
        $this->info('');
        $this->warn('═══════════════════════════════════════════════════════════');
        $this->warn('  ⚠️  ROUTE DE SECOURS D\'URGENCE - CONFIDENTIEL');
        $this->warn('═══════════════════════════════════════════════════════════');
        $this->info('');
        $this->line('URL de récupération :');
        $this->comment($url);
        $this->info('');
        $this->warn('⚠️  CONSERVEZ CETTE URL EN SÉCURITÉ');
        $this->warn('⚠️  NE LA PARTAGEZ JAMAIS');
        $this->warn('⚠️  CHANGEZ LE TOKEN APRÈS CHAQUE UTILISATION');
        $this->info('');
        $this->warn('═══════════════════════════════════════════════════════════');
        $this->info('');
        
        return Command::SUCCESS;
    }
}
