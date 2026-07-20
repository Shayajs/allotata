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

    public function test_normalize_mode_invalid(): void
    {
        $this->assertNull($this->service->normalizeMode('SUPPORT'));
        $this->assertNull($this->service->normalizeMode(''));
        $this->assertNull($this->service->normalizeMode(null));
    }

    public function test_should_exit_on_get_without_params_when_bridge_active(): void
    {
        session([AccountAccessService::SESSION_ADMIN_ID => 1]);

        $request = Request::create('/admin/users', 'GET');

        $this->assertTrue($this->service->shouldExitOnGet($request));
    }

    public function test_should_not_exit_on_get_without_params_when_no_bridge(): void
    {
        $request = Request::create('/admin/users', 'GET');

        $this->assertFalse($this->service->shouldExitOnGet($request));
    }

    public function test_should_not_exit_on_get_with_mode_param(): void
    {
        $request = Request::create('/dashboard?mode=VIEW&compte=5', 'GET');

        $this->assertFalse($this->service->shouldExitOnGet($request));
    }

    public function test_should_not_exit_on_post_without_params(): void
    {
        $request = Request::create('/dashboard/reservation/1/cancel', 'POST');

        $this->assertFalse($this->service->shouldExitOnGet($request));
    }

    public function test_build_query_returns_empty_when_inactive(): void
    {
        $this->assertSame([], $this->service->buildQuery());
    }
}
