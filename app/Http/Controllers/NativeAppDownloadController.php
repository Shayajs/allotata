<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NativeAppDownloadController extends Controller
{
    public function apk(): BinaryFileResponse
    {
        $filename = (string) config('play.apk_filename', 'AlloTata.apk');
        $path = $this->resolveApkPath();

        if (! $path) {
            abort(404, 'APK pas encore publié.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    public function assetlinks(): JsonResponse
    {
        $fingerprints = config('play.sha256_fingerprints', []);

        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('play.package_name'),
                    'sha256_cert_fingerprints' => $fingerprints,
                ],
            ],
        ]);
    }

    public static function apkAvailable(): bool
    {
        return (new self)->resolveApkPath() !== null;
    }

    private function resolveApkPath(): ?string
    {
        $filename = (string) config('play.apk_filename', 'AlloTata.apk');
        $candidates = [
            storage_path('app/public/downloads/'.$filename),
            public_path('downloads/'.$filename),
            base_path('mobile/dist/'.$filename),
            base_path('mobile/dist/AlloTata.apk'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
