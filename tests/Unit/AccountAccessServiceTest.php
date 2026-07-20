<?php

namespace Tests\Unit;

use App\Services\AccountAccessService;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class AccountAccessServiceTest extends TestCase
{
    private AccountAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $notifications = $this->createMock(UserNotificationService::class);
        $this->service = new AccountAccessService($notifications);
    }

    public function test_normalize_mode_view(): void
    {
        $this->assertSame('VIEW', $this->service->normalizeMode('VIEW'));
        $this->assertSame('VIEW', $this->service->normalizeMode('view'));
    }

    public function test_normalize_mode_edit_and_admin_alias(): void
    {
        $this->assertSame('EDIT', $this->service->normalizeMode('EDIT'));
        $this->assertSame('EDIT', $this->service->normalizeMode('edit'));
        $this->assertSame('EDIT', $this->service->normalizeMode('ADMIN'));
        $this->assertSame('EDIT', $this->service->normalizeMode('admin'));
    }

    public function test_normalize_mode_support_and_billing(): void
    {
        $this->assertSame('SUPPORT', $this->service->normalizeMode('SUPPORT'));
        $this->assertSame('SUPPORT', $this->service->normalizeMode('support'));
        $this->assertSame('BILLING', $this->service->normalizeMode('BILLING'));
        $this->assertSame('BILLING', $this->service->normalizeMode('billing'));
    }

    public function test_normalize_mode_invalid(): void
    {
        $this->assertNull($this->service->normalizeMode('DEBUG'));
        $this->assertNull($this->service->normalizeMode(''));
        $this->assertNull($this->service->normalizeMode(null));
    }

    public function test_explicit_exit_flag(): void
    {
        $request = Request::create('/admin/users?exit_account_access=1', 'GET');

        $this->assertTrue($this->service->wantsExplicitExit($request));
    }

    public function test_needs_query_injection_when_bridge_active_without_params(): void
    {
        $this->activateBridge(AccountAccessService::MODE_EDIT);

        $request = Request::create('/m/mon-entreprise', 'GET');

        $this->assertTrue($this->service->needsQueryInjection($request));
        $this->assertStringContainsString('mode=EDIT', $this->service->injectQueryUrl($request));
        $this->assertStringContainsString('compte=5', $this->service->injectQueryUrl($request));
    }

    public function test_no_query_injection_when_params_present(): void
    {
        $this->activateBridge(AccountAccessService::MODE_EDIT);

        $request = Request::create('/m/mon-entreprise?mode=EDIT&compte=5', 'GET');

        $this->assertFalse($this->service->needsQueryInjection($request));
    }

    public function test_should_exit_on_admin_panel_when_bridge_active(): void
    {
        $this->activateBridge(AccountAccessService::MODE_EDIT);

        $request = Request::create('/admin/users', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/admin/users', fn () => null);
            $route->name('admin.users.index');

            return $route;
        });

        $this->assertTrue($this->service->shouldExitOnAdminPanel($request));
    }

    public function test_should_not_exit_on_get_without_params_when_no_bridge(): void
    {
        $request = Request::create('/admin/users', 'GET');

        $this->assertFalse($this->service->shouldExitOnGet($request));
    }

    public function test_should_not_exit_on_get_with_mode_param(): void
    {
        $request = Request::create('/dashboard?mode=VIEW&compte=5', 'GET');

        $this->assertFalse($this->service->wantsExplicitExit($request));
    }

    public function test_should_not_exit_on_post_without_params(): void
    {
        $this->activateBridge(AccountAccessService::MODE_EDIT);

        $request = Request::create('/dashboard/reservation/1/cancel', 'POST');

        $this->assertFalse($this->service->needsQueryInjection($request));
        $this->assertFalse($this->service->wantsExplicitExit($request));
    }

    public function test_build_query_returns_empty_when_inactive(): void
    {
        $this->assertSame([], $this->service->buildQuery());
    }

    public function test_can_write_route_support_allows_messagerie(): void
    {
        $this->activateBridge(AccountAccessService::MODE_SUPPORT);

        $this->assertTrue($this->service->canWriteRoute('messagerie.send'));
        $this->assertTrue($this->service->canWriteRoute('tickets.add-message'));
        $this->assertFalse($this->service->canWriteRoute('subscription.cancel'));
        $this->assertFalse($this->service->canWriteRoute('dashboard.reservation.cancel'));
    }

    public function test_can_write_route_billing_allows_subscription(): void
    {
        $this->activateBridge(AccountAccessService::MODE_BILLING);

        $this->assertTrue($this->service->canWriteRoute('subscription.cancel'));
        $this->assertTrue($this->service->canWriteRoute('checkout.charge'));
        $this->assertTrue($this->service->canWriteRoute('entreprise.finances.store'));
        $this->assertFalse($this->service->canWriteRoute('messagerie.send'));
    }

    public function test_can_write_route_edit_allows_all(): void
    {
        $this->activateBridge(AccountAccessService::MODE_EDIT);

        $this->assertTrue($this->service->canWriteRoute('messagerie.send'));
        $this->assertTrue($this->service->canWriteRoute('subscription.cancel'));
        $this->assertTrue($this->service->canWriteRoute('dashboard.reservation.cancel'));
    }

    public function test_can_write_route_view_blocks_all(): void
    {
        $this->activateBridge(AccountAccessService::MODE_VIEW);

        $this->assertFalse($this->service->canWrite());
        $this->assertFalse($this->service->canWriteRoute('messagerie.send'));
        $this->assertFalse($this->service->canWriteRoute('api.presence.heartbeat'));
    }

    public function test_can_write_route_utility_in_support(): void
    {
        $this->activateBridge(AccountAccessService::MODE_SUPPORT);

        $this->assertTrue($this->service->canWriteRoute('api.presence.heartbeat'));
        $this->assertTrue($this->service->canWriteRoute('logout'));
    }

    private function activateBridge(string $mode): void
    {
        session([
            AccountAccessService::SESSION_ADMIN_ID => 1,
            AccountAccessService::SESSION_MODE => $mode,
            AccountAccessService::SESSION_COMPTE => 5,
        ]);
    }
}
