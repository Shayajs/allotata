<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GdprRequest;
use App\Models\User;
use App\Services\GdprService;
use Illuminate\Http\Request;

class GdprController extends Controller
{
    public function __construct(
        protected GdprService $gdprService,
    ) {}

    /**
     * Dashboard RGPD admin : liste des demandes, configuration
     */
    public function index(Request $request)
    {
        $query = GdprRequest::with(['user', 'requestedBy'])
            ->orderByDesc('created_at');

        // Filtres
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(25);

        // Statistiques
        $stats = [
            'pending_deletions' => GdprRequest::where('type', 'deletion')->where('status', 'pending')->count(),
            'pending_exports' => GdprRequest::where('type', 'export')->whereIn('status', ['pending', 'processing'])->count(),
            'completed_total' => GdprRequest::where('status', 'completed')->count(),
            'total' => GdprRequest::count(),
        ];

        $delayDays = $this->gdprService->getDeletionDelayDays();

        return view('admin.gdpr.index', compact('requests', 'stats', 'delayDays'));
    }

    /**
     * Générer un export pour un utilisateur (initié par l'admin)
     */
    public function generateExport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($request->user_id);
        $admin = auth()->user();

        $gdprRequest = $this->gdprService->requestExport($user, $admin, $request->reason);

        if ($gdprRequest->isCompleted()) {
            return back()->with('success', "Export généré avec succès pour {$user->name} ({$user->email}).");
        }

        return back()->with('error', 'Erreur lors de la génération de l\'export.');
    }

    /**
     * Créer une demande de suppression pour un utilisateur (initié par l'admin)
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'La raison est obligatoire pour les demandes initiées par l\'administrateur.',
        ]);

        $user = User::findOrFail($request->user_id);
        $admin = auth()->user();

        // Ne pas supprimer un admin
        if ($user->isAdmin()) {
            return back()->with('error', 'Impossible de supprimer un compte administrateur via cette interface.');
        }

        $gdprRequest = $this->gdprService->requestDeletion($user, $admin, $request->reason);

        $delayDays = $this->gdprService->getDeletionDelayDays();

        return back()->with('success', "Demande de suppression créée pour {$user->name}. Exécution prévue dans {$delayDays} jours.");
    }

    /**
     * Exécuter immédiatement une suppression (sans attendre le délai)
     */
    public function executeNow(GdprRequest $gdprRequest)
    {
        if (!$gdprRequest->isDeletion()) {
            return back()->with('error', 'Cette demande n\'est pas une demande de suppression.');
        }

        if ($gdprRequest->isCompleted()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $success = $this->gdprService->executeDeletion($gdprRequest);

        if ($success) {
            return back()->with('success', 'Suppression/anonymisation effectuée avec succès.');
        }

        return back()->with('error', 'Erreur lors de l\'exécution de la suppression. Consultez les logs.');
    }

    /**
     * Annuler une demande (admin)
     */
    public function cancel(GdprRequest $gdprRequest)
    {
        if (!$gdprRequest->canBeCancelled()) {
            return back()->with('error', 'Cette demande ne peut plus être annulée.');
        }

        $this->gdprService->cancelDeletion($gdprRequest);

        return back()->with('success', 'Demande annulée avec succès.');
    }

    /**
     * Télécharger un export (admin)
     */
    public function downloadExport(GdprRequest $gdprRequest)
    {
        if (!$gdprRequest->isExport() || !$gdprRequest->export_path) {
            return back()->with('error', 'Aucun fichier d\'export disponible.');
        }

        $absolutePath = storage_path("app/{$gdprRequest->export_path}");

        if (!file_exists($absolutePath)) {
            return back()->with('error', 'Le fichier d\'export n\'a pas été trouvé sur le disque.');
        }

        $userName = $gdprRequest->user?->name ?? 'utilisateur_inconnu';
        $fileName = "export_rgpd_{$userName}_" . now()->format('Y-m-d') . '.zip';

        return response()->download($absolutePath, $fileName);
    }

    /**
     * Mettre à jour le délai de grâce
     */
    public function updateDelay(Request $request)
    {
        $request->validate([
            'delay_days' => 'required|integer|min:0|max:365',
        ]);

        $this->gdprService->setDeletionDelayDays($request->delay_days);

        return back()->with('success', "Délai de grâce mis à jour : {$request->delay_days} jours.");
    }

    /**
     * Rechercher un utilisateur (pour le formulaire admin)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $users = User::where('statut_compte', '!=', 'supprime')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'est_client', 'est_gerant']);

        return response()->json($users);
    }
}
