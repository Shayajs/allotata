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
        if (! $this->accountAccess->isActive()) {
            return $next($request);
        }

        if (in_array($request->method(), self::$safeMethods, true)) {
            return $next($request);
        }

        $mode = $this->accountAccess->mode();
        $routeName = $request->route()?->getName();

        if ($mode === AccountAccessService::MODE_EDIT) {
            return $next($request);
        }

        if ($this->accountAccess->canWriteRoute($routeName)) {
            return $next($request);
        }

        $editUrl = $this->accountAccess->switchModeUrl('dashboard', AccountAccessService::MODE_EDIT);
        $modeLabel = $this->accountAccess->modeLabel();

        $message = $mode === AccountAccessService::MODE_VIEW
            ? 'Mode lecture seule : les modifications sont interdites. Passez en mode EDIT pour agir sur ce compte.'
            : "Mode {$modeLabel} : cette action n'est pas autorisée. Passez en mode EDIT pour un contrôle total.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'edit_url' => $editUrl,
                'mode' => $mode,
            ], 403);
        }

        abort(403, $message.($editUrl ? ' '.$editUrl : ''));
    }
}
