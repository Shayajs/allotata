<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

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
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        $settings = $request->except('_token');
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
