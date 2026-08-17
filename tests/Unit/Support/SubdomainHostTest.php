<?php

namespace Tests\Unit\Support;

use App\Support\SubdomainHost;
use Illuminate\Http\Request;
use Tests\TestCase;

class SubdomainHostTest extends TestCase
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

    public function test_parse_apex_www_hosts_mappes_tenant_et_reserves(): void
    {
        $this->assertSame('apex', SubdomainHost::parse('allotata.test')['mode']);
        $this->assertSame('apex', SubdomainHost::parse('www.allotata.test')['mode']);
        $this->assertSame('admin', SubdomainHost::parse('admin.allotata.test')['mode']);
        $this->assertSame('dash', SubdomainHost::parse('dash.allotata.test')['mode']);
        $this->assertSame('sign', SubdomainHost::parse('sign.allotata.test')['mode']);
        $this->assertSame('api', SubdomainHost::parse('api.allotata.test')['mode']);
        $this->assertSame('support', SubdomainHost::parse('support.allotata.test')['mode']);
        $this->assertSame('learn', SubdomainHost::parse('learn.allotata.test')['mode']);
        $this->assertSame('tenant', SubdomainHost::parse('acme.allotata.test')['mode']);
        $this->assertSame('acme', SubdomainHost::parse('acme.allotata.test')['subdomain']);
        $this->assertSame('unknown', SubdomainHost::parse('mail.allotata.test')['mode']);
        $this->assertSame('unknown', SubdomainHost::parse('example.com')['mode']);
    }

    public function test_inbound_admin_reecrit_ou_redirige_ou_404(): void
    {
        $admin = SubdomainHost::inboundPath(Request::create('https://admin.allotata.test/users', 'GET'));
        $this->assertSame('/admin/users', $admin['path']);

        $adminRoot = SubdomainHost::inboundPath(Request::create('https://admin.allotata.test/', 'GET'));
        $this->assertSame('/admin', $adminRoot['path']);

        $foreign = SubdomainHost::inboundPath(Request::create('https://admin.allotata.test/a-propos', 'GET'));
        $this->assertSame('https://allotata.test/a-propos', $foreign['redirect']);

        $unknown = SubdomainHost::inboundPath(Request::create('https://admin.allotata.test/pipo', 'GET'));
        $this->assertSame(404, $unknown['abort']);
    }

    public function test_inbound_dash_et_sign(): void
    {
        $dash = SubdomainHost::inboundPath(Request::create('https://dash.allotata.test/', 'GET'));
        $this->assertSame('/dashboard', $dash['path']);

        $this->assertNull(SubdomainHost::inboundPath(Request::create('https://dash.allotata.test/settings', 'GET')));

        $sign = SubdomainHost::inboundPath(Request::create('https://sign.allotata.test/', 'GET'));
        $this->assertSame('/signin', $sign['path']);

        $this->assertNull(SubdomainHost::inboundPath(Request::create('https://sign.allotata.test/signup', 'GET')));
    }

    public function test_inbound_host_inconnu_est_404(): void
    {
        $mail = SubdomainHost::inboundPath(Request::create('https://mail.allotata.test/', 'GET'));
        $this->assertSame(404, $mail['abort']);
    }

    public function test_inbound_api_reecrit_vers_prefixe_api(): void
    {
        $health = SubdomainHost::inboundPath(Request::create('https://api.allotata.test/v3/HealthCheck', 'GET'));
        $this->assertSame('/api/v3/HealthCheck', $health['path']);
    }

    public function test_owner_url(): void
    {
        $this->assertSame('https://admin.allotata.test/users', SubdomainHost::ownerUrl('/admin/users'));
        $this->assertSame('https://dash.allotata.test/', SubdomainHost::ownerUrl('/dashboard'));
        $this->assertSame('https://sign.allotata.test/', SubdomainHost::ownerUrl('/signin'));
        $this->assertSame('https://acme.allotata.test/manage', SubdomainHost::ownerUrl('/m/acme'));
        $this->assertSame('https://allotata.test/forum', SubdomainHost::ownerUrl('/forum'));
    }

    public function test_outbound_selon_le_host(): void
    {
        $this->app->instance('request', Request::create('https://admin.allotata.test/users', 'GET'));
        $this->assertSame('/', SubdomainHost::outboundPath('/admin'));
        $this->assertSame('/users', SubdomainHost::outboundPath('/admin/users'));

        $this->app->instance('request', Request::create('https://dash.allotata.test/', 'GET'));
        $this->assertSame('/', SubdomainHost::outboundPath('/dashboard'));

        $this->app->instance('request', Request::create('https://sign.allotata.test/', 'GET'));
        $this->assertSame('/', SubdomainHost::outboundPath('/signin'));

        $this->app->instance('request', Request::create('https://acme.allotata.test/manage', 'GET'));
        $this->assertSame('/manage', SubdomainHost::outboundPath('/m/acme'));
        $this->assertSame('/manage/agenda', SubdomainHost::outboundPath('/m/acme/agenda'));
        $this->assertSame('/public', SubdomainHost::outboundPath('/p/acme'));
        $this->assertSame('/', SubdomainHost::outboundPath('/w/acme-web'));
        $this->assertSame('/reservation-form', SubdomainHost::outboundPath('/w/acme-web/reservation-form'));

        $this->app->instance('request', Request::create('https://allotata.test/m/acme', 'GET'));
        $this->assertSame('/m/acme', SubdomainHost::outboundPath('/m/acme'));
    }

    public function test_flag_off_ne_reecrit_rien(): void
    {
        config(['subdomains.enabled' => false]);

        $this->assertNull(SubdomainHost::inboundPath(Request::create('https://admin.allotata.test/users', 'GET')));
        $this->assertSame('/m/acme', SubdomainHost::outboundPath('/m/acme'));
    }

    public function test_slugs_reserves(): void
    {
        $this->assertTrue(SubdomainHost::isReservedSlug('admin'));
        $this->assertTrue(SubdomainHost::isReservedSlug('DASH'));
        $this->assertTrue(SubdomainHost::isReservedSlug('sign'));
        $this->assertFalse(SubdomainHost::isReservedSlug('acme'));
    }

    public function test_tenant_url(): void
    {
        $this->assertSame('https://acme.allotata.test/manage', SubdomainHost::tenantUrl('acme', '/manage'));
    }
}
