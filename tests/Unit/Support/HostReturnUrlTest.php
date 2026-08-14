<?php

namespace Tests\Unit\Support;

use App\Support\HostReturnUrl;
use Tests\TestCase;

class HostReturnUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subdomains.enabled' => true,
            'subdomains.base_domain' => 'allotata.test',
            'app.url' => 'https://allotata.test',
        ]);
    }

    public function test_accepte_apex_et_sous_domaines_mappes(): void
    {
        $this->assertSame(
            'https://admin.allotata.test/users',
            HostReturnUrl::normalize('https://admin.allotata.test/users')
        );
        $this->assertSame(
            'https://acme.allotata.test/manage',
            HostReturnUrl::normalize('https://acme.allotata.test/manage')
        );
        $this->assertSame(
            'https://allotata.test/dashboard',
            HostReturnUrl::normalize('https://allotata.test/dashboard')
        );
    }

    public function test_rejette_un_host_externe_ou_reserve_non_mappe(): void
    {
        $this->assertNull(HostReturnUrl::normalize('https://evil.test/dashboard'));
        $this->assertNull(HostReturnUrl::normalize('https://mail.allotata.test/'));
        $this->assertNull(HostReturnUrl::normalize('/dashboard'));
    }
}
