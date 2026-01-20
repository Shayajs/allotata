<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmergencyRecoveryController extends Controller
{
    /**
     * Afficher la page de récupération d'urgence
     */
    public function index(Request $request)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        $providedToken = $request->get('token');
        
        if ($providedToken !== $secretToken) {
            abort(404, 'Page not found');
        }

        $users = User::orderBy('created_at', 'desc')->limit(50)->get(['id', 'name', 'email', 'is_admin', 'created_at']);
        $adminCount = User::where('is_admin', true)->count();

        return view('emergency-recovery.index', [
            'users' => $users,
            'adminCount' => $adminCount,
            'token' => $secretToken, // Pour les formulaires
        ]);
    }

    /**
     * Créer un nouveau compte admin d'urgence
     */
    public function createAdmin(Request $request)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        if ($request->input('secret_token') !== $secretToken) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => true,
            'est_client' => true,
            'email_verified_at' => now(), // Auto-vérifier pour l'urgence
        ]);

        // Logger l'action critique
        Log::critical("EMERGENCY RECOVERY: Nouveau compte admin créé", [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "✅ Compte admin créé avec succès : {$user->email}");
    }

    /**
     * Promouvoir un utilisateur existant en admin
     */
    public function promoteToAdmin(Request $request, $userId)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        if ($request->input('secret_token') !== $secretToken) {
            abort(403, 'Unauthorized');
        }

        $user = User::findOrFail($userId);
        
        if ($user->is_admin) {
            return back()->with('info', "{$user->name} est déjà administrateur.");
        }

        $user->update(['is_admin' => true]);

        // Logger l'action critique
        Log::critical("EMERGENCY RECOVERY: Utilisateur promu admin", [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "✅ {$user->name} ({$user->email}) a été promu administrateur.");
    }

    /**
     * Se connecter directement en tant qu'utilisateur
     */
    public function loginAs(Request $request, $userId)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        if ($request->input('secret_token') !== $secretToken) {
            abort(403, 'Unauthorized');
        }

        $user = User::findOrFail($userId);
        Auth::login($user);

        // Logger l'action critique
        Log::critical("EMERGENCY RECOVERY: Connexion directe", [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Vous êtes connecté en tant que {$user->name}.");
    }
}
