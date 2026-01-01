<?php

namespace App\Console\Commands;

use App\Models\EntrepriseInvitation;
use Illuminate\Console\Command;

class NettoyerInvitationsExpirees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:nettoyer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime les invitations expirées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Nettoyage des invitations expirées...');

        $invitationsExpirees = EntrepriseInvitation::whereIn('statut', ['en_attente_compte', 'en_attente_acceptation'])
            ->where(function($query) {
                $query->whereNotNull('expire_at')
                      ->where('expire_at', '<', now());
            })
            ->get();

        $count = $invitationsExpirees->count();

        foreach ($invitationsExpirees as $invitation) {
            $invitation->delete();
        }

        $this->info("{$count} invitation(s) expirée(s) supprimée(s).");

        return Command::SUCCESS;
    }
}
