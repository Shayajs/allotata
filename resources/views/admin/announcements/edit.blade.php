@extends('admin.layout')

@section('title', 'Modifier annonce')
@section('header', 'Modifier l\'annonce')
@section('subheader', $announcement->titre)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="titre" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Titre <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="titre" 
                    id="titre"
                    value="{{ old('titre', $announcement->titre) }}"
                    required
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Message <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="message" 
                    id="message"
                    rows="4"
                    required
                    class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                >{{ old('message', $announcement->message) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type</label>
                    <select name="type" id="type" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="info" {{ old('type', $announcement->type) === 'info' ? 'selected' : '' }}>Information</option>
                        <option value="success" {{ old('type', $announcement->type) === 'success' ? 'selected' : '' }}>Succès</option>
                        <option value="warning" {{ old('type', $announcement->type) === 'warning' ? 'selected' : '' }}>Avertissement</option>
                        <option value="danger" {{ old('type', $announcement->type) === 'danger' ? 'selected' : '' }}>Danger</option>
                    </select>
                </div>

                <div>
                    <label for="cible" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cible</label>
                    <select name="cible" id="cible" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="tous" {{ old('cible', $announcement->cible) === 'tous' ? 'selected' : '' }}>Tous les utilisateurs</option>
                        <option value="clients" {{ old('cible', $announcement->cible) === 'clients' ? 'selected' : '' }}>Clients uniquement</option>
                        <option value="gerants" {{ old('cible', $announcement->cible) === 'gerants' ? 'selected' : '' }}>Gérants uniquement</option>
                        <option value="admins" {{ old('cible', $announcement->cible) === 'admins' ? 'selected' : '' }}>Admins uniquement</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_debut" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de début</label>
                    <input type="datetime-local" name="date_debut" id="date_debut" value="{{ old('date_debut', $announcement->date_debut?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label for="date_fin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de fin</label>
                    <input type="datetime-local" name="date_fin" id="date_fin" value="{{ old('date_fin', $announcement->date_fin?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="est_actif" value="1" {{ old('est_actif', $announcement->est_actif) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Annonce active</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="afficher_banniere" value="1" {{ old('afficher_banniere', $announcement->afficher_banniere) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Afficher en bannière sur le site</span>
                </label>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Êtes-vous sûr ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ui-btn-simple text-red-600 hover:text-red-700 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Supprimer
                    </button>
                </form>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.announcements.index') }}" class="px-6 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                        Annuler
                    </a>
                    <button type="submit" class="ui-btn-simple px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
