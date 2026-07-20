<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AccountAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleAccountAccess
{
    public function __construct(
        private AccountAccessService $accountAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->accountAccess->shouldExitOnGet($request)) {
            $this->accountAccess->exit();

            return $next($request);
        }

        $context = $this->accountAccess->resolveContext($request);

        if ($context === null) {
            return $next($request);
        }

        $mode = $context['mode'];
        $compte = $context['compte'];
        $adminId = $this->accountAccess->adminId();

        if (! $adminId) {
            if (! Auth::check() || ! Auth::user()->isAdmin()) {
                return $next($request);
            }

            $adminId = Auth::id();
        }

        if ($compte === $adminId) {
            return redirect()->back()->with('error', 'Impossible d\'accéder à votre propre compte via ce mode.');
        }

        $target = User::find($compte);

        if (! $target) {
            abort(404, 'Compte introuvable.');
        }

        $this->accountAccess->enter($target, $adminId, $mode);

        if (Auth::id() !== $target->id) {
            Auth::login($target);
        }

        return $next($request);
    }
}
