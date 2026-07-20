<?php

namespace App\Http\Middleware;

use App\Services\AccountAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAccountAccessViewWrites
{
    private static array $safeMethods = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private AccountAccessService $accountAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            $this->accountAccess->isActive()
            && $this->accountAccess->mode() === AccountAccessService::MODE_VIEW
            && ! in_array($request->method(), self::$safeMethods, true)
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Mode lecture seule : les modifications sont interdites. Passez en mode EDIT pour agir sur ce compte.',
                    'edit_url' => $this->accountAccess->switchModeUrl('dashboard', AccountAccessService::MODE_EDIT),
                ], 403);
            }

            $editUrl = $this->accountAccess->switchModeUrl('dashboard', AccountAccessService::MODE_EDIT);

            abort(403, 'Mode lecture seule : les modifications sont interdites.'.($editUrl ? ' Passez en mode EDIT : '.$editUrl : ''));
        }

        return $next($request);
    }
}
