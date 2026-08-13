<?php

namespace App\Services\Facturation;

use App\Models\Devis;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfDocumentRenderer
{
    public function __construct(
        private DocumentSnapshotService $snapshots,
    ) {}

    public function facturePdf(Facture $facture)
    {
        $doc = $this->snapshots->ensureFacture($facture);
        $doc = $this->enrichirLogo($doc);

        return Pdf::loadView('factures.pdf', ['doc' => $doc])
            ->setPaper('a4');
    }

    public function devisPdf(Devis $devis)
    {
        $doc = $this->snapshots->ensureDevis($devis);
        $doc = $this->enrichirLogo($doc);

        return Pdf::loadView('devis.pdf', ['doc' => $doc])
            ->setPaper('a4');
    }

    public function outputFacture(Facture $facture): string
    {
        return $this->facturePdf($facture)->output();
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array<string, mixed>
     */
    private function enrichirLogo(array $doc): array
    {
        $doc['logo_base64'] = $this->encoderLogo($doc['logo'] ?? null);

        return $doc;
    }

    private function encoderLogo(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $candidates = [
            public_path('media/'.$path),
            storage_path('app/public/'.$path),
            public_path('storage/'.$path),
        ];

        $full = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $full = $candidate;
                break;
            }
        }

        if (! $full && Storage::disk('public')->exists($path)) {
            $full = Storage::disk('public')->path($path);
        }

        if (! $full || ! is_file($full)) {
            return null;
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION) ?: 'png');
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($full));
    }
}
