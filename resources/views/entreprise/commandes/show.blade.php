@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'commandes']) }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour aux commandes
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Commande #{{ $commande->id }}</h1>
            <span class="px-4 py-2 text-sm font-semibold rounded-full 
                {{ $commande->statut === 'en_attente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                {{ $commande->statut === 'confirmee' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                {{ $commande->statut === 'terminee' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                {{ $commande->statut === 'livree' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                {{ $commande->statut === 'annulee' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
            ">
                {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
            </span>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Informations produit -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Produit</h2>
                <div class="space-y-2 text-sm">
                    <p><strong>Nom :</strong> {{ $commande->produit->nom }}</p>
                    <p><strong>Quantité :</strong> {{ $commande->quantite }}x</p>
                    <p><strong>Prix unitaire :</strong> {{ number_format($commande->prix_unitaire, 2, ',', ' ') }} €</p>
                    <p><strong>Prix total :</strong> <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ $commande->prix_total_formate }}</span></p>
                </div>
            </div>

            <!-- Informations client -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Client</h2>
                <div class="space-y-2 text-sm">
                    <p><strong>Nom :</strong> {{ $commande->nom_client_complet ?? 'Non renseigné' }}</p>
                    <p><strong>Email :</strong> {{ $commande->email_client_complet ?? 'Non renseigné' }}</p>
                    <p><strong>Téléphone :</strong> 
                        @if($commande->telephone_client)
                            {{ $commande->telephone_client }}
                        @elseif($commande->telephone_client_non_inscrit)
                            {{ $commande->telephone_client_non_inscrit }}
                        @else
                            Non renseigné
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Mode de livraison -->
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Mode de réception</h2>
            <div class="text-sm space-y-2">
                <p><strong>Mode :</strong> 
                    @if($commande->mode_livraison === 'livraison')
                        🚚 Livraison
                    @elseif($commande->mode_livraison === 'vente_sur_place')
                        🏪 Vente sur place
                    @else
                        💬 À discuter
                    @endif
                </p>
                @if($commande->mode_livraison === 'livraison' && $commande->adresse_livraison)
                    <p><strong>Adresse de livraison :</strong></p>
                    <p class="ml-4">{{ $commande->adresse_livraison }}<br>
                    {{ $commande->code_postal_livraison }} {{ $commande->ville_livraison }}</p>
                @endif
                @if($commande->date_livraison_souhaitee)
                    <p><strong>Date souhaitée :</strong> {{ $commande->date_livraison_souhaitee->format('d/m/Y') }}</p>
                @endif
                @if($commande->date_livraison_prevue)
                    <p><strong>Date prévue :</strong> {{ $commande->date_livraison_prevue->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>

        @if($commande->notes)
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Notes</h2>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $commande->notes }}</p>
            </div>
        @endif

        <!-- Actions -->
        @if($commande->statut === 'en_attente')
            <div class="flex gap-4">
                <form action="{{ route('commandes.accept', [$entreprise->slug, $commande->id]) }}" method="POST" class="flex-1">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de livraison prévue (optionnel)</label>
                        <input 
                            type="date" 
                            name="date_livraison_prevue" 
                            min="{{ date('Y-m-d') }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                        <textarea 
                            name="notes_gerant" 
                            rows="3"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Notes pour le client..."
                        ></textarea>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                        ✓ Accepter la commande
                    </button>
                </form>
                <form action="{{ route('commandes.reject', [$entreprise->slug, $commande->id]) }}" method="POST" class="flex-1">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Raison du refus (optionnel)</label>
                        <textarea 
                            name="raison_refus" 
                            rows="3"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Expliquez pourquoi vous refusez cette commande..."
                        ></textarea>
                    </div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                        ✗ Refuser la commande
                    </button>
                </form>
            </div>
        @elseif($commande->statut === 'confirmee' && !$commande->est_paye)
            <form action="{{ route('commandes.marquer-payee', [$entreprise->slug, $commande->id]) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date de paiement (optionnel)</label>
                    <input 
                        type="date" 
                        name="date_paiement" 
                        value="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                    💰 Marquer comme payé
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
