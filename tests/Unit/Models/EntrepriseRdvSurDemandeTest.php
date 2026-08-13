<?php

namespace Tests\Unit\Models;

use App\Models\Entreprise;
use PHPUnit\Framework\TestCase;

class EntrepriseRdvSurDemandeTest extends TestCase
{
    public function test_prend_rdv_sur_demande_quand_l_option_est_activee(): void
    {
        $entreprise = new Entreprise(['rdv_uniquement_messagerie' => true]);

        $this->assertTrue($entreprise->prendRdvSurDemande());
    }

    public function test_prend_rdv_sur_demande_est_faux_par_defaut(): void
    {
        $entreprise = new Entreprise(['rdv_uniquement_messagerie' => false]);

        $this->assertFalse($entreprise->prendRdvSurDemande());
    }
}
