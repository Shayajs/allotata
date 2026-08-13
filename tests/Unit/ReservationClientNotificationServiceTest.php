<?php

namespace Tests\Unit;

use App\Services\ReservationClientNotificationService;
use PHPUnit\Framework\TestCase;

class ReservationClientNotificationServiceTest extends TestCase
{
    public function test_apply_placeholders(): void
    {
        $service = new ReservationClientNotificationService();

        $result = $service->applyPlaceholders(
            '{nom_client}, RDV chez {nom_entreprise} le {date} à {heure} pour {prestations} ({lieu})',
            [
                'nom_client' => 'Léa',
                'nom_entreprise' => 'Salon Vert',
                'prestations' => 'Coupe',
                'date' => '14/08/2026',
                'heure' => '14h30',
                'lieu' => 'Paris',
                'date_complete' => '14/08/2026 à 14:30',
            ]
        );

        $this->assertSame(
            'Léa, RDV chez Salon Vert le 14/08/2026 à 14h30 pour Coupe (Paris)',
            $result
        );
    }
}
