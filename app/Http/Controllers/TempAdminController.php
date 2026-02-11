<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TempAdminController extends Controller
{
    /**
     * Afficher la page d'administration temporaire
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_clients' => User::where('est_client', true)->count(),
            'total_gerants' => User::where('est_gerant', true)->count(),
            'total_admins' => User::where('is_admin', true)->count(),
            'total_entreprises' => Entreprise::count(),
            'entreprises_verifiees' => Entreprise::where('est_verifiee', true)->count(),
            'entreprises_en_attente' => Entreprise::where('est_verifiee', false)->count(),
            'total_reservations' => Reservation::count(),
            'reservations_payees' => Reservation::where('est_paye', true)->count(),
        ];

        $users = User::orderBy('created_at', 'desc')->paginate(20);
        $admins = User::where('is_admin', true)->get();

        return view('temp-admin.index', [
            'stats' => $stats,
            'users' => $users,
            'admins' => $admins,
        ]);
    }

    /**
     * Créer un nouveau compte admin
     */
    public function createAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'est_client' => ['boolean'],
            'est_gerant' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => $validated['est_client'] ?? true,
            'est_gerant' => $validated['est_gerant'] ?? false,
            'is_admin' => true, // Toujours admin pour cette page temporaire
        ]);

        return back()->with('success', "Compte admin créé avec succès pour {$user->name} ({$user->email})");
    }

    /**
     * Promouvoir un utilisateur existant en admin
     */
    public function promoteToAdmin(Request $request, User $user)
    {
        $user->update(['is_admin' => true]);

        return back()->with('success', "{$user->name} a été promu administrateur avec succès.");
    }

    /**
     * Retirer les droits admin d'un utilisateur
     */
    public function demoteFromAdmin(Request $request, User $user)
    {
        // Ne pas permettre de retirer les droits si c'est le dernier admin
        $adminCount = User::where('is_admin', true)->count();
        if ($adminCount <= 1) {
            return back()->withErrors(['error' => 'Impossible de retirer les droits admin : il doit y avoir au moins un administrateur.']);
        }

        $user->update(['is_admin' => false]);

        return back()->with('success', "Les droits administrateur ont été retirés à {$user->name}.");
    }

    /**
     * Se connecter en tant qu'utilisateur (pour tester)
     */
    public function loginAs(Request $request, User $user)
    {
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "Vous êtes maintenant connecté en tant que {$user->name}.");
    }
}
