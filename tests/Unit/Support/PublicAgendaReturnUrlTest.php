<?php

namespace Tests\Unit\Support;

use App\Support\PublicAgendaReturnUrl;
use Tests\TestCase;

class PublicAgendaReturnUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://allotata.test',
            'subdomains.base_domain' => 'allotata.test',
        ]);
    }

    public function test_accepte_lagenda_apex(): void
    {
        $this->assertSame(
            'https://allotata.test/p/acme/agenda',
            PublicAgendaReturnUrl::normalize('https://allotata.test/p/acme/agenda')
        );
    }

    public function test_accepte_lagenda_sous_domaine_public(): void
    {
        $this->assertSame(
            'https://acme.allotata.test/public/agenda',
            PublicAgendaReturnUrl::normalize('https://acme.allotata.test/public/agenda')
        );
    }

    public function test_rejette_un_host_externe(): void
    {
        $this->assertNull(PublicAgendaReturnUrl::normalize('https://evil.test/p/acme/agenda'));
    }

    public function test_rejette_un_chemin_hors_agenda(): void
    {
        $this->assertNull(PublicAgendaReturnUrl::normalize('https://allotata.test/p/acme'));
        $this->assertNull(PublicAgendaReturnUrl::normalize('https://acme.allotata.test/manage'));
    }
}
