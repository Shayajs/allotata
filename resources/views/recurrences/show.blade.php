@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('recurrences.index', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour aux récurrences
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- En-tête --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $recurrence->typeService->nom }}</h1>
                <span class="mt-1 inline-block px-3 py-1 text-sm font-medium rounded-full {{ $recurrence->est_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $recurrence->est_active ? 'Active' : 'Annulée' }}
                </span>
            </div>
            @if($recurrence->est_active)
                <form action="{{ route('recurrences.destroy', [$entreprise->slug, $recurrence->id]) }}" method="POST"
                      onsubmit="return confirm('Annuler cette récurrence et toutes ses occurrences futures ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        Annuler la récurrence
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Client</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->nom_client_complet ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Fréquence</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->frequence_libelle }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Période</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->date_debut->format('d/m/Y') }} - {{ $recurrence->date_fin->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Heure</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->heure }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Prix / séance</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ number_format($recurrence->prix_par_occurrence, 2, ',', ' ') }} &euro;</p>
            </div>
            @if($recurrence->membre)
                <div>
                    <p class="text-slate-500 dark:text-slate-400 mb-1">Membre assigné</p>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->membre->user->name ?? 'N/A' }}</p>
                </div>
            @endif
            @if($recurrence->lieu)
                <div>
                    <p class="text-slate-500 dark:text-slate-400 mb-1">Lieu</p>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ $recurrence->lieu }}</p>
                </div>
            @endif
        </div>

        @if($recurrence->notes)
            <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-sm text-slate-600 dark:text-slate-300">
                <strong>Notes :</strong> {{ $recurrence->notes }}
            </div>
        @endif
    </div>

    {{-- Liste des occurrences --}}
    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Occurrences ({{ $recurrence->reservations->count() }})</h2>

    <div class="space-y-3">
        @foreach($recurrence->reservations as $reservation)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="text-center min-w-[60px]">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('d') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 uppercase">{{ $reservation->date_reservation->translatedFormat('M Y') }}</div>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $reservation->date_reservation->format('H:i') }} - {{ $reservation->duree_minutes }} min</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($reservation->prix, 2, ',', ' ') }} &euro;</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @php
                        $statutColors = [
                            'en_attente' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'confirmee' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'annulee' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'terminee' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                        ];
                        $statutLabels = [
                            'en_attente' => 'En attente',
                            'confirmee' => 'Confirmée',
                            'annulee' => 'Annulée',
                            'terminee' => 'Terminée',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statutColors[$reservation->statut] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $statutLabels[$reservation->statut] ?? $reservation->statut }}
                    </span>
                    <a href="{{ route('reservations.show', [$entreprise->slug, $reservation->id]) }}" 
                       class="text-green-600 dark:text-green-400 hover:underline text-xs font-medium">
                        Voir
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
