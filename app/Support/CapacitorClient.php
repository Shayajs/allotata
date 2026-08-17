<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapacitorClient
{
    public const COOKIE = 'allotata_native';

    public const HEADER = 'X-Capacitor';

    public static function detect(?Request $request = null): bool
    {
        $request ??= request();

        if ($request->attributes->has('is_capacitor')) {
            return (bool) $request->attributes->get('is_capacitor');
        }

        if ($request->headers->get(self::HEADER) === '1'
            || $request->headers->get('X-AlloTata-Native') === '1'
            || $request->query('native') === '1') {
            return true;
        }

        $ua = strtolower((string) $request->userAgent());
        if (str_contains($ua, 'allotataapp') || str_contains($ua, 'capacitor')) {
            return true;
        }

        return $request->cookie(self::COOKIE) === '1';
    }

    public static function brandUrl(?Request $request = null): string
    {
        if (! self::detect($request)) {
            return route('home');
        }

        return Auth::check() ? route('dashboard') : route('login');
    }

    public static function afterLoginRedirect(?Request $request = null): ?\Illuminate\Http\RedirectResponse
    {
        if (! self::detect($request)) {
            return null;
        }

        return redirect()->route('native.handoff');
    }
}
