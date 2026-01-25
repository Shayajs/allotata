@extends('layouts.user')

@section('title', 'Dashboard Feedback')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Dashboard Feedback
            </h1>
            <p class="text-slate-600 dark:text-slate-400">Vue d'ensemble des feedbacks</p>
        </div>
        <a href="{{ route('feedback.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition">
            Voir tous les feedbacks
        </a>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Total</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['poste'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Postés</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['traitement_en_cours'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">En cours</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['termine'] }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Terminés</div>
        </div>
    </div>

    <!-- Meilleurs feedbacks non résolus -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Meilleurs feedbacks non résolus</h2>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($meilleursNonResolus as $feedback)
                <a href="{{ route('feedback.show', $feedback) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded font-semibold">
                                    {{ $feedback->votes_count }} votes
                                </span>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $feedback->titre }}</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit($feedback->description, 150) }}</p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ $feedback->user->name }}</span>
                                <span>{{ $feedback->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-slate-600 dark:text-slate-400">
                    Aucun feedback non résolu pour le moment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Meilleurs feedbacks de tous les temps -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Meilleurs feedbacks de tous les temps</h2>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($meilleursTousTemps as $feedback)
                <a href="{{ route('feedback.show', $feedback) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded font-semibold">
                                    {{ $feedback->votes_count }} votes
                                </span>
                                <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 rounded">{{ ucfirst(str_replace('_', ' ', $feedback->statut)) }}</span>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $feedback->titre }}</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit($feedback->description, 150) }}</p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ $feedback->user->name }}</span>
                                <span>{{ $feedback->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-slate-600 dark:text-slate-400">
                    Aucun feedback pour le moment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Feedbacks récemment terminés -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Récemment terminés</h2>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 divide-y divide-slate-200 dark:divide-slate-700">
            @forelse($recemmentTermines as $feedback)
                <a href="{{ route('feedback.show', $feedback) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">
                                    Terminé
                                </span>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $feedback->titre }}</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ Str::limit($feedback->description, 150) }}</p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ $feedback->user->name }}</span>
                                <span>Terminé {{ $feedback->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-slate-600 dark:text-slate-400">
                    Aucun feedback terminé récemment.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
