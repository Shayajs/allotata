<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Afficher les paramètres
     */
    public function index()
    {
        // Initialiser les paramètres par défaut si nécessaire
        Setting::initDefaults();
        
        $settings = Setting::getAllGrouped();
        
        // Récupérer les chemins des logos actuels
        $logoLight = Setting::get('site_logo_light', null);
        $logoDark = Setting::get('site_logo_dark', null);
        $logoTransparent = Setting::get('site_logo_transparent', null);
        
        return view('admin.settings.index', compact('settings', 'logoLight', 'logoDark', 'logoTransparent'));
    }

    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        $settings = $request->except(['_token', 'logo_light', 'logo_dark', 'logo_transparent']);
        $changes = [];

        foreach ($settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if ($setting) {
                $oldValue = $setting->value;
                
                // Gérer les checkboxes
                if ($setting->type === 'boolean') {
                    $value = $value ? '1' : '0';
                }
                
                if ($oldValue !== $value) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $value];
                    $setting->update(['value' => $value]);
                }
            }
        }

        if (!empty($changes)) {
            ActivityLog::log('update', 'Mise à jour des paramètres système', null, $changes);
        }

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Uploader le logo mode clair
     */
    public function uploadLogoLight(Request $request)
    {
        $validated = $request->validate([
            'logo_light' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        return $this->uploadLogo($request->file('logo_light'), 'site_logo_light', 'Logo mode clair');
    }

    /**
     * Uploader le logo mode sombre
     */
    public function uploadLogoDark(Request $request)
    {
        $validated = $request->validate([
            'logo_dark' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        return $this->uploadLogo($request->file('logo_dark'), 'site_logo_dark', 'Logo mode sombre');
    }

    /**
     * Uploader le logo sans fond (transparent)
     */
    public function uploadLogoTransparent(Request $request)
    {
        $validated = $request->validate([
            'logo_transparent' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        return $this->uploadLogo($request->file('logo_transparent'), 'site_logo_transparent', 'Logo sans fond');
    }

    /**
     * Méthode privée pour uploader un logo
     */
    private function uploadLogo($file, string $settingKey, string $label)
    {
        try {
            $imageService = app(ImageService::class);
            
            // Nom de fichier standardisé
            $extension = $file->getClientOriginalExtension();
            $filename = 'logo_' . str_replace('site_logo_', '', $settingKey) . '.' . $extension;
            
            // Uploader le logo
            $logoPath = $imageService->processAndStore($file, 'site_logos', $filename);
            
            // Récupérer l'ancien logo
            $oldLogoPath = Setting::get($settingKey, null);
            
            // Mettre à jour le setting
            Setting::set($settingKey, $logoPath, 'string');
            
            // Supprimer l'ancien logo si différent
            if ($oldLogoPath && $oldLogoPath !== $logoPath && Storage::disk('public')->exists($oldLogoPath)) {
                try {
                    Storage::disk('public')->delete($oldLogoPath);
                } catch (\Exception $e) {
                    \Log::warning("Erreur lors de la suppression de l'ancien logo {$settingKey}", [
                        'path' => $oldLogoPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            ActivityLog::log('update', "Mise à jour du {$label}");
            
            return back()->with('success', "Le {$label} a été mis à jour avec succès.");
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'upload du logo {$settingKey}", [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', "Erreur lors de l'upload du logo : " . $e->getMessage());
        }
    }

    /**
     * Supprimer un logo
     */
    public function deleteLogo(Request $request, string $type)
    {
        $allowedTypes = ['light', 'dark', 'transparent'];
        
        if (!in_array($type, $allowedTypes)) {
            return back()->with('error', 'Type de logo invalide.');
        }
        
        $settingKey = 'site_logo_' . $type;
        $logoPath = Setting::get($settingKey, null);
        
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            try {
                Storage::disk('public')->delete($logoPath);
            } catch (\Exception $e) {
                \Log::warning("Erreur lors de la suppression du logo {$settingKey}", [
                    'path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Setting::set($settingKey, null, 'string');
        ActivityLog::log('update', "Suppression du logo mode {$type}");
        
        return back()->with('success', "Le logo a été supprimé avec succès.");
    }

    /**
     * Créer un nouveau paramètre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'nullable|string',
            'type' => 'required|in:string,integer,float,boolean,json',
            'group' => 'required|string|max:100',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $setting = Setting::create($validated);
        
        ActivityLog::log('create', "Création du paramètre {$validated['key']}", $setting);

        return back()->with('success', 'Paramètre créé avec succès.');
    }

    /**
     * Supprimer un paramètre
     */
    public function destroy(Setting $setting)
    {
        $key = $setting->key;
        $setting->delete();
        
        ActivityLog::log('delete', "Suppression du paramètre {$key}");

        return back()->with('success', 'Paramètre supprimé.');
    }
}
