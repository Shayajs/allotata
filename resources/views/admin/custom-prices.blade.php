@extends('admin.layout')

@section('title', 'Prix personnalisés')
@section('header', 'Prix personnalisés')
@section('subheader', 'Surcharge locale des tarifs par utilisateur ou entreprise — aucun appel Stripe')

@section('content')
<div class="max-w-7xl mx-auto">

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-sm text-red-800 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ════════════════ Liste existante ════════════════ --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Prix personnalisés existants</h2>

        @if($customPrices->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden table-responsive-to-cards">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cible</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Montant</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Notes</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($customPrices as $cp)
                            <tr>
                                <td class="px-5 py-4 whitespace-nowrap" data-label="Cible">
                                    @if($cp->user)
                                        <div class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ $cp->user->name }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 pl-6">{{ $cp->user->email }}</div>
                                    @elseif($cp->entreprise)
                                        <div class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            {{ $cp->entreprise->nom }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Cible supprimée</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap" data-label="Type">
                                    <span class="px-2.5 py-1 text-xs rounded-full font-medium
                                        @if($cp->subscription_type === 'default') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400
                                        @elseif($cp->subscription_type === 'site_web') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                                        @else bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 @endif">
                                        @if($cp->subscription_type === 'default') Premium
                                        @elseif($cp->subscription_type === 'site_web') Site Web
                                        @else Multi-Personnes @endif
                                    </span>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap" data-label="Montant">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ number_format($cp->amount, 2, ',', ' ') }} {{ strtoupper($cp->currency) }}<span class="font-normal text-slate-400">/mois</span>
                                    </div>
                                    @if($cp->stripe_price_id)
                                        <code class="text-[10px] text-slate-400 mt-0.5 block">{{ $cp->stripe_price_id }}</code>
                                    @endif
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap" data-label="Statut">
                                    @if($cp->isValid())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactif
                                        </span>
                                    @endif
                                    @if($cp->expires_at)
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                                            Expire {{ $cp->expires_at->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-4" data-label="Notes">
                                    <div class="text-xs text-slate-500 dark:text-slate-400 max-w-48 truncate" title="{{ $cp->notes }}">
                                        {{ $cp->notes ?: '—' }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap" data-label="Actions">
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.custom-prices.toggle', $cp) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="ui-btn-simple px-3 py-1.5 text-xs font-medium rounded-lg transition
                                                {{ $cp->is_active
                                                    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-200'
                                                    : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200' }}">
                                                {{ $cp->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.custom-prices.delete', $cp) }}" method="POST" onsubmit="return confirm('Supprimer ce prix personnalisé ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-btn-simple px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 transition">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $customPrices->links() }}</div>
        @else
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-700 p-8 text-center">
                <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <p class="text-slate-500 dark:text-slate-400 text-sm">Aucun prix personnalisé pour le moment.</p>
            </div>
        @endif
    </div>

    {{-- ════════════════ Formulaire de création ════════════════ --}}
    <div x-data="{ targetType: '', subType: '' }" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Nouveau prix personnalisé</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Le montant est géré localement. Aucun produit/prix n'est créé sur Stripe.</p>

        <form action="{{ route('admin.custom-prices.create') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Cible --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cible *</label>
                    <select name="target_type" x-model="targetType" required
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <option value="">Choisir...</option>
                        <option value="user">Utilisateur</option>
                        <option value="entreprise">Entreprise</option>
                    </select>
                </div>

                {{-- Utilisateur --}}
                <div x-show="targetType === 'user'" x-transition>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Utilisateur *</label>
                    <select name="user_id" :required="targetType === 'user'"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <option value="">Sélectionner...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Entreprise --}}
                <div x-show="targetType === 'entreprise'" x-transition>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Entreprise *</label>
                    <select name="entreprise_id" :required="targetType === 'entreprise'"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <option value="">Sélectionner...</option>
                        @foreach($entreprises as $entreprise)
                            <option value="{{ $entreprise->id }}">{{ $entreprise->nom }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type d'abonnement --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Type d'abonnement *</label>
                    <select name="subscription_type" x-model="subType" required
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <option value="">Choisir...</option>
                        <option value="default">Premium (Cashier)</option>
                        <option value="site_web">Site Web Vitrine</option>
                        <option value="multi_personnes">Gestion Multi-Personnes</option>
                    </select>
                </div>

                {{-- Montant --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Montant / mois *</label>
                    <div class="relative">
                        <input type="number" name="amount" step="0.01" min="0.01" required placeholder="10.00"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm pr-16">
                        <select name="currency" class="absolute right-1 top-1 bottom-1 w-20 border-0 bg-slate-100 dark:bg-slate-600 rounded-md text-xs font-medium text-slate-700 dark:text-slate-300 focus:ring-0">
                            <option value="eur" selected>EUR</option>
                            <option value="usd">USD</option>
                            <option value="gbp">GBP</option>
                        </select>
                    </div>
                </div>

                {{-- Stripe Price ID (optionnel, seulement pour Cashier) --}}
                <div x-show="subType === 'default'" x-transition>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Stripe Price ID
                        <span class="text-slate-400 font-normal">(optionnel)</span>
                    </label>
                    <input type="text" name="stripe_price_id" placeholder="price_1Abc..."
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm font-mono">
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        Uniquement pour les abonnements Cashier. Créez le prix dans le
                        <a href="https://dashboard.stripe.com/prices" target="_blank" class="underline text-green-600 dark:text-green-400">Dashboard Stripe</a>
                        et collez l'ID ici. Sinon le prix par défaut du <code>.env</code> est utilisé.
                    </p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes internes</label>
                    <input type="text" name="notes" placeholder="Ristourne 50%, accord commercial..."
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                </div>

                {{-- Expiration --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Expiration
                        <span class="text-slate-400 font-normal">(optionnel)</span>
                    </label>
                    <input type="date" name="expires_at" min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        Passé cette date, le tarif standard reprend.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit"
                    class="ui-btn-simple px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                    Créer le prix personnalisé
                </button>
            </div>
        </form>
    </div>

    {{-- ════════════════ Aide ════════════════ --}}
    <div class="mt-6 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Fonctionnement
        </h3>
        <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
            <li><strong>Site Web / Multi-Personnes :</strong> Le montant local est utilisé directement par le calcul des échéances. Aucun objet Stripe n'est créé.</li>
            <li><strong>Premium (Cashier) :</strong> Le montant local surcharge le calcul. Si un <code>stripe_price_id</code> est fourni, il remplace le prix par défaut du <code>.env</code> lors du checkout Cashier.</li>
            <li><strong>Priorité :</strong> Prix personnalisé actif > Prix par défaut (tarifs / .env).</li>
            <li><strong>Doublon interdit :</strong> Un seul prix actif par cible + type d'abonnement.</li>
        </ul>
    </div>
</div>
@endsection
