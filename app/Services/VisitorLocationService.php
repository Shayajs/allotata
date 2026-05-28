<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitorLocationService
{
    private const SESSION_KEY = 'visitor_location';

    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private AddressService $addressService
    ) {}

    /**
     * Résout la position approximative du visiteur (IP, session, utilisateur connecté).
     *
     * @return array{latitude: float, longitude: float, city: ?string, source: string}
     */
    public function resolve(Request $request): array
    {
        if ($session = session(self::SESSION_KEY)) {
            return $session;
        }

        $userLat = $request->input('user_lat');
        $userLng = $request->input('user_lng');
        if ($userLat && $userLng) {
            return $this->remember([
                'latitude' => (float) $userLat,
                'longitude' => (float) $userLng,
                'city' => null,
                'source' => 'browser',
            ]);
        }

        $user = Auth::user();
        if ($user && $user->latitude && $user->longitude) {
            return $this->remember([
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'city' => null,
                'source' => 'user',
            ]);
        }

        $ip = $request->ip();
        if ($this->isPublicIp($ip)) {
            $fromIp = $this->resolveFromIp($ip);
            if ($fromIp) {
                return $this->remember($fromIp);
            }
        }

        return $this->remember($this->defaultLocation());
    }

    private function resolveFromIp(string $ip): ?array
    {
        $cacheKey = 'visitor_ip_location_'.md5($ip);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($ip) {
            try {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,lat,lon,city,countryCode,query',
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                if (($data['status'] ?? '') !== 'success') {
                    return null;
                }

                if (isset($data['lat'], $data['lon'])) {
                    return [
                        'latitude' => (float) $data['lat'],
                        'longitude' => (float) $data['lon'],
                        'city' => $data['city'] ?? null,
                        'source' => 'ip',
                    ];
                }

                if (! empty($data['city'])) {
                    $countryLabel = ($data['countryCode'] ?? '') === 'FR' ? 'France' : ($data['countryCode'] ?? '');
                    $geocoded = $this->addressService->geocode(trim($data['city'].', '.$countryLabel));
                    if ($geocoded && isset($geocoded['latitude'], $geocoded['longitude'])) {
                        return [
                            'latitude' => (float) $geocoded['latitude'],
                            'longitude' => (float) $geocoded['longitude'],
                            'city' => $data['city'],
                            'source' => 'ip_geocoded',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("VisitorLocationService: impossible de géolocaliser {$ip}: ".$e->getMessage());
            }

            return null;
        });
    }

    private function isPublicIp(?string $ip): bool
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * @return array{latitude: float, longitude: float, city: ?string, source: string}
     */
    private function defaultLocation(): array
    {
        return [
            'latitude' => 46.603354,
            'longitude' => 1.888334,
            'city' => null,
            'source' => 'default',
        ];
    }

    /**
     * @param  array{latitude: float, longitude: float, city: ?string, source: string}  $location
     * @return array{latitude: float, longitude: float, city: ?string, source: string}
     */
    private function remember(array $location): array
    {
        session([self::SESSION_KEY => $location]);

        return $location;
    }
}
