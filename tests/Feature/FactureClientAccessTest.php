<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Facturation\FactureEmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FactureClientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_ne_peut_pas_telecharger_avant_paiement(): void
    {
        Mail::fake();
        $gerant = User::factory()->create(['est_gerant' => true]);
        $entreprise = Entreprise::factory()->create(['user_id' => $gerant->id]);
        $client = User::factory()->create(['est_client' => true]);
        $reservation = Reservation::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $client->id,
            'statut' => 'confirmee',
        ]);

        $facture = app(FactureEmissionService::class)->emettrePourReservation($reservation);

        $this->actingAs($client)
            ->get(route('factures.download', $facture->id))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('factures.show', $facture->id))
            ->assertForbidden();

        $this->actingAs($gerant)
            ->get(route('factures.entreprise.download', [$entreprise->slug, $facture->id]))
            ->assertOk();

        $reservation->update(['est_paye' => true, 'date_paiement' => now()]);
        app(FactureEmissionService::class)->acquitter($facture->fresh(), now());

        $this->actingAs($client)
            ->get(route('factures.download', $facture->id))
            ->assertOk();
    }
}
