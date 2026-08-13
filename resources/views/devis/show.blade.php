@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('devis.index', $entreprise->slug) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour aux devis
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Informations du devis --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $devisItem->numero_devis ? 'Devis '.$devisItem->numero_devis : 'Devis #'.$devisItem->id }}</h1>
            @php
                $statutColors = [
                    'en_attente' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'propose' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'accepte' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'refuse' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                ];
            @endphp
            <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $statutColors[$devisItem->statut] ?? 'bg-slate-100 text-slate-700' }}">
                {{ $devisItem->statut_libelle }}
            </span>
            @if($devisItem->snapshot || $devisItem->numero_devis)
                <a href="{{ route('devis.pdf', [$entreprise->slug, $devisItem->id]) }}"
                   class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    PDF
                </a>
            @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Client</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $devisItem->nom_client_complet ?? $devisItem->user?->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Service</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $devisItem->typeService->nom }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Email</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $devisItem->email_client ?? $devisItem->user?->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Téléphone</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $devisItem->telephone_client ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-slate-500 dark:text-slate-400 mb-1">Date de demande</p>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $devisItem->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @if($devisItem->reservation)
                <div>
                    <p class="text-slate-500 dark:text-slate-400 mb-1">Réservation liée</p>
                    <a href="{{ route('reservations.show', [$entreprise->slug, $devisItem->reservation->id]) }}" 
                       class="font-semibold text-green-600 dark:text-green-400 hover:underline">
                        Voir la réservation #{{ $devisItem->reservation->id }}
                    </a>
                </div>
            @endif
        </div>

        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 mb-6">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description du besoin</h3>
            <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{{ $devisItem->description_besoin }}</p>
        </div>

        @if($devisItem->montant_propose)
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 mb-6">
                <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-3">Votre proposition</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-blue-600 dark:text-blue-400 mb-1">Montant</p>
                        <p class="font-bold text-blue-800 dark:text-blue-200">{{ number_format($devisItem->montant_propose, 2, ',', ' ') }} &euro;</p>
                    </div>
                    <div>
                        <p class="text-blue-600 dark:text-blue-400 mb-1">Date proposée</p>
                        <p class="font-bold text-blue-800 dark:text-blue-200">{{ $devisItem->date_proposee ? $devisItem->date_proposee->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-blue-600 dark:text-blue-400 mb-1">Durée estimée</p>
                        <p class="font-bold text-blue-800 dark:text-blue-200">{{ $devisItem->duree_proposee_minutes ? $devisItem->duree_proposee_minutes . ' min' : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-blue-600 dark:text-blue-400 mb-1">Type</p>
                        <p class="font-bold text-blue-800 dark:text-blue-200">{{ $devisItem->type_structure_propose ?? 'Ponctuel' }}</p>
                    </div>
                </div>
                @if($devisItem->notes_prestataire)
                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                        <p class="text-sm text-blue-600 dark:text-blue-400"><strong>Notes :</strong> {{ $devisItem->notes_prestataire }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Formulaire de proposition (si en attente) --}}
    @if($devisItem->estEnAttente())
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Faire une proposition</h2>

            <form action="{{ route('devis.proposer', [$entreprise->slug, $devisItem->id]) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Montant (&euro;) *</label>
                            <input type="number" name="montant_propose" required min="0" step="0.01" value="{{ old('montant_propose', $devisItem->typeService->prix) }}"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Durée (min) *</label>
                            <input type="number" name="duree_proposee_minutes" required min="1" value="{{ old('duree_proposee_minutes', $devisItem->typeService->duree_minutes) }}"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Date proposée *</label>
                            <input type="date" name="date_proposee" required min="{{ date('Y-m-d') }}" value="{{ old('date_proposee') }}"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Heure proposée *</label>
                            <input type="time" name="heure_proposee" required value="{{ old('heure_proposee') }}"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Type de prestation</label>
                        <select name="type_structure_propose"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="ponctuel">Ponctuel</option>
                            <option value="multi_jours">Multi-jours</option>
                            <option value="multi_rendez_vous">Multi rendez-vous</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes pour le client</label>
                        <textarea name="notes_prestataire" rows="3" placeholder="Détails de votre proposition..."
                                  class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none">{{ old('notes_prestataire') }}</textarea>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 text-white font-bold rounded-xl bg-green-600 hover:bg-green-700 transition shadow-lg {{ $entreprise->profilFacturationComplet() ? '' : 'opacity-60' }}">
                        Envoyer la proposition au client
                    </button>
                    @unless($entreprise->profilFacturationComplet())
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            Complétez le <a class="underline" href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres']) }}">profil de facturation</a> (SIRET, adresse) avant d'envoyer.
                        </p>
                    @endunless
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
