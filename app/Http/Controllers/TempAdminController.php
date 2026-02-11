<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TempAdminController extends Controller
{
    /**
     * Page de bootstrap : création du premier administrateur.
     * Accessible uniquement s'il n'existe aucun admin (middleware no.admin.exists).
     */
    public function index()
    {
        return view('temp-admin.index');
    }

    /**
     * Créer le premier compte administrateur.
     */
    public function createAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'est_client' => true,
            'est_gerant' => true,
        ]);

        // Assignation explicite du rôle admin (hors mass assignment)
        $user->is_admin = true;
        $user->save();

        return redirect()->route('signin')
            ->with('success', "Compte administrateur créé pour {$user->name}. Connectez-vous maintenant.");
    }
}
