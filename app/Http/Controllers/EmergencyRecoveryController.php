<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmergencyRecoveryController extends Controller
{
    protected $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

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
        
        // Récupérer les sauvegardes disponibles
        try {
            $backups = $this->backupService->listBackups();
        } catch (\Exception $e) {
            $backups = [];
            Log::warning("Erreur lors de la récupération des sauvegardes dans emergency recovery: " . $e->getMessage());
        }

        return view('emergency-recovery.index', [
            'users' => $users,
            'adminCount' => $adminCount,
            'backups' => $backups,
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

    /**
     * Importer une sauvegarde depuis un fichier (route de secours)
     */
    public function importBackup(Request $request)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        if ($request->input('secret_token') !== $secretToken) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:sql,gz|max:102400', // Max 100MB
        ]);

        try {
            $file = $request->file('backup_file');
            $originalName = $file->getClientOriginalName();
            
            // Déplacer le fichier vers le dossier de sauvegardes
            $backupPath = storage_path('app/backups/database');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $filename = 'imported_' . time() . '_' . $originalName;
            $filepath = $backupPath . '/' . $filename;
            
            $file->move($backupPath, $filename);
            
            // Si c'est un fichier .gz, le décompresser
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'gz') {
                $gz = gzopen($filepath, 'rb');
                $sqlFilepath = str_replace('.gz', '', $filepath);
                $sqlFile = fopen($sqlFilepath, 'wb');
                
                while (!gzeof($gz)) {
                    fwrite($sqlFile, gzread($gz, 4096));
                }
                
                fclose($sqlFile);
                gzclose($gz);
                unlink($filepath);
                
                $filepath = $sqlFilepath;
                $filename = basename($sqlFilepath);
            }

            // Logger l'action critique
            Log::critical("EMERGENCY RECOVERY: Sauvegarde importée", [
                'filename' => $filename,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('success', "✅ Sauvegarde importée avec succès : {$filename}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'import de sauvegarde dans emergency recovery: " . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }
    }

    /**
     * Restaurer une sauvegarde (route de secours)
     */
    public function restoreBackup(Request $request, $filename)
    {
        // Vérifier le token secret
        $secretToken = env('EMERGENCY_RECOVERY_TOKEN', 'change-me-in-production-' . Str::random(32));
        if ($request->input('secret_token') !== $secretToken) {
            abort(403, 'Unauthorized');
        }

        try {
            $filepath = storage_path('app/backups/database/' . $filename);
            
            if (!file_exists($filepath)) {
                return back()->with('error', 'Le fichier de sauvegarde n\'existe pas');
            }

            $result = $this->backupService->restoreBackup($filepath, true);

            // Logger l'action critique
            Log::critical("EMERGENCY RECOVERY: Base de données restaurée", [
                'filename' => $filename,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('success', '✅ Base de données restaurée avec succès !');
        } catch (\Exception $e) {
            Log::error("Erreur lors de la restauration dans emergency recovery: " . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la restauration: ' . $e->getMessage());
        }
    }
}
