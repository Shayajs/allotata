<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountAccessAuditService
{
    private static array $readMethods = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private AccountAccessService $accountAccess,
    ) {}

    public function shouldLog(Request $request, Response $response): bool
    {
        if (! $this->accountAccess->canWrite()) {
            return false;
        }

        if (in_array($request->method(), self::$readMethods, true)) {
            return false;
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 400) {
            return false;
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, ['stop-impersonating', 'admin.users.impersonate'], true)) {
            return false;
        }

        return true;
    }

    public function logAction(Request $request, Response $response): void
    {
        if (! $this->shouldLog($request, $response)) {
            return;
        }

        $targetUserId = $this->accountAccess->targetUserId();
        $adminId = $this->accountAccess->adminId();

        if (! $targetUserId || ! $adminId) {
            return;
        }

        $routeName = $request->route()?->getName() ?? $request->path();
        $summary = $this->buildSummary($request, $routeName);

        SecurityLog::log(
            $targetUserId,
            'admin_account_action',
            $request->ip(),
            $request->userAgent(),
            null,
            [
                'admin_id' => $adminId,
                'mode' => AccountAccessService::MODE_EDIT,
                'route' => $routeName,
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'summary' => $summary,
                'status' => $response->getStatusCode(),
            ],
            'medium',
            false,
            $summary,
        );
    }

    private function buildSummary(Request $request, string $routeName): string
    {
        $method = $request->method();
        $path = $request->path();

        if ($routeName && $routeName !== $path) {
            return "{$method} {$routeName}";
        }

        return "{$method} /{$path}";
    }
}
