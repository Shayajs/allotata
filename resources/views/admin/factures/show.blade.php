@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Facture {{ $facture->numero_facture }}
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Détails de la facture
            </p>
        </div>
        <a href="{{ route('admin.factures.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">
            ← Retour
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 space-y-6">
        <!-- Informations générales -->
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Type</h3>
                <span class="text-sm px-2 py-1 rounded-full
                    @if($facture->estAbonnementPlateforme()) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                    @endif">
                    {{ $facture->libelleOrigine() }}
                </span>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Statut</h3>
                <span class="text-sm px-2 py-1 rounded-full
                    @if($facture->statut === 'payee') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                    @elseif($facture->statut === 'annulee') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                    @elseif($facture->statut === 'brouillon') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                    @endif">
                    {{ ucfirst($facture->statut) }}
                </span>
            </div>
        </div>

        <!-- Entreprise et Client -->
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Facturé par</h3>
                @if($facture->estAbonnementPlateforme())
                    <p class="text-sm text-slate-900 dark:text-white">Lucas Espinar, EI — Allotata</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">SIRET 994 535 904 00019</p>
                @elseif($facture->entreprise)
                    <p class="text-sm text-slate-900 dark:text-white">{{ $facture->entreprise->nom }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ $facture->entreprise->email }}</p>
                @endif
            </div>
            @if($facture->user)
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Facturé à</h3>
                    <p class="text-sm text-slate-900 dark:text-white">{{ $facture->user->name }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ $facture->user->email }}</p>
                </div>
            @endif
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Date de facture</h3>
                <p class="text-sm text-slate-900 dark:text-white">{{ $facture->date_facture->format('d/m/Y') }}</p>
            </div>
            @if($facture->date_echeance)
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Date d'échéance</h3>
                    <p class="text-sm text-slate-900 dark:text-white">{{ $facture->date_echeance->format('d/m/Y') }}</p>
                </div>
            @endif
        </div>

        <!-- Montants -->
        <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 uppercase">Montants</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Montant HT</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</span>
                </div>
                @if($facture->taux_tva > 0)
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">TVA ({{ $facture->taux_tva }}%)</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ number_format($facture->montant_tva, 2, ',', ' ') }} €</span>
                    </div>
                @endif
                <div class="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-base font-semibold text-slate-900 dark:text-white">Total TTC</span>
                    <span class="text-base font-bold text-slate-900 dark:text-white">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($facture->notes)
            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Notes</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">{{ $facture->notes }}</p>
            </div>
        @endif

        <!-- Abonnement lié -->
        @if($facture->entrepriseSubscription)
            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase">Abonnement lié</h3>
                <p class="text-sm text-slate-900 dark:text-white">
                    {{ $facture->entrepriseSubscription->type === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes' }}
                    ({{ $facture->entrepriseSubscription->est_manuel ? 'Manuel' : 'Stripe' }})
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
