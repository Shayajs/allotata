@extends('layouts.user')

@section('title', $post->titre)

@section('content')
<div class="max-w-7xl 2xl:max-w-[1600px] mx-auto">
    <div class="mb-6">
        <a href="{{ route('forum.index') }}" class="text-green-600 dark:text-green-400 hover:underline flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Retour au forum
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    @if($post->est_epingle)
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                        </svg>
                    @endif
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $post->titre }}</h1>
                </div>
                <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400 mb-4">
                    <span>{{ $post->user->name }}</span>
                    <span>{{ $post->created_at->diffForHumans() }}</span>
                    <span>{{ $post->vues }} vues</span>
                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">{{ $post->category->nom }}</span>
                </div>
            </div>
            @if(auth()->check() && (auth()->id() === $post->user_id || auth()->user()->isAdmin()))
                <div class="flex gap-2">
                    <a href="{{ route('forum.post.edit', $post) }}" class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50">
                        Modifier
                    </a>
                    <form method="POST" action="{{ route('forum.post.destroy', $post) }}" onsubmit="return confirm('Êtes-vous sûr ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50">
                            Supprimer
                        </button>
                    </form>
                </div>
            @endif
        </div>
        <div class="prose dark:prose-invert max-w-none">
            {!! nl2br(e($post->contenu)) !!}
        </div>
    </div>

    <!-- Commentaires -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
            Commentaires ({{ $post->comments->count() }})
        </h2>

        @auth
            <form method="POST" action="{{ route('forum.comment.store', $post) }}" class="mb-6">
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
            @foreach($post->comments->where('parent_id', null) as $comment)
                @include('forum.comment', ['comment' => $comment, 'level' => 0])
            @endforeach
        </div>
    </div>
</div>
@endsection
