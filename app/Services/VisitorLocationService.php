<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitorLocationService
{
    private const SESSION_KEY = 'visitor_location_v2';

    private const CACHE_TTL_SECONDS = 3600;

    private const CACHE_KEY_PREFIX = 'visitor_ip_location_v2_';

    public function __construct(
        private AddressService $addressService
    ) {}

    /**
     * Résout la position approximative du visiteur (GPS, session, utilisateur, IP).
     *
     * @return array{latitude: float, longitude: float, city: ?string, source: string}
     */
    public function resolve(Request $request): array
    {
        if ($request->boolean('forget_geo')) {
            $this->forget();
        }

        $browser = $this->fromBrowserRequest($request);
        if ($browser) {
            return $this->remember($browser);
        }

        if ($session = session(self::SESSION_KEY)) {
            return $session;
        }

        $user = Auth::user();
        if ($user && $user->latitude && $user->longitude) {
            return $this->remember([
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'city' => $user->ville ?: null,
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

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function isBrowser(?array $location): bool
    {
        return ($location['source'] ?? '') === 'browser';
    }

    /**
     * @return array{latitude: float, longitude: float, city: ?string, source: string}|null
     */
    private function fromBrowserRequest(Request $request): ?array
    {
        if ($request->boolean('forget_geo')) {
            return null;
        }

        $lat = $request->input('user_lat');
        $lng = $request->input('user_lng');
        if (! $this->validCoords($lat, $lng)) {
            return null;
        }

        $city = $request->input('user_city');

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'city' => is_string($city) && $city !== '' ? $city : null,
            'source' => 'browser',
        ];
    }

    private function validCoords(mixed $lat, mixed $lng): bool
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    private function resolveFromIp(string $ip): ?array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($ip);

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

                if (($data['countryCode'] ?? '') !== 'FR') {
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
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'city' => 'Paris',
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
