@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'reservations']) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour au dashboard
        </a>
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Récurrences</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $entreprise->nom }} - Séries de rendez-vous récurrents</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($recurrences->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">Aucune récurrence</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Les clients pourront créer des réservations récurrentes depuis votre page publique.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($recurrences as $recurrence)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->typeService->nom }}</h3>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $recurrence->est_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $recurrence->est_active ? 'Active' : 'Annulée' }}
                                </span>
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 space-y-1">
                                <p><strong>Client :</strong> {{ $recurrence->nom_client_complet ?? 'N/A' }}</p>
                                <p><strong>Fréquence :</strong> {{ $recurrence->frequence_libelle }}</p>
                                <p><strong>Période :</strong> {{ $recurrence->date_debut->format('d/m/Y') }} - {{ $recurrence->date_fin->format('d/m/Y') }}</p>
                                <p><strong>Heure :</strong> {{ $recurrence->heure }}</p>
                                <p><strong>Prix/séance :</strong> {{ number_format($recurrence->prix_par_occurrence, 2, ',', ' ') }} &euro;</p>
                                <p><strong>Occurrences :</strong> {{ $recurrence->nombre_occurrences }} ({{ $recurrence->occurrences_futures }} futures)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('recurrences.show', [$entreprise->slug, $recurrence->id]) }}" 
                               class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition">
                                Détails
                            </a>
                            @if($recurrence->est_active)
                                <form action="{{ route('recurrences.destroy', [$entreprise->slug, $recurrence->id]) }}" method="POST" 
                                      onsubmit="return confirm('Annuler cette récurrence et toutes ses occurrences futures ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                        Annuler
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $recurrences->links() }}
        </div>
    @endif
</div>
@endsection
