<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntrepriseModification;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        if ($request->filled('statut')) {
            if ($request->statut === 'lue') {
                $query->where('est_lue', true);
            } elseif ($request->statut === 'non_lue') {
                $query->where('est_lue', false);
            }
        }

        $notifications = $query->paginate(20)->withQueryString();
        $nombreNonLues = $user->nombre_notifications_non_lues;
        $modifications = EntrepriseModification::pending()
            ->with(['entreprise.user', 'user'])
            ->latest()
            ->get();

        return view('admin.inbox.index', compact('notifications', 'nombreNonLues', 'modifications'));
    }

    public function show(int $id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->marquerCommeLue();

        if ($notification->lien) {
            return redirect($notification->lien);
        }

        return redirect()->route('admin.inbox.index');
    }

    public function marquerLue(int $id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->marquerCommeLue();

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function marquerToutesLues()
    {
        Notification::where('user_id', Auth::id())
            ->where('est_lue', false)
            ->update([
                'est_lue' => true,
                'lue_at' => now(),
            ]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
