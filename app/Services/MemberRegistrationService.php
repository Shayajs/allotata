<?php

namespace App\Services;

use App\Models\EmailVerification;
use App\Models\SecurityLog;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class MemberRegistrationService
{
    /**
     * @return array<string, list<mixed>>
     */
    public function regles(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'date_naissance' => ['required', 'date', 'before:today'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'code_postal' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'invitation_token' => ['nullable', 'string'],
            'genre' => ['nullable', 'in:homme,femme,non_precise'],
            'source_inscription' => ['nullable', 'in:google,bouche_a_oreille,reseaux_sociaux,publicite,parrainage,autre'],
            'code_parrainage' => ['nullable', 'string', 'max:10'],
            'notifications_reservations' => ['nullable'],
            'notifications_paiements' => ['nullable'],
            'notifications_messages' => ['nullable'],
            'notifications_rappels' => ['nullable'],
            'notifications_promotions' => ['nullable'],
            'notifications_mises_a_jour' => ['nullable'],
            'cgu_accepted' => ['required', 'accepted'],
            'cgv_accepted' => ['required', 'accepted'],
            'confidentialite_accepted' => ['required', 'accepted'],
            'return' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * Crée un membre (client, pas gérant), envoie l’e-mail de vérification, ne connecte pas.
     *
     * @param  array<string, mixed>  $validated
     */
    public function creer(array $validated, Request $request, bool $notificationsDepuisFormulaire = true): User
    {
        $fullName = trim($validated['name']).' '.trim($validated['surname']);

        $parrainId = null;
        if (! empty($validated['code_parrainage'])) {
            $parrain = User::where('code_parrain', strtoupper($validated['code_parrainage']))->first();
            if ($parrain) {
                $parrainId = $parrain->id;
            }
        }

        $user = User::create([
            'name' => $fullName,
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true,
            'est_gerant' => false,
            'email_verified_at' => null,
            'date_naissance' => $validated['date_naissance'],
            'telephone' => $validated['telephone'],
            'adresse' => $validated['adresse'],
            'ville' => $validated['ville'],
            'code_postal' => $validated['code_postal'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'genre' => $validated['genre'] ?? 'non_precise',
            'source_inscription' => $validated['source_inscription'] ?? null,
            'code_parrain' => User::generateCodeParrain(),
            'parrain_id' => $parrainId,
            'cgu_accepted_at' => now(),
            'cgv_accepted_at' => now(),
            'confidentialite_accepted_at' => now(),
            'notifications_reservations' => $this->prefNotif($request, $validated, 'notifications_reservations', $notificationsDepuisFormulaire),
            'notifications_paiements' => $this->prefNotif($request, $validated, 'notifications_paiements', $notificationsDepuisFormulaire),
            'notifications_messages' => $this->prefNotif($request, $validated, 'notifications_messages', $notificationsDepuisFormulaire),
            'notifications_rappels' => $this->prefNotif($request, $validated, 'notifications_rappels', $notificationsDepuisFormulaire),
            'notifications_promotions' => $this->prefNotif($request, $validated, 'notifications_promotions', $notificationsDepuisFormulaire),
            'notifications_mises_a_jour' => $this->prefNotif($request, $validated, 'notifications_mises_a_jour', $notificationsDepuisFormulaire),
        ]);

        $emailVerification = EmailVerification::generateHashForUser($user->id);

        try {
            $user->notify(new EmailVerificationNotification($emailVerification));
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email de vérification : ".$e->getMessage());
        }

        SecurityLog::log(
            $user->id,
            'account_created',
            $request->ip(),
            $request->userAgent(),
            null,
            [],
            'low',
            false
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function prefNotif(Request $request, array $validated, string $cle, bool $depuisFormulaire): bool
    {
        if ($depuisFormulaire) {
            return $request->has($cle);
        }

        if (array_key_exists($cle, $validated) && $validated[$cle] !== null) {
            return filter_var($validated[$cle], FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }
}
