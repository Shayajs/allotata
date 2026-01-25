@extends('layouts.user')

@section('title', 'Forum')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                Forum de discussion
            </h1>
            <p class="text-slate-600 dark:text-slate-400">Échangez et partagez vos idées</p>
        </div>
        @auth
            <a href="{{ route('forum.create') }}" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                + Nouveau post
            </a>
        @endauth
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Section Nouveautés -->
    @if($nouveautes->count() > 0)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Nouveautés
                </h2>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('forum.nouveaute.create') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                            + Ajouter une nouveauté
                        </a>
                    @endif
                @endauth
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($nouveautes as $post)
                    <a href="{{ route('forum.post.show', $post) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($post->est_epingle)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    @endif
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $post->titre }}</h3>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($post->contenu), 150) }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $post->user->name }}</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                    <span>{{ $post->vues }} vues</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section Demandes -->
    @if($demandes->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Demandes
            </h2>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($demandes as $post)
                    <a href="{{ route('forum.post.show', $post) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($post->est_epingle)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    @endif
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $post->titre }}</h3>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($post->contenu), 150) }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $post->user->name }}</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                    <span>{{ $post->vues }} vues</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section Autres -->
    @if($autres->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
                Autres discussions
            </h2>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($autres as $post)
                    <a href="{{ route('forum.post.show', $post) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($post->est_epingle)
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    @endif
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $post->titre }}</h3>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit(strip_tags($post->contenu), 150) }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ $post->user->name }}</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                    <span>{{ $post->vues }} vues</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($nouveautes->count() === 0 && $demandes->count() === 0 && $autres->count() === 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
            <p class="text-slate-600 dark:text-slate-400">Aucun post pour le moment.</p>
            @auth
                <a href="{{ route('forum.create') }}" class="mt-4 inline-block px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                    Créer le premier post
                </a>
            @endauth
        </div>
    @endif
</div>
@endsection
