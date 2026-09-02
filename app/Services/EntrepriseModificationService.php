<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\EntrepriseModification;
use App\Models\User;
use App\Support\SubdomainHost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EntrepriseModificationService
{
    /**
     * Champs à impact fort sur la fiche publique (identité légale, lien externe, médias).
     * Nom, adresse, description, etc. s'appliquent tout de suite.
     */
    public const MODERATED_FIELDS = [
        'siren',
        'siret',
        'video_url',
        'site_web_externe',
        'logo',
        'image_fond',
    ];

    public function __construct(
        private ImageService $imageService,
    ) {}

    public function shouldQueue(Entreprise $entreprise, bool $applyImmediately = false): bool
    {
        return ! $applyImmediately && (bool) $entreprise->est_verifiee;
    }

    public function valuesDiffer(mixed $live, mixed $next): bool
    {
        if ($live === $next) {
            return false;
        }
        if (($live === null || $live === '') && ($next === null || $next === '')) {
            return false;
        }
        if (is_bool($live) || is_bool($next)) {
            return (bool) $live !== (bool) $next;
        }
        if (is_numeric($live) && is_numeric($next)) {
            return (float) $live !== (float) $next;
        }

        return (string) $live !== (string) $next;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function queueFields(Entreprise $entreprise, array $fields): ?EntrepriseModification
    {
        if (isset($fields['nom']) && $this->valuesDiffer($entreprise->nom, $fields['nom'])) {
            $fields['slug'] = SubdomainHost::nextAvailableSlug(Str::slug((string) $fields['nom']), $entreprise->id);
        }

        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $payload['fields'] = $fields;

        if ($this->payloadIsEmpty($payload)) {
            $pending?->delete();

            return null;
        }

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queueLogo(Entreprise $entreprise, UploadedFile $file): EntrepriseModification
    {
        $path = $this->imageService->processAndStore($file, 'pending-modifications/'.$entreprise->id.'/logos');
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $this->deleteOrphanPath($payload['logo'] ?? null, $entreprise->logo);
        $payload['logo'] = $path;

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queueLogoDeletion(Entreprise $entreprise): ?EntrepriseModification
    {
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $this->deleteOrphanPath($payload['logo'] ?? null, $entreprise->logo);
        $payload['logo'] = ['_delete' => true];

        if (! $entreprise->logo && $this->payloadIsEmpty($payload)) {
            $pending?->delete();

            return null;
        }

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queueImageFond(Entreprise $entreprise, UploadedFile $file): EntrepriseModification
    {
        $path = $this->imageService->processAndStore($file, 'pending-modifications/'.$entreprise->id.'/fonds');
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $this->deleteOrphanPath($payload['image_fond'] ?? null, $entreprise->image_fond);
        $payload['image_fond'] = $path;

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queueImageFondDeletion(Entreprise $entreprise): ?EntrepriseModification
    {
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $this->deleteOrphanPath($payload['image_fond'] ?? null, $entreprise->image_fond);
        $payload['image_fond'] = ['_delete' => true];

        if (! $entreprise->image_fond && $this->payloadIsEmpty($payload)) {
            $pending?->delete();

            return null;
        }

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queuePhotoAdd(Entreprise $entreprise, UploadedFile $file, ?string $titre, ?string $description): EntrepriseModification
    {
        $path = $this->imageService->processAndStore($file, 'pending-modifications/'.$entreprise->id.'/photos');
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $photos = $payload['photos_add'] ?? [];
        $photos[] = [
            'path' => $path,
            'titre' => $titre,
            'description' => $description,
        ];
        $payload['photos_add'] = $photos;

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function queuePhotoDelete(Entreprise $entreprise, int $photoId): EntrepriseModification
    {
        $pending = $this->pendingOf($entreprise);
        $payload = $pending?->payload ?? [];
        $ids = $payload['photos_delete'] ?? [];
        if (! in_array($photoId, $ids, true)) {
            $ids[] = $photoId;
        }
        $payload['photos_delete'] = $ids;

        return $this->savePending($entreprise, $payload, $pending);
    }

    public function approve(EntrepriseModification $modification, User $admin): Entreprise
    {
        if (! $modification->estEnAttente()) {
            throw new \RuntimeException('Cette demande a déjà été traitée.');
        }

        $entreprise = $modification->entreprise;
        $payload = $modification->payload ?? [];
        $fields = $payload['fields'] ?? [];

        if (isset($fields['nom'])) {
            $fields['nom_valide'] = true;
            $fields['nom_refus_raison'] = null;
        }
        if (array_key_exists('siren', $fields) || array_key_exists('siret', $fields)) {
            $fields['siren_valide'] = true;
            $fields['siren_refus_raison'] = null;
            $fields['siren_verifie'] = true;
        }

        if ($fields !== []) {
            $entreprise->update($fields);
        }

        if (array_key_exists('logo', $payload)) {
            $this->applyMediaPath($entreprise, 'logo', $payload['logo']);
        }
        if (array_key_exists('image_fond', $payload)) {
            $this->applyMediaPath($entreprise, 'image_fond', $payload['image_fond']);
        }

        foreach ($payload['photos_add'] ?? [] as $photo) {
            $maxOrdre = $entreprise->realisationPhotos()->max('ordre') ?? 0;
            $entreprise->realisationPhotos()->create([
                'photo_path' => $photo['path'],
                'titre' => $photo['titre'] ?? null,
                'description' => $photo['description'] ?? null,
                'ordre' => $maxOrdre + 1,
            ]);
        }

        foreach ($payload['photos_delete'] ?? [] as $photoId) {
            $photo = $entreprise->realisationPhotos()->find($photoId);
            if (! $photo) {
                continue;
            }
            if ($photo->photo_path) {
                $this->imageService->delete($photo->photo_path);
            }
            $photo->delete();
        }

        $modification->update([
            'statut' => EntrepriseModification::STATUT_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $entreprise = $entreprise->fresh();
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        return $entreprise;
    }

    public function reject(EntrepriseModification $modification, User $admin, ?string $motif = null): void
    {
        if (! $modification->estEnAttente()) {
            throw new \RuntimeException('Cette demande a déjà été traitée.');
        }

        $entreprise = $modification->entreprise;
        $this->discardQueuedFiles($modification->payload ?? [], $entreprise);

        $modification->update([
            'statut' => EntrepriseModification::STATUT_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'motif_refus' => $motif,
        ]);
    }

    private function pendingOf(Entreprise $entreprise): ?EntrepriseModification
    {
        return $entreprise->modificationEnAttente()->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function savePending(Entreprise $entreprise, array $payload, ?EntrepriseModification $pending): EntrepriseModification
    {
        if ($pending) {
            $pending->update([
                'payload' => $payload,
                'user_id' => Auth::id() ?? $pending->user_id,
            ]);
            $saved = $pending->fresh();
        } else {
            $saved = EntrepriseModification::create([
                'entreprise_id' => $entreprise->id,
                'user_id' => Auth::id() ?? $entreprise->user_id,
                'payload' => $payload,
                'statut' => EntrepriseModification::STATUT_PENDING,
            ]);
        }

        $this->notifyAdmins($entreprise);

        return $saved;
    }

    private function notifyAdmins(Entreprise $entreprise): void
    {
        try {
            app(AdminNotificationService::class)->notifyEntrepriseModified($entreprise);
        } catch (\Throwable $e) {
            Log::warning('Notification admin modification entreprise: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadIsEmpty(array $payload): bool
    {
        return empty($payload['fields'])
            && ! array_key_exists('logo', $payload)
            && ! array_key_exists('image_fond', $payload)
            && empty($payload['photos_add'])
            && empty($payload['photos_delete']);
    }

    private function deleteOrphanPath(mixed $path, ?string $livePath): void
    {
        if (! is_string($path) || $path === '' || $path === $livePath) {
            return;
        }

        try {
            $this->imageService->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Suppression fichier pending: '.$e->getMessage(), ['path' => $path]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function discardQueuedFiles(array $payload, Entreprise $entreprise): void
    {
        $this->deleteOrphanPath($payload['logo'] ?? null, $entreprise->logo);
        $this->deleteOrphanPath($payload['image_fond'] ?? null, $entreprise->image_fond);
        foreach ($payload['photos_add'] ?? [] as $photo) {
            $this->deleteOrphanPath($photo['path'] ?? null, null);
        }
    }

    private function applyMediaPath(Entreprise $entreprise, string $column, mixed $value): void
    {
        $current = $entreprise->{$column};

        if (is_array($value) && ! empty($value['_delete'])) {
            if ($current) {
                $this->imageService->delete($current);
            }
            $entreprise->update([$column => null]);

            return;
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $entreprise->update([$column => $value]);
        if ($current && $current !== $value) {
            try {
                $this->imageService->delete($current);
            } catch (\Throwable $e) {
                Log::warning('Suppression ancien média: '.$e->getMessage());
            }
        }
    }
}
