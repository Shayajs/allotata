<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Liste des logs d'activité
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('admin')
            ->orderBy('created_at', 'desc');

        // Filtre par action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filtre par admin
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filtre par date
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        // Actions uniques pour le filtre
        $actions = ActivityLog::distinct()->pluck('action');

        // Admins pour le filtre
        $admins = \App\Models\User::where('is_admin', true)->get();

        return view('admin.activity-logs.index', compact('logs', 'actions', 'admins'));
    }
}
