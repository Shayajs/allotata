<?php

namespace App\Http\Controllers;

use App\Models\GdprRequest;
use App\Services\GdprService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GdprController extends Controller
{
    public function __construct(
        protected GdprService $gdprService,
    ) {}

    /**
     * Demander l'export de ses données personnelles
     */
    public function requestExport(Request $request)
    {
        $user = Auth::user();

        // Vérifier qu'il n'y a pas déjà un export en cours
        $pendingExport = GdprRequest::forUser($user->id)
            ->where('type', GdprRequest::TYPE_EXPORT)
            ->whereIn('status', [GdprRequest::STATUS_PENDING, GdprRequest::STATUS_PROCESSING])
            ->first();

        if ($pendingExport) {
            return back()->with('error', 'Un export est déjà en cours de traitement. Veuillez patienter.');
        }

        // Vérifier qu'on n'a pas fait d'export récemment (rate-limit : 1 par 24h)
        $recentExport = GdprRequest::forUser($user->id)
            ->where('type', GdprRequest::TYPE_EXPORT)
            ->where('status', GdprRequest::STATUS_COMPLETED)
            ->where('created_at', '>', now()->subDay())
            ->first();

        if ($recentExport) {
            return back()->with('error', 'Vous avez déjà demandé un export dans les dernières 24 heures. Le lien de téléchargement est disponible ci-dessous.');
        }

        $gdprRequest = $this->gdprService->requestExport($user);

        if ($gdprRequest->isCompleted()) {
            return back()->with('success', 'Votre export a été généré avec succès. Vous pouvez le télécharger ci-dessous.');
        }

        return back()->with('error', 'Une erreur est survenue lors de la génération de l\'export. Veuillez réessayer ou contacter le support.');
    }

    /**
     * Télécharger un export généré
     */
    public function downloadExport(GdprRequest $gdprRequest)
    {
        $user = Auth::user();

        // Vérifier que la demande appartient à l'utilisateur
        if ($gdprRequest->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if (!$gdprRequest->isDownloadAvailable()) {
            return back()->with('error', 'Le lien de téléchargement a expiré ou l\'export n\'est pas disponible.');
        }

        $absolutePath = storage_path("app/{$gdprRequest->export_path}");

        if (!file_exists($absolutePath)) {
            return back()->with('error', 'Le fichier d\'export n\'a pas été trouvé. Veuillez en générer un nouveau.');
        }

        $fileName = "mes_donnees_allotata_" . now()->format('Y-m-d') . '.zip';

        return response()->download($absolutePath, $fileName);
    }

    /**
     * Demander la suppression de son compte
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:1000',
            'confirm_deletion' => 'required|accepted',
        ], [
            'password.required' => 'Veuillez saisir votre mot de passe pour confirmer.',
            'confirm_deletion.accepted' => 'Vous devez confirmer que vous comprenez les conséquences de la suppression.',
        ]);

        $user = Auth::user();

        // Vérifier le mot de passe
        if (!\Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Le mot de passe est incorrect.');
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente
        $existingRequest = GdprRequest::forUser($user->id)
            ->where('type', GdprRequest::TYPE_DELETION)
            ->whereIn('status', [GdprRequest::STATUS_PENDING, GdprRequest::STATUS_PROCESSING])
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'Une demande de suppression est déjà en cours. Vous pouvez l\'annuler depuis cette page.');
        }

        $gdprRequest = $this->gdprService->requestDeletion($user, null, $request->reason);
        $delayDays = $this->gdprService->getDeletionDelayDays();

        return back()->with('success', "Votre demande de suppression a été enregistrée. Votre compte sera définitivement supprimé dans {$delayDays} jours. Vous pouvez annuler cette demande à tout moment pendant ce délai.");
    }

    /**
     * Annuler une demande de suppression
     */
    public function cancelDeletion(GdprRequest $gdprRequest)
    {
        $user = Auth::user();

        if ($gdprRequest->user_id !== $user->id) {
            abort(403);
        }

        if (!$gdprRequest->canBeCancelled()) {
            return back()->with('error', 'Cette demande ne peut plus être annulée.');
        }

        $this->gdprService->cancelDeletion($gdprRequest);

        return back()->with('success', 'Votre demande de suppression a été annulée. Votre compte est conservé.');
    }

    /**
     * Récupère l'état RGPD pour l'onglet confidentialité (données injectées dans la vue)
     */
    public static function getGdprDataForUser(int $userId): array
    {
        // Dernier export disponible
        $lastExport = GdprRequest::forUser($userId)
            ->where('type', GdprRequest::TYPE_EXPORT)
            ->where('status', GdprRequest::STATUS_COMPLETED)
            ->latest()
            ->first();

        // Demande de suppression en cours
        $pendingDeletion = GdprRequest::forUser($userId)
            ->where('type', GdprRequest::TYPE_DELETION)
            ->where('status', GdprRequest::STATUS_PENDING)
            ->first();

        // Export en cours
        $pendingExport = GdprRequest::forUser($userId)
            ->where('type', GdprRequest::TYPE_EXPORT)
            ->whereIn('status', [GdprRequest::STATUS_PENDING, GdprRequest::STATUS_PROCESSING])
            ->first();

        // Historique des demandes
        $history = GdprRequest::forUser($userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'lastExport' => $lastExport,
            'pendingDeletion' => $pendingDeletion,
            'pendingExport' => $pendingExport,
            'history' => $history,
        ];
    }
}
