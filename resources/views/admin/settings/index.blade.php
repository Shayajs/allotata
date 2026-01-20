@extends('admin.layout')

@section('title', 'Paramètres système')
@section('header', 'Paramètres système')
@section('subheader', 'Configuration globale de la plateforme')

@section('content')
@php
    use App\Helpers\SiteHelper;
@endphp
<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    
    @foreach($settings as $group => $groupSettings)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 capitalize">
                @switch($group)
                    @case('general')
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Général
                        @break
                    @case('logos')
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Logos du site
                        @break
                    @case('subscription')
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Abonnements
                        @break
                    @case('commission')
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Commission
                        @break
                    @case('notifications')
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Notifications
                        @break
                    @default
                        {{ ucfirst($group) }}
                @endswitch
            </h2>
            
            <div class="space-y-4">
                @foreach($groupSettings as $setting)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start py-3 border-b border-slate-100 dark:border-slate-700 last:border-0">
                        <div>
                            <label for="{{ $setting->key }}" class="font-medium text-slate-900 dark:text-white">{{ $setting->label }}</label>
                            @if($setting->description)
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $setting->description }}</p>
                            @endif
                        </div>
                        <div class="md:col-span-2">
                            @switch($setting->type)
                                @case('boolean')
                                    <label class="flex items-center gap-3">
                                        <input 
                                            type="checkbox" 
                                            name="{{ $setting->key }}" 
                                            value="1"
                                            {{ $setting->value ? 'checked' : '' }}
                                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                        >
                                        <span class="text-sm text-slate-600 dark:text-slate-400">Activé</span>
                                    </label>
                                    @break
                                @case('integer')
                                @case('float')
                                    <input 
                                        type="number" 
                                        name="{{ $setting->key }}"
                                        id="{{ $setting->key }}"
                                        value="{{ $setting->value }}"
                                        step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                                        class="w-full max-w-xs px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                                    @break
                                @case('json')
                                    <textarea 
                                        name="{{ $setting->key }}"
                                        id="{{ $setting->key }}"
                                        rows="3"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-mono text-sm"
                                    >{{ $setting->value }}</textarea>
                                    @break
                                @default
                                    <input 
                                        type="text" 
                                        name="{{ $setting->key }}"
                                        id="{{ $setting->key }}"
                                        value="{{ $setting->value }}"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                    >
                            @endswitch
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Enregistrer les modifications
        </button>
    </div>
</form>

<!-- Gestion des logos du site -->
<div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <div class="flex items-center gap-3 mb-6">
        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">🎨 Logos du site</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Gérez les logos d'Allo Tata pour les emails, le site web et les favicons</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Logo Mode Clair -->
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Logo Mode Clair</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Utilisé pour le favicon en mode clair et l'affichage sur fond clair</p>
            
            @if($logoLight)
                <div class="mb-4 p-4 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                    <img src="{{ route('storage.serve', ['path' => $logoLight]) }}" alt="Logo mode clair" class="max-w-full h-20 object-contain mx-auto">
                </div>
                <form action="{{ route('admin.settings.delete-logo', 'light') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logo ?');" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-3 py-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        Supprimer
                    </button>
                </form>
            @endif
            
            <form action="{{ route('admin.settings.upload-logo-light') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo_light" id="logo_light" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp,image/svg+xml" class="hidden" onchange="this.form.submit()">
                <label for="logo_light" class="block w-full px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg cursor-pointer transition">
                    {{ $logoLight ? 'Remplacer' : 'Uploader' }} le logo
                </label>
            </form>
        </div>

        <!-- Logo Mode Sombre -->
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-slate-700 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Logo Mode Sombre</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Utilisé pour le favicon en mode sombre et l'affichage sur fond sombre</p>
            
            @if($logoDark)
                <div class="mb-4 p-4 bg-slate-900 rounded-lg border border-slate-700">
                    <img src="{{ route('storage.serve', ['path' => $logoDark]) }}" alt="Logo mode sombre" class="max-w-full h-20 object-contain mx-auto">
                </div>
                <form action="{{ route('admin.settings.delete-logo', 'dark') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logo ?');" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-3 py-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        Supprimer
                    </button>
                </form>
            @endif
            
            <form action="{{ route('admin.settings.upload-logo-dark') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo_dark" id="logo_dark" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp,image/svg+xml" class="hidden" onchange="this.form.submit()">
                <label for="logo_dark" class="block w-full px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg cursor-pointer transition">
                    {{ $logoDark ? 'Remplacer' : 'Uploader' }} le logo
                </label>
            </form>
        </div>

        <!-- Logo Sans Fond (Transparent) -->
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white">Logo Sans Fond</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Utilisé dans les emails et sur fonds colorés (format PNG avec transparence recommandé)</p>
            
            @if($logoTransparent)
                <div class="mb-4 p-4 bg-gradient-to-br from-green-100 to-orange-100 dark:from-green-900/30 dark:to-orange-900/30 rounded-lg border border-slate-200 dark:border-slate-700">
                    <img src="{{ route('storage.serve', ['path' => $logoTransparent]) }}" alt="Logo sans fond" class="max-w-full h-20 object-contain mx-auto">
                </div>
                <form action="{{ route('admin.settings.delete-logo', 'transparent') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logo ?');" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-3 py-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        Supprimer
                    </button>
                </form>
            @endif
            
            <form action="{{ route('admin.settings.upload-logo-transparent') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo_transparent" id="logo_transparent" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp,image/svg+xml" class="hidden" onchange="this.form.submit()">
                <label for="logo_transparent" class="block w-full px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg cursor-pointer transition">
                    {{ $logoTransparent ? 'Remplacer' : 'Uploader' }} le logo
                </label>
            </form>
        </div>
    </div>

    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <strong>💡 Recommandations :</strong> Utilisez des fichiers PNG ou SVG pour de meilleurs résultats. Le logo transparent doit être en PNG avec transparence pour un rendu optimal dans les emails.
        </p>
    </div>
</div>

<!-- Ajouter un paramètre -->
<div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">➕ Ajouter un paramètre</h2>
    <form method="POST" action="{{ route('admin.settings.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Clé</label>
            <input type="text" name="key" required placeholder="ma_cle" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Label</label>
            <input type="text" name="label" required placeholder="Mon paramètre" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Valeur</label>
            <input type="text" name="value" placeholder="valeur" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type</label>
            <select name="type" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="string">Texte</option>
                <option value="integer">Entier</option>
                <option value="float">Décimal</option>
                <option value="boolean">Booléen</option>
                <option value="json">JSON</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Groupe</label>
            <input type="text" name="group" required placeholder="general" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
        </div>
        <div>
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all">
                Ajouter
            </button>
        </div>
    </form>
</div>
@endsection
