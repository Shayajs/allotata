<?php

namespace App\Console\Commands;

use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Console\Command;

class SeedEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-templates:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charger les templates d\'emails par défaut dans la base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Chargement des templates d\'emails...');
        
        $seeder = new EmailTemplateSeeder();
        $seeder->run();
        
        $this->info('Templates d\'emails chargés avec succès !');
        
        return Command::SUCCESS;
    }
}
