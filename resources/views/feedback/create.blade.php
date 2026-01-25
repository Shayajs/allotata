@extends('layouts.user')

@section('title', 'Créer un feedback')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('feedback.index') }}" class="text-green-600 dark:text-green-400 hover:underline flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Retour aux feedbacks
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Créer un nouveau feedback</h1>

        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-400">
                <strong>Astuce :</strong> Vérifiez si un feedback similaire existe déjà avant de créer le vôtre. Vous pouvez voter pour les feedbacks existants plutôt que d'en créer un nouveau.
            </p>
        </div>

        <form method="POST" action="{{ route('feedback.store') }}" id="feedback-form">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Titre *
                </label>
                <input 
                    type="text" 
                    name="titre" 
                    id="titre"
                    value="{{ old('titre') }}"
                    required
                    autocomplete="off"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    placeholder="Ex: Ajouter une fonctionnalité de..."
                >
                <div id="suggestions" class="mt-2 hidden bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <!-- Suggestions seront injectées ici -->
                </div>
                @error('titre')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Catégorie *
                </label>
                <select 
                    name="categorie" 
                    required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                    <option value="demande" {{ old('categorie') == 'demande' ? 'selected' : '' }}>Demande</option>
                    <option value="remerciement" {{ old('categorie') == 'remerciement' ? 'selected' : '' }}>Remerciement</option>
                    <option value="erreur" {{ old('categorie') == 'erreur' ? 'selected' : '' }}>Erreur</option>
                    <option value="conseil" {{ old('categorie') == 'conseil' ? 'selected' : '' }}>Conseil</option>
                    <option value="autre" {{ old('categorie') == 'autre' ? 'selected' : '' }}>Autre</option>
                </select>
                @error('categorie')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Description *
                </label>
                <textarea 
                    name="description" 
                    rows="8"
                    required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    placeholder="Décrivez votre feedback en détail..."
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Publier
                </button>
                <a href="{{ route('feedback.index') }}" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let searchTimeout;
const titreInput = document.getElementById('titre');
const suggestionsDiv = document.getElementById('suggestions');

titreInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        suggestionsDiv.classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`/api/feedback/search-titres?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    suggestionsDiv.innerHTML = data.map(item => `
                        <a href="${item.url}" class="block p-3 hover:bg-slate-100 dark:hover:bg-slate-700 border-b border-slate-200 dark:border-slate-700 last:border-b-0">
                            <div class="font-semibold text-slate-900 dark:text-white">${item.titre}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">${item.votes} votes</div>
                        </a>
                    `).join('');
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                suggestionsDiv.classList.add('hidden');
            });
    }, 300);
});

// Fermer les suggestions en cliquant ailleurs
document.addEventListener('click', function(e) {
    if (!titreInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
        suggestionsDiv.classList.add('hidden');
    }
});
</script>
@endpush
@endsection
