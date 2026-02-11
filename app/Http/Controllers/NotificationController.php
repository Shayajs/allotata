<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Afficher toutes les notifications
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications();

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par statut (lue/non lue)
        if ($request->filled('statut')) {
            if ($request->statut === 'lue') {
                $query->where('est_lue', true);
            } elseif ($request->statut === 'non_lue') {
                $query->where('est_lue', false);
            }
        }

        $notifications = $query->paginate(20)->withQueryString();
        $nombreNonLues = $user->nombre_notifications_non_lues;

        return view('notifications.index', [
            'notifications' => $notifications,
            'nombreNonLues' => $nombreNonLues,
        ]);
    }

    /**
     * Afficher une notification
     */
    public function show($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);

        // Marquer comme lue
        $notification->marquerCommeLue();

        return view('notifications.show', [
            'notification' => $notification,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLue($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        
        $notification->marquerCommeLue();

        return back()->with('success', 'Notification marquée comme lue.');
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesLues()
    {
        $user = Auth::user();
        
        Notification::where('user_id', $user->id)
            ->where('est_lue', false)
            ->update([
                'est_lue' => true,
                'lue_at' => now(),
            ]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        
        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}
