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
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Demandes de devis</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $entreprise->nom }} - Gérez les demandes de vos clients</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($devis->isEmpty())
        <div class="text-center py-16 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">Aucune demande de devis</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Les clients pourront demander un devis depuis votre page publique pour les services "Sur devis".</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($devis as $d)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-semibold text-slate-900 dark:text-white">Devis #{{ $d->id }} - {{ $d->typeService->nom }}</h3>
                                @php
                                    $statutColors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'propose' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'accepte' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'refuse' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statutColors[$d->statut] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $d->statut_libelle }}
                                </span>
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 space-y-1">
                                <p><strong>Client :</strong> {{ $d->nom_client_complet ?? 'N/A' }}</p>
                                <p><strong>Besoin :</strong> {{ Str::limit($d->description_besoin, 120) }}</p>
                                <p><strong>Date :</strong> {{ $d->created_at->format('d/m/Y H:i') }}</p>
                                @if($d->montant_propose)
                                    <p><strong>Montant proposé :</strong> {{ number_format($d->montant_propose, 2, ',', ' ') }} &euro;</p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('devis.show', [$entreprise->slug, $d->id]) }}" 
                           class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition">
                            {{ $d->estEnAttente() ? 'Proposer' : 'Détails' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $devis->links() }}
        </div>
    @endif
</div>
@endsection
