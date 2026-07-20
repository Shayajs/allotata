<?php

namespace App\Http\Middleware;

use App\Services\AccountAccessAuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAccountAccessActions
{
    public function __construct(
        private AccountAccessAuditService $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->audit->logAction($request, $response);

        return $response;
    }
}
