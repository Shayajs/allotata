<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\InjectSiteFavicon;
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
        $logoPwa = Setting::get('site_logo_pwa', null);
        
        return view('admin.settings.index', compact('settings', 'logoLight', 'logoDark', 'logoTransparent', 'logoPwa'));
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
     * Uploader le logo PWA et générer les icônes
     */
    public function uploadLogoPwa(Request $request)
    {
        $request->validate([
            'logo_pwa' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Jusqu'à 5MB pour une bonne qualité source
        ]);

        try {
            $file = $request->file('logo_pwa');
            $imageService = app(ImageService::class);
            
            // 1. Sauvegarder l'original comme "site_logo_pwa" via ImageService
            $extension = $file->getClientOriginalExtension();
            $filename = 'logo_pwa.' . $extension;
            $logoPath = $imageService->processAndStore($file, 'site_logos', $filename);
            
            // Mettre à jour le setting pour l'original
            Setting::set('site_logo_pwa', $logoPath, 'string');
            
            // 2. Générer les icônes (192, 512, 1024) dans public/icons/
            $sourcePath = Storage::disk('public')->path($logoPath);
            $iconsDir = public_path('icons');
            
            if (!file_exists($iconsDir)) {
                mkdir($iconsDir, 0755, true);
            }
            
            $sizes = [192, 512, 1024];
            
            foreach ($sizes as $size) {
                $this->generateIcon($sourcePath, $iconsDir . "/icon-{$size}x{$size}.png", $size);
            }
            
            ActivityLog::log('update', "Mise à jour du Logo PWA et génération des icônes");
            
            return back()->with('success', "Le logo PWA a été mis à jour et les icônes ont été régénérées.");
            
        } catch (\Exception $e) {
            \Log::error("Erreur lors de l'upload du logo PWA", [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', "Erreur lors du traitement du logo PWA : " . $e->getMessage());
        }
    }

    /**
     * Génère une icône carrée redimensionnée
     */
    private function generateIcon($sourcePath, $destPath, $size)
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) return;
        
        $mime = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        switch ($mime) {
            case 'image/jpeg': $source = imagecreatefromjpeg($sourcePath); break;
            case 'image/png': $source = imagecreatefrompng($sourcePath); break;
            case 'image/gif': $source = imagecreatefromgif($sourcePath); break;
            case 'image/webp': $source = imagecreatefromwebp($sourcePath); break;
            default: return;
        }
        
        $dest = imagecreatetruecolor($size, $size);
        
        // Gérer la transparence
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $size, $size, $transparent);
        
        // Redimensionner
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $size, $size, $width, $height);
        
        // Sauvegarder en PNG
        imagepng($dest, $destPath, 9);
        
        imagedestroy($source);
        imagedestroy($dest);
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

            if (in_array($settingKey, ['site_logo_light', 'site_logo_dark'], true)) {
                InjectSiteFavicon::clearCache();
            }
            
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
        $allowedTypes = ['light', 'dark', 'transparent', 'pwa'];
        
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
