<?php

namespace App\Services\Audit;

use App\Models\Contact;
use App\Models\Ticket;

class ContactChecker extends BaseChecker
{
    public function key(): string
    {
        return 'contacts';
    }

    public function label(): string
    {
        return 'Contacts & Support';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Messages de contact non lus
        $unreadContacts = Contact::where('est_lu', false)->count();
        $totalContacts30d = Contact::where('created_at', '>=', now()->subDays(30))->count();
        $oldUnread = Contact::where('est_lu', false)
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        $items[] = ['label' => 'Messages non lus', 'value' => $unreadContacts, 'severity' => $unreadContacts > 10 ? 'critical' : ($unreadContacts > 3 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Non lus depuis +3 jours', 'value' => $oldUnread, 'severity' => $oldUnread > 5 ? 'critical' : ($oldUnread > 0 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Messages reçus (30j)', 'value' => $totalContacts30d, 'severity' => 'info'];

        $score -= min(20, $unreadContacts * 2);
        $score -= min(20, $oldUnread * 4);

        // Tickets de support
        $openTickets = Ticket::where('statut', 'ouvert')->count();
        $urgentTickets = Ticket::where('statut', 'ouvert')
            ->where('priorite', 'haute')
            ->count();
        $oldTickets = Ticket::where('statut', 'ouvert')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        $items[] = ['label' => 'Tickets ouverts', 'value' => $openTickets, 'severity' => $openTickets > 10 ? 'critical' : ($openTickets > 5 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Tickets urgents', 'value' => $urgentTickets, 'severity' => $urgentTickets > 0 ? 'critical' : 'ok'];
        $items[] = ['label' => 'Tickets ouverts >7j', 'value' => $oldTickets, 'severity' => $oldTickets > 3 ? 'critical' : ($oldTickets > 0 ? 'warning' : 'ok')];

        $score -= min(15, $urgentTickets * 5);
        $score -= min(15, $oldTickets * 3);

        // Temps de réponse moyen (contacts lus)
        $avgResponseTime = Contact::where('est_lu', true)
            ->whereNotNull('lu_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(JULIANDAY(lu_at) - JULIANDAY(created_at)) * 24 as avg_hours')
            ->value('avg_hours');

        $avgHours = $avgResponseTime ? round($avgResponseTime, 1) : null;
        $items[] = [
            'label' => 'Temps de réponse moyen',
            'value' => $avgHours !== null ? $avgHours . 'h' : 'N/A',
            'severity' => $avgHours === null ? 'info' : ($avgHours > 48 ? 'critical' : ($avgHours > 24 ? 'warning' : 'ok')),
        ];

        if ($avgHours && $avgHours > 48) {
            $score -= 10;
        }

        if ($oldUnread > 3) {
            $recommendations[] = 'Des messages de contact attendent depuis plus de 3 jours — traiter en priorité.';
        }
        if ($urgentTickets > 0) {
            $recommendations[] = "Il y a {$urgentTickets} ticket(s) urgent(s) non résolu(s).";
        }
        if ($oldTickets > 3) {
            $recommendations[] = 'Certains tickets sont ouverts depuis plus d\'une semaine.';
        }
        if ($avgHours && $avgHours > 24) {
            $recommendations[] = 'Le temps de réponse moyen dépasse 24h — améliorer la réactivité.';
        }

        return $this->result($score, $items, $recommendations);
    }
}
