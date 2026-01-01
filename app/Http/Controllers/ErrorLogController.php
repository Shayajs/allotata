<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ErrorLogController extends Controller
{
    /**
     * Afficher la page des logs d'erreurs
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est admin
        if (!$user->is_admin) {
            abort(403, 'Accès refusé');
        }

        // Requête de base
        $query = ErrorLog::query();
        
        // Filtre par statut
        $status = $request->input('status', 'unread');
        if ($status === 'unread') {
            $query->where('est_vue', false);
        } elseif ($status === 'read') {
            $query->where('est_vue', true);
        }
        
        // Filtre par niveau
        $level = $request->input('level');
        if ($level) {
            $query->where('level', $level);
        }
        
        // Récupérer les erreurs paginées
        $errors = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Statistiques
        $stats = [
            'total' => ErrorLog::count(),
            'unread' => ErrorLog::where('est_vue', false)->count(),
            'read' => ErrorLog::where('est_vue', true)->count(),
            'today' => ErrorLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.errors.index', compact('errors', 'stats'));
    }
    
    /**
     * API : Récupérer les erreurs récentes non vues (pour notifications temps réel)
     */
    public function api(Request $request)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est admin
        if (!$user->is_admin) {
            return response()->json(['errors' => []]);
        }

        // Vérifier que la colonne existe et que les notifications sont activées
        if (!Schema::hasColumn('users', 'notifications_erreurs_actives') || 
            !isset($user->notifications_erreurs_actives) || 
            !$user->notifications_erreurs_actives) {
            return response()->json(['errors' => []]);
        }

        $limit = $request->input('limit', 10);
        $lastId = $request->input('last_id', 0);

        $errors = ErrorLog::where('id', '>', $lastId)
            ->where('est_vue', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($error) {
                $data = [
                    'id' => $error->id,
                    'level' => $error->level,
                    'message' => $error->message,
                    'file' => $error->file,
                    'line' => $error->line,
                    'url' => $error->url,
                    'method' => $error->method,
                    'created_at' => $error->created_at->diffForHumans(),
                    'created_at_full' => $error->created_at->format('d/m/Y H:i:s'),
                ];
                
                // Si le mode debug est activé, inclure plus de détails
                if (config('app.debug')) {
                    $data['trace'] = $error->trace;
                    $data['context'] = $error->context;
                    $data['ip'] = $error->ip;
                    $data['user_agent'] = $error->user_agent;
                }
                
                return $data;
            });

        return response()->json([
            'errors' => $errors,
            'count' => $errors->count(),
        ]);
    }

    /**
     * Marquer une erreur comme vue
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        
        if (!$user->is_admin) {
            return response()->json(['success' => false], 403);
        }

        $error = ErrorLog::findOrFail($id);
        $error->marquerCommeVue();

        return response()->json(['success' => true]);
    }

    /**
     * Marquer toutes les erreurs comme vues
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        if (!$user->is_admin) {
            return response()->json(['success' => false], 403);
        }

        ErrorLog::where('est_vue', false)->update([
            'est_vue' => true,
            'vu_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
