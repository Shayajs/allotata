@extends('layouts.user')

@section('title', 'Feedback')

@section('content')
<div class="max-w-7xl 2xl:max-w-[1600px] mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
                Feedback & Suggestions
            </h1>
            <p class="text-slate-600 dark:text-slate-400">Partagez vos idées et votez pour les meilleures</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('feedback.dashboard') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition">
                Dashboard
            </a>
            @auth
                <a href="{{ route('feedback.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    + Nouveau feedback
                </a>
            @endauth
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Filtres -->
    <div class="mb-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Rechercher..."
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
            </div>
            <div class="flex-1 min-w-[150px]">
                <select 
                    name="categorie" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="">Toutes les catégories</option>
                    <option value="demande" {{ request('categorie') == 'demande' ? 'selected' : '' }}>Demande</option>
                    <option value="remerciement" {{ request('categorie') == 'remerciement' ? 'selected' : '' }}>Remerciement</option>
                    <option value="erreur" {{ request('categorie') == 'erreur' ? 'selected' : '' }}>Erreur</option>
                    <option value="conseil" {{ request('categorie') == 'conseil' ? 'selected' : '' }}>Conseil</option>
                    <option value="autre" {{ request('categorie') == 'autre' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <select 
                    name="statut" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="">Tous les statuts</option>
                    <option value="poste" {{ request('statut') == 'poste' ? 'selected' : '' }}>Posté</option>
                    <option value="traitement_en_cours" {{ request('statut') == 'traitement_en_cours' ? 'selected' : '' }}>Traitement en cours</option>
                    <option value="termine" {{ request('statut') == 'termine' ? 'selected' : '' }}>Terminé</option>
                    <option value="refuse" {{ request('statut') == 'refuse' ? 'selected' : '' }}>Refusé</option>
                    <option value="deja_fait" {{ request('statut') == 'deja_fait' ? 'selected' : '' }}>Déjà fait</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <select 
                    name="sort" 
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Plus récent</option>
                    <option value="votes" {{ request('sort') == 'votes' ? 'selected' : '' }}>Plus de votes</option>
                    <option value="commentaires" {{ request('sort') == 'commentaires' ? 'selected' : '' }}>Plus de commentaires</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                Filtrer
            </button>
        </form>
    </div>

    <!-- Liste des feedbacks -->
    <div class="space-y-4">
        @forelse($feedbacks as $feedback)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md transition">
                <div class="flex items-start gap-4">
                    <div class="flex flex-col items-center gap-2">
                        <button 
                            onclick="voteFeedback({{ $feedback->id }})"
                            class="flex flex-col items-center p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition {{ auth()->check() && $feedback->hasUserVoted(auth()->id()) ? 'bg-green-100 dark:bg-green-900/30' : '' }}"
                            @guest disabled @endguest
                        >
                            <svg class="w-6 h-6 {{ auth()->check() && $feedback->hasUserVoted(auth()->id()) ? 'text-green-600 dark:text-green-400' : 'text-slate-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-semibold" id="votes-{{ $feedback->id }}">{{ $feedback->votes_count }}</span>
                        </button>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <a href="{{ route('feedback.show', $feedback) }}" class="text-xl font-semibold text-slate-900 dark:text-white hover:text-green-600 dark:hover:text-green-400">
                                    {{ $feedback->titre }}
                                </a>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 rounded">{{ ucfirst($feedback->categorie) }}</span>
                                    <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded">{{ ucfirst(str_replace('_', ' ', $feedback->statut)) }}</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 mb-3 line-clamp-2">{{ Str::limit($feedback->description, 200) }}</p>
                        <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                            <span>{{ $feedback->user->name }}</span>
                            <span>{{ $feedback->created_at->diffForHumans() }}</span>
                            <span>{{ $feedback->comments_count }} commentaires</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <p class="text-slate-600 dark:text-slate-400">Aucun feedback trouvé.</p>
                @auth
                    <a href="{{ route('feedback.create') }}" class="mt-4 inline-block px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Créer le premier feedback
                    </a>
                @endauth
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $feedbacks->links() }}
    </div>
</div>

@push('scripts')
<script>
function voteFeedback(feedbackId) {
    @auth
        fetch(`/feedback/${feedbackId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`votes-${feedbackId}`).textContent = data.votesCount;
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    @else
        window.location.href = '{{ route("login") }}';
    @endauth
}
</script>
@endpush
@endsection
