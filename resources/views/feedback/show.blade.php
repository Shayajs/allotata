@extends('layouts.user')

@section('title', $feedback->titre)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('feedback.index') }}" class="text-green-600 dark:text-green-400 hover:underline flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Retour aux feedbacks
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex flex-col items-center gap-2">
                <button 
                    onclick="voteFeedback({{ $feedback->id }})"
                    class="flex flex-col items-center p-3 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition {{ $hasVoted ? 'bg-green-100 dark:bg-green-900/30' : '' }}"
                    @guest disabled @endguest
                >
                    <svg class="w-8 h-8 {{ $hasVoted ? 'text-green-600 dark:text-green-400' : 'text-slate-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-lg font-bold" id="votes-{{ $feedback->id }}">{{ $feedback->votes_count }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">votes</span>
                </button>
            </div>
            <div class="flex-1">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ $feedback->titre }}</h1>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 rounded">{{ ucfirst($feedback->categorie) }}</span>
                            <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded">{{ ucfirst(str_replace('_', ' ', $feedback->statut)) }}</span>
                        </div>
                    </div>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('feedback.admin.update', $feedback) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="statut" onchange="this.form.submit()" class="text-sm px-3 py-1 border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700">
                                    <option value="poste" {{ $feedback->statut == 'poste' ? 'selected' : '' }}>Posté</option>
                                    <option value="traitement_en_cours" {{ $feedback->statut == 'traitement_en_cours' ? 'selected' : '' }}>Traitement en cours</option>
                                    <option value="termine" {{ $feedback->statut == 'termine' ? 'selected' : '' }}>Terminé</option>
                                    <option value="refuse" {{ $feedback->statut == 'refuse' ? 'selected' : '' }}>Refusé</option>
                                    <option value="deja_fait" {{ $feedback->statut == 'deja_fait' ? 'selected' : '' }}>Déjà fait</option>
                                </select>
                                <select name="categorie" onchange="this.form.submit()" class="text-sm px-3 py-1 border border-slate-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700">
                                    <option value="demande" {{ $feedback->categorie == 'demande' ? 'selected' : '' }}>Demande</option>
                                    <option value="remerciement" {{ $feedback->categorie == 'remerciement' ? 'selected' : '' }}>Remerciement</option>
                                    <option value="erreur" {{ $feedback->categorie == 'erreur' ? 'selected' : '' }}>Erreur</option>
                                    <option value="conseil" {{ $feedback->categorie == 'conseil' ? 'selected' : '' }}>Conseil</option>
                                    <option value="autre" {{ $feedback->categorie == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </form>
                        @endif
                    @endauth
                </div>
                <div class="prose dark:prose-invert max-w-none mb-4">
                    {!! nl2br(e($feedback->description)) !!}
                </div>
                <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <span>{{ $feedback->user->name }}</span>
                    <span>{{ $feedback->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Commentaires -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
            Commentaires ({{ $feedback->comments->count() }})
        </h2>

        @auth
            <form method="POST" action="{{ route('feedback.comment', $feedback) }}" class="mb-6">
                @csrf
                <textarea 
                    name="contenu" 
                    rows="3"
                    required
                    placeholder="Ajouter un commentaire..."
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white mb-2"
                ></textarea>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Commenter
                </button>
            </form>
        @else
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                <a href="{{ route('login') }}" class="text-green-600 dark:text-green-400 hover:underline">Connectez-vous</a> pour commenter.
            </p>
        @endauth

        <div class="space-y-4">
            @foreach($feedback->comments as $comment)
                <div class="border-l-2 border-slate-200 dark:border-slate-700 pl-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $comment->user->name }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-700 dark:text-slate-300">{{ $comment->contenu }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
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
