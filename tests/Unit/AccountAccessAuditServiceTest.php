<?php

namespace Tests\Unit;

use App\Services\AccountAccessAuditService;
use App\Services\AccountAccessService;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AccountAccessAuditServiceTest extends TestCase
{
    public function test_should_not_log_get_requests(): void
    {
        $accountAccess = $this->createMock(AccountAccessService::class);
        $accountAccess->method('canWrite')->willReturn(true);

        $audit = new AccountAccessAuditService($accountAccess);
        $request = Request::create('/dashboard', 'GET');
        $response = new Response('', 200);

        $this->assertFalse($audit->shouldLog($request, $response));
    }

    public function test_should_not_log_when_view_mode(): void
    {
        $accountAccess = $this->createMock(AccountAccessService::class);
        $accountAccess->method('canWrite')->willReturn(false);

        $audit = new AccountAccessAuditService($accountAccess);
        $request = Request::create('/dashboard/reservation/1/cancel', 'POST');
        $response = new Response('', 200);

        $this->assertFalse($audit->shouldLog($request, $response));
    }

    public function test_should_log_post_in_edit_mode_with_success(): void
    {
        $accountAccess = $this->createMock(AccountAccessService::class);
        $accountAccess->method('canWrite')->willReturn(true);

        $audit = new AccountAccessAuditService($accountAccess);
        $request = Request::create('/dashboard/reservation/1/cancel', 'POST');
        $response = new Response('', 302);

        $this->assertTrue($audit->shouldLog($request, $response));
    }

    public function test_should_not_log_failed_response(): void
    {
        $accountAccess = $this->createMock(AccountAccessService::class);
        $accountAccess->method('canWrite')->willReturn(true);

        $audit = new AccountAccessAuditService($accountAccess);
        $request = Request::create('/dashboard/reservation/1/cancel', 'POST');
        $response = new Response('', 403);

        $this->assertFalse($audit->shouldLog($request, $response));
    }
}
