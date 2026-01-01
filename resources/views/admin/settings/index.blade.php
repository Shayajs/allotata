@extends('admin.layout')

@section('title', 'Paramètres système')
@section('header', '⚙️ Paramètres système')
@section('subheader', 'Configuration globale de la plateforme')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    
    @foreach($settings as $group => $groupSettings)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 capitalize">
                @switch($group)
                    @case('general')
                        🏠 Général
                        @break
                    @case('subscription')
                        💳 Abonnements
                        @break
                    @case('commission')
                        💰 Commission
                        @break
                    @case('notifications')
                        🔔 Notifications
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
            ✅ Enregistrer les modifications
        </button>
    </div>
</form>

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
