<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\RealisationPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntrepriseProfileService
{
    public bool $lastUpdateQueued = false;

    public function __construct(
        private ImageService $imageService,
        private EntrepriseModificationService $modifications,
    ) {}

    /**
     * Règles de validation partagées (profil gérant / admin).
     *
     * @return array<string, mixed>
     */
    public function updateRules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'type_activite' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'afficher_video' => ['nullable'],
            'mots_cles' => ['nullable', 'string', 'max:500'],
            'type_localisation' => ['required', 'in:physique,virtuel'],
            'ville' => ['nullable', 'required_if:type_localisation,physique', 'string', 'max:255'],
            'adresse_rue' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'afficher_adresse_complete' => ['nullable'],
            'rayon_deplacement' => ['nullable', 'integer', 'min:0'],
            'siren' => ['nullable', 'string', 'max:9', 'regex:/^[0-9]{0,9}$/'],
            'siret' => [
                'nullable',
                'string',
                'max:14',
                'regex:/^[0-9]{0,14}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\s+/', '', (string) $value);
                    if ($digits === '') {
                        return;
                    }
                    if (strlen($digits) === 14 && ! app(\App\Services\Facturation\BillingProfileService::class)->siretEstValide($digits)) {
                        $fail('Le SIRET est invalide (14 chiffres, clé de Luhn).');
                    }
                },
            ],
            'status_juridique' => ['nullable', 'string', 'in:en_cours,auto_entrepreneur,sarl,eurl,sas'],
            'tva_intracommunautaire' => ['nullable', 'string', 'max:20'],
            'assujetti_tva' => ['nullable'],
            'taux_tva_defaut' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'capital_social' => ['nullable', 'numeric', 'min:0'],
            'rcs_ville' => ['nullable', 'string', 'max:255'],
            'nom_responsable' => ['nullable', 'string', 'max:255'],
            'pdf_couleur_primaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pdf_couleur_secondaire' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'afficher_nom_gerant' => ['nullable'],
            'prix_negociables' => ['nullable'],
            'rdv_uniquement_messagerie' => ['nullable'],
            'rdv_sur_demande_message' => ['nullable', 'string', 'max:2000'],
            'accepter_reservations_auto' => ['nullable'],
            'intervalle_creneaux_minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
            'notif_message_prise' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'notif_message_annulation' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'livraison_disponible_par_defaut' => ['nullable'],
            'vente_sur_place_disponible_par_defaut' => ['nullable'],
            'site_web_externe' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function update(Entreprise $entreprise, Request $request, bool $applyImmediately = false): Entreprise
    {
        $this->lastUpdateQueued = false;
        $validated = $request->validate($this->updateRules());

        $queue = $this->modifications->shouldQueue($entreprise, $applyImmediately);

        if ($validated['nom'] !== $entreprise->nom) {
            $validated['slug'] = \App\Support\SubdomainHost::nextAvailableSlug(
                Str::slug($validated['nom']),
                $entreprise->id
            );
        }

        if (! empty($validated['mots_cles'])) {
            $motsClesArray = array_map('trim', explode(',', $validated['mots_cles']));
            $motsClesArray = array_filter($motsClesArray, function ($mot) {
                return ! empty($mot) && strlen($mot) >= 2;
            });
            $motsClesArray = array_unique($motsClesArray);
            $validated['mots_cles'] = implode(', ', $motsClesArray);
        }

        $validated['afficher_nom_gerant'] = $request->has('afficher_nom_gerant') && $request->input('afficher_nom_gerant') == '1';
        $validated['prix_negociables'] = $request->has('prix_negociables') && $request->input('prix_negociables') == '1';
        $validated['rdv_uniquement_messagerie'] = $request->has('rdv_uniquement_messagerie') && $request->input('rdv_uniquement_messagerie') == '1';
        $validated['accepter_reservations_auto'] = $request->has('accepter_reservations_auto') && $request->input('accepter_reservations_auto') == '1';
        if ($validated['rdv_uniquement_messagerie']) {
            $validated['accepter_reservations_auto'] = false;
        }
        $validated['rdv_sur_demande_message'] = trim((string) $request->input('rdv_sur_demande_message', '')) !== ''
            ? trim((string) $request->input('rdv_sur_demande_message'))
            : null;

        if (empty($validated['intervalle_creneaux_minutes'] ?? null)) {
            $validated['intervalle_creneaux_minutes'] = $entreprise->intervalle_creneaux_minutes ?? 30;
        }
        $validated['livraison_disponible_par_defaut'] = $request->has('livraison_disponible_par_defaut') && $request->input('livraison_disponible_par_defaut') == '1';
        $validated['vente_sur_place_disponible_par_defaut'] = $request->has('vente_sur_place_disponible_par_defaut') && $request->input('vente_sur_place_disponible_par_defaut') == '1';
        $validated['afficher_adresse_complete'] = $request->has('afficher_adresse_complete') && $request->input('afficher_adresse_complete') == '1';
        $validated['afficher_video'] = $request->has('afficher_video') && $request->input('afficher_video') == '1';
        $validated['assujetti_tva'] = $request->has('assujetti_tva') && $request->input('assujetti_tva') == '1';

        if (! empty($validated['siret'])) {
            $validated['siret'] = preg_replace('/\s+/', '', $validated['siret']);
            if (strlen($validated['siret']) === 14) {
                $validated['siren'] = substr($validated['siret'], 0, 9);
            }
        }

        foreach (['notif_message_prise', 'notif_message_annulation'] as $champNotif) {
            if (array_key_exists($champNotif, $validated)) {
                $validated[$champNotif] = trim((string) $validated[$champNotif]) !== ''
                    ? trim((string) $validated[$champNotif])
                    : null;
            }
        }

        if (empty($validated['video_url'])) {
            $validated['video_url'] = null;
            $validated['afficher_video'] = false;
        }

        if (empty($validated['latitude'])) {
            $validated['latitude'] = null;
        }
        if (empty($validated['longitude'])) {
            $validated['longitude'] = null;
        }

        $validated = Entreprise::applyTypeLocalisation($validated, $validated['type_localisation']);

        if ($queue) {
            $moderated = [];
            $immediate = [];
            foreach ($validated as $key => $value) {
                if (in_array($key, EntrepriseModificationService::MODERATED_FIELDS, true)) {
                    if ($this->modifications->valuesDiffer($entreprise->{$key} ?? null, $value)) {
                        $moderated[$key] = $value;
                    }
                } else {
                    $immediate[$key] = $value;
                }
            }

            $oldSlug = $entreprise->slug;
            if ($immediate !== []) {
                $entreprise->update($immediate);
                CacheService::clearEntrepriseCache($entreprise->id, $oldSlug);
                if (($immediate['slug'] ?? null) && $immediate['slug'] !== $oldSlug) {
                    CacheService::clearEntrepriseCache($entreprise->id, $immediate['slug']);
                }
            }
            if ($moderated !== []) {
                $this->modifications->queueFields($entreprise, $moderated);
                $this->lastUpdateQueued = true;
            }

            return $entreprise->fresh();
        }

        $entreprise->update($validated);
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        return $entreprise->fresh();
    }

    /**
     * @return array{logo_path: string, logo_url: string}
     */
    public function uploadLogo(Entreprise $entreprise, UploadedFile $file, bool $applyImmediately = false): array
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $modification = $this->modifications->queueLogo($entreprise, $file);
            $path = $modification->payload['logo'] ?? null;

            return [
                'logo_path' => is_string($path) ? $path : '',
                'logo_url' => is_string($path) ? asset('media/'.$path) : '',
                'queued' => true,
            ];
        }

        $logoPath = $this->imageService->processAndStore($file, 'logos');

        if (! Storage::disk('public')->exists($logoPath)) {
            throw new \RuntimeException('Erreur lors de l\'upload du logo.');
        }

        $oldLogoPath = $entreprise->logo;
        $entreprise->update(['logo' => $logoPath]);
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        if ($oldLogoPath) {
            try {
                $this->imageService->delete($oldLogoPath);
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la suppression de l\'ancien logo', [
                    'path' => $oldLogoPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'logo_path' => $logoPath,
            'logo_url' => asset('media/'.$logoPath),
            'queued' => false,
        ];
    }

    public function deleteLogo(Entreprise $entreprise, bool $applyImmediately = false): void
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $this->modifications->queueLogoDeletion($entreprise);

            return;
        }

        if (! $entreprise->logo) {
            return;
        }

        $this->imageService->delete($entreprise->logo);
        $entreprise->update(['logo' => null]);
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);
    }

    /**
     * @return array{image_fond_path: string, image_fond_url: string}
     */
    public function uploadImageFond(Entreprise $entreprise, UploadedFile $file, bool $applyImmediately = false): array
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $modification = $this->modifications->queueImageFond($entreprise, $file);
            $path = $modification->payload['image_fond'] ?? null;

            return [
                'image_fond_path' => is_string($path) ? $path : '',
                'image_fond_url' => is_string($path) ? asset('media/'.$path) : '',
                'queued' => true,
            ];
        }

        $imageFondPath = $this->imageService->processAndStore($file, 'images_fond');

        if (! Storage::disk('public')->exists($imageFondPath)) {
            throw new \RuntimeException('Erreur lors de l\'upload de l\'image de fond.');
        }

        $oldImageFondPath = $entreprise->image_fond;
        $entreprise->update(['image_fond' => $imageFondPath]);
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        if ($oldImageFondPath) {
            try {
                $this->imageService->delete($oldImageFondPath);
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la suppression de l\'ancienne image de fond', [
                    'path' => $oldImageFondPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'image_fond_path' => $imageFondPath,
            'image_fond_url' => asset('media/'.$imageFondPath),
            'queued' => false,
        ];
    }

    public function deleteImageFond(Entreprise $entreprise, bool $applyImmediately = false): void
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $this->modifications->queueImageFondDeletion($entreprise);

            return;
        }

        if (! $entreprise->image_fond) {
            return;
        }

        $this->imageService->delete($entreprise->image_fond);
        $entreprise->update(['image_fond' => null]);
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);
    }

    public function addRealisationPhoto(Entreprise $entreprise, UploadedFile $file, ?string $titre = null, ?string $description = null, bool $applyImmediately = false): ?RealisationPhoto
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $this->modifications->queuePhotoAdd($entreprise, $file, $titre, $description);

            return null;
        }

        $photoPath = $this->imageService->processAndStore($file, 'realisations');
        $maxOrdre = $entreprise->realisationPhotos()->max('ordre') ?? 0;

        $photo = $entreprise->realisationPhotos()->create([
            'photo_path' => $photoPath,
            'titre' => $titre,
            'description' => $description,
            'ordre' => $maxOrdre + 1,
        ]);

        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);

        return $photo;
    }

    public function deleteRealisationPhoto(Entreprise $entreprise, int $photoId, bool $applyImmediately = false): void
    {
        if ($this->modifications->shouldQueue($entreprise, $applyImmediately)) {
            $this->modifications->queuePhotoDelete($entreprise, $photoId);

            return;
        }

        $photo = $entreprise->realisationPhotos()->findOrFail($photoId);

        if ($photo->photo_path) {
            $this->imageService->delete($photo->photo_path);
        }

        $photo->delete();
        CacheService::clearEntrepriseCache($entreprise->id, $entreprise->slug);
    }
}
