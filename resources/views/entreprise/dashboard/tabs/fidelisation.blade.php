<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Fidélisation</h2>
    </div>

    <!-- Sous-onglets -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
        <div class="flex space-x-1 overflow-x-auto">
            <button
                id="fidelisation-subtab-clients-reguliers"
                onclick="showFidelisationSubTab('clients-reguliers')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-500 dark:border-green-400 whitespace-nowrap"
            >
                Clients réguliers
            </button>
            <button
                id="fidelisation-subtab-clients-risque"
                onclick="showFidelisationSubTab('clients-risque')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Clients à risque
            </button>
            <button
                id="fidelisation-subtab-statistiques"
                onclick="showFidelisationSubTab('statistiques')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Statistiques
            </button>
            <button
                id="fidelisation-subtab-actions"
                onclick="showFidelisationSubTab('actions')"
                class="fidelisation-subtab-btn px-4 sm:px-6 py-3 text-sm font-medium text-center border-b-2 transition-colors text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 border-transparent whitespace-nowrap"
            >
                Actions
            </button>
        </div>
    </div>

    <!-- Contenu sous-onglet Clients réguliers -->
    <div id="fidelisation-subtab-content-clients-reguliers" class="fidelisation-subtab-content">
        <!-- Affichage de la norme quartier -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Norme quartier</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Basée sur l'analyse des entreprises du même quartier, la norme de régularité est de 
                        <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($fidelisationData['norme_quartier'], 2, ',', ' ') }} visites/mois</span>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        Les clients avec une fréquence supérieure ou égale à cette norme sont considérés comme réguliers.
                    </p>
                </div>
            </div>
        </div>

        <!-- Barre de recherche et filtres -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <form method="GET" action="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="space-y-4">
                <input type="hidden" name="tab" value="fidelisation">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Rechercher
                        </label>
                        <input 
                            type="text" 
                            name="fidelisation_search" 
                            value="{{ request('fidelisation_search') }}"
                            placeholder="Nom, email..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Statut
                        </label>
                        <select 
                            name="fidelisation_statut" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="regulier" {{ request('fidelisation_statut') === 'regulier' ? 'selected' : '' }}>Régulier</option>
                            <option value="occasionnel" {{ request('fidelisation_statut') === 'occasionnel' ? 'selected' : '' }}>Occasionnel</option>
                            <option value="nouveau" {{ request('fidelisation_statut') === 'nouveau' ? 'selected' : '' }}>Nouveau</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Trier par
                        </label>
                        <select 
                            name="fidelisation_sort" 
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="plus_present" {{ request('fidelisation_sort', 'plus_present') === 'plus_present' ? 'selected' : '' }}>Plus présent</option>
                            <option value="moins_present" {{ request('fidelisation_sort') === 'moins_present' ? 'selected' : '' }}>Moins présent</option>
                            <option value="frequence_desc" {{ request('fidelisation_sort') === 'frequence_desc' ? 'selected' : '' }}>Fréquence décroissante</option>
                            <option value="frequence_asc" {{ request('fidelisation_sort') === 'frequence_asc' ? 'selected' : '' }}>Fréquence croissante</option>
                            <option value="derniere_visite_desc" {{ request('fidelisation_sort') === 'derniere_visite_desc' ? 'selected' : '' }}>Dernière visite récente</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                            🔍 Rechercher
                        </button>
                    </div>
                </div>
                @if(request()->hasAny(['fidelisation_search', 'fidelisation_statut', 'fidelisation_sort']))
                    <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'fidelisation']) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400">
                        Réinitialiser les filtres
                    </a>
                @endif
            </form>
        </div>

        <!-- Tableau des clients -->
        @if(count($fidelisationData['clients']) > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Réservations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fréquence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dernière visite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($fidelisationData['clients'] as $clientData)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if($clientData['client'])
                                                <x-avatar :user="$clientData['client']" size="md" class="flex-shrink-0" />
                                                <div>
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                        <x-user-name :user="$clientData['client']" />
                                                    </div>
                                                    <div class="text-sm text-slate-500 dark:text-slate-400">
                                                        {{ $clientData['client']->email }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 font-semibold flex-shrink-0">
                                                    ?
                                                </div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                    Client inconnu
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $clientData['nb_reservations'] }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            réservation{{ $clientData['nb_reservations'] > 1 ? 's' : '' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ number_format($clientData['frequence_moyenne'], 2, ',', ' ') }} /mois
                                        </div>
                                        @if($clientData['frequence_moyenne'] >= $fidelisationData['norme_quartier'])
                                            <div class="text-xs text-green-600 dark:text-green-400">
                                                ✓ Au-dessus de la norme
                                            </div>
                                        @elseif($clientData['frequence_moyenne'] >= ($fidelisationData['norme_quartier'] * 0.5))
                                            <div class="text-xs text-yellow-600 dark:text-yellow-400">
                                                ≈ Proche de la norme
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                En dessous de la norme
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($clientData['derniere_visite'])
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                {{ $clientData['derniere_visite']->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $clientData['derniere_visite']->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-sm text-slate-400 dark:text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($clientData['statut'] === 'regulier')
                                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                                                Régulier
                                            </span>
                                        @elseif($clientData['statut'] === 'occasionnel')
                                            <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full">
                                                Occasionnel
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                                                Nouveau
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun client trouvé</h3>
                <p class="text-slate-600 dark:text-slate-400">
                    @if(request()->hasAny(['fidelisation_search', 'fidelisation_statut']))
                        Aucun client ne correspond à vos critères de recherche.
                    @else
                        Aucun client n'a encore effectué de réservation dans votre entreprise.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- Contenu sous-onglet Clients à risque -->
    <div id="fidelisation-subtab-content-clients-risque" class="fidelisation-subtab-content hidden">
        <!-- Alerte -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Clients à risque</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Ces clients n'ont pas réservé depuis un certain temps ou voient leur fréquence de visite diminuer. 
                        Pensez à leur envoyer un rappel ou une offre spéciale pour les fidéliser.
                    </p>
                </div>
            </div>
        </div>

        <!-- Filtre jours sans réservation -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <form method="GET" action="{{ route('entreprise.dashboard', $entreprise->slug) }}" class="flex items-end gap-4">
                <input type="hidden" name="tab" value="fidelisation">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Considérer à risque si pas de réservation depuis (jours)
                    </label>
                    <input 
                        type="number" 
                        name="fidelisation_jours_risque" 
                        value="{{ request('fidelisation_jours_risque', 90) }}"
                        min="30"
                        max="365"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                    🔍 Filtrer
                </button>
            </form>
        </div>

        <!-- Tableau des clients à risque -->
        @if(count($fidelisationClientsARisque['clients'] ?? []) > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Historique</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dernière visite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jours sans visite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Raison</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($fidelisationClientsARisque['clients'] ?? [] as $clientData)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if($clientData['client'])
                                                <x-avatar :user="$clientData['client']" size="md" class="flex-shrink-0" />
                                                <div>
                                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                        <x-user-name :user="$clientData['client']" />
                                                    </div>
                                                    <div class="text-sm text-slate-500 dark:text-slate-400">
                                                        {{ $clientData['client']->email }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ $clientData['nb_reservations'] }} réservation{{ $clientData['nb_reservations'] > 1 ? 's' : '' }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Fréquence : {{ number_format($clientData['frequence_moyenne'], 2, ',', ' ') }}/mois
                                        </div>
                                        @if(isset($clientData['ca_total']))
                                            <div class="text-xs font-semibold text-green-600 dark:text-green-400 mt-1">
                                                CA total : {{ number_format($clientData['ca_total'], 2, ',', ' ') }}€
                                            </div>
                                        @endif
                                        @if($clientData['premiere_visite'])
                                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                                Client depuis {{ $clientData['premiere_visite']->diffForHumans() }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($clientData['derniere_visite'])
                                            <div class="text-sm text-slate-900 dark:text-white">
                                                {{ $clientData['derniere_visite']->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $clientData['derniere_visite']->format('H:i') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ $clientData['jours_sans_visite'] }} jours
                                        </div>
                                        @if(isset($clientData['score_risque']))
                                            @if($clientData['score_risque'] >= 70)
                                                <span class="text-xs text-red-600 dark:text-red-400 font-semibold">⚠️ Risque très élevé ({{ $clientData['score_risque'] }}%)</span>
                                            @elseif($clientData['score_risque'] >= 50)
                                                <span class="text-xs text-orange-600 dark:text-orange-400">⚠️ Risque élevé ({{ $clientData['score_risque'] }}%)</span>
                                            @else
                                                <span class="text-xs text-yellow-600 dark:text-yellow-400">⚠️ Risque modéré ({{ $clientData['score_risque'] }}%)</span>
                                            @endif
                                        @elseif($clientData['jours_sans_visite'] > 180)
                                            <span class="text-xs text-red-600 dark:text-red-400 font-semibold">⚠️ Très élevé</span>
                                        @elseif($clientData['jours_sans_visite'] > 120)
                                            <span class="text-xs text-orange-600 dark:text-orange-400">⚠️ Élevé</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ $clientData['raison'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <button 
                                                onclick="envoyerRappel({{ $clientData['client']->id ?? 0 }}, '{{ $clientData['client']->email ?? '' }}')"
                                                class="px-3 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition"
                                                title="Envoyer un rappel par email"
                                            >
                                                📧 Email
                                            </button>
                                            <button 
                                                onclick="ouvrirMessagerie({{ $clientData['client']->id ?? 0 }})"
                                                class="px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition"
                                                title="Ouvrir la messagerie"
                                            >
                                                💬 Message
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-green-400 dark:text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun client à risque</h3>
                <p class="text-slate-600 dark:text-slate-400">
                    Tous vos clients sont actifs ! Aucun client ne nécessite d'attention particulière pour le moment.
                </p>
            </div>
        @endif
    </div>

    <!-- Contenu sous-onglet Statistiques -->
    <div id="fidelisation-subtab-content-statistiques" class="fidelisation-subtab-content hidden">
        <!-- Cartes de statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total clients</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $fidelisationStats['stats']['total_clients'] ?? 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Clients uniques</p>
            </div>
            <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-xl shadow-sm border border-green-200 dark:border-green-800">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Clients réguliers</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $fidelisationStats['stats']['clients_reguliers'] ?? 0 }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    @if(($fidelisationStats['stats']['total_clients'] ?? 0) > 0)
                        {{ round((($fidelisationStats['stats']['clients_reguliers'] ?? 0) / $fidelisationStats['stats']['total_clients']) * 100, 1) }}% du total
                    @endif
                </p>
            </div>
            <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Taux de rétention</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $fidelisationStats['stats']['taux_retention'] ?? 0 }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Clients avec 2+ visites</p>
            </div>
            <div class="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-xl shadow-sm border border-purple-200 dark:border-purple-800">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">CA moyen / client</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($fidelisationStats['stats']['ca_moyen_client'] ?? 0, 2, ',', ' ') }}€</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Par client</p>
            </div>
        </div>

        <!-- Métriques supplémentaires -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="p-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Durée moyenne entre visites</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $fidelisationStats['stats']['duree_moyenne_entre_visites'] ?? 0 }} jours</p>
            </div>
            <div class="p-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Clients occasionnels</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $fidelisationStats['stats']['clients_occasionnels'] ?? 0 }}</p>
            </div>
            <div class="p-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Clients nouveaux</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $fidelisationStats['stats']['clients_nouveaux'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Répartition des statuts -->
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Répartition des clients</h3>
                <canvas id="repartitionStatutsChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Évolution des clients -->
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Nouveaux clients (12 mois)</h3>
                <canvas id="evolutionClientsChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Évolution des statuts -->
            @if(!empty($fidelisationStats['stats']['evolution_statuts']))
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Évolution des statuts (12 derniers mois)</h3>
                <canvas id="evolutionStatutsChart" style="max-height: 300px;"></canvas>
            </div>
            @endif
        </div>

        <!-- Top clients -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top clients par CA -->
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">⭐ Top 5 clients par CA</h3>
                @if(!empty($fidelisationStats['stats']['top_clients_ca']))
                    <div class="space-y-3">
                        @foreach($fidelisationStats['stats']['top_clients_ca'] as $index => $clientData)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-slate-400 dark:text-slate-500">{{ $index + 1 }}</span>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            @if($clientData['client'])
                                                {{ $clientData['client']->name }}
                                            @else
                                                Client inconnu
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $clientData['nb_reservations'] }} réservation{{ $clientData['nb_reservations'] > 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600 dark:text-green-400">{{ number_format($clientData['ca_total'], 2, ',', ' ') }}€</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500 dark:text-slate-400 text-center py-4">Aucune donnée disponible</p>
                @endif
            </div>

            <!-- Top clients par fréquence -->
            <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">🔥 Top 5 clients par fréquence</h3>
                @if(!empty($fidelisationStats['stats']['top_clients_frequence']))
                    <div class="space-y-3">
                        @foreach($fidelisationStats['stats']['top_clients_frequence'] as $index => $clientData)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-slate-400 dark:text-slate-500">{{ $index + 1 }}</span>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            @if($clientData['client'])
                                                {{ $clientData['client']->name }}
                                            @else
                                                Client inconnu
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $clientData['nb_reservations'] }} réservation{{ $clientData['nb_reservations'] > 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($clientData['frequence_moyenne'], 2, ',', ' ') }}/mois</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500 dark:text-slate-400 text-center py-4">Aucune donnée disponible</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Contenu sous-onglet Actions -->
    <div id="fidelisation-subtab-content-actions" class="fidelisation-subtab-content hidden">
        <div class="space-y-6">
            <!-- Section actions rapides -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Actions rapides</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Envoyer un rappel aux clients à risque -->
                    <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Rappel aux clients à risque</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Envoyez un rappel personnalisé aux {{ count($fidelisationClientsARisque['clients'] ?? []) }} client(s) qui n'ont pas réservé depuis un certain temps.
                                </p>
                                <button 
                                    onclick="envoyerRappelsClientsRisque()"
                                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition"
                                    {{ count($fidelisationClientsARisque['clients'] ?? []) === 0 ? 'disabled' : '' }}
                                >
                                    Envoyer des rappels
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Offre spéciale aux clients réguliers -->
                    <div class="p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Offre spéciale clients réguliers</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Récompensez vos {{ $fidelisationStats['stats']['clients_reguliers'] ?? 0 }} client(s) régulier(s) avec une offre spéciale.
                                </p>
                                <button 
                                    onclick="envoyerOffreReguliers()"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition"
                                    {{ ($fidelisationStats['stats']['clients_reguliers'] ?? 0) === 0 ? 'disabled' : '' }}
                                >
                                    Créer une offre
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Message de bienvenue aux nouveaux -->
                    <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Accueillir les nouveaux clients</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Envoyez un message de bienvenue aux {{ $fidelisationStats['stats']['clients_nouveaux'] ?? 0 }} nouveau(x) client(s).
                                </p>
                                <button 
                                    onclick="envoyerBienvenueNouveaux()"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                    {{ ($fidelisationStats['stats']['clients_nouveaux'] ?? 0) === 0 ? 'disabled' : '' }}
                                >
                                    Envoyer un message
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Relancer les occasionnels -->
                    <div class="p-6 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Relancer les occasionnels</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                    Encouragez vos {{ $fidelisationStats['stats']['clients_occasionnels'] ?? 0 }} client(s) occasionnel(s) à revenir plus souvent.
                                </p>
                                <button 
                                    onclick="relancerOccasionnels()"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition"
                                    {{ ($fidelisationStats['stats']['clients_occasionnels'] ?? 0) === 0 ? 'disabled' : '' }}
                                >
                                    Relancer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conseils et astuces -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h4 class="font-semibold text-slate-900 dark:text-white mb-3">💡 Conseils pour améliorer la fidélisation</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600 dark:text-slate-400">
                    <div>
                        <p class="font-medium mb-1">✅ Personnalisez vos messages</p>
                        <p>Les clients apprécient les messages adaptés à leur historique de visite.</p>
                    </div>
                    <div>
                        <p class="font-medium mb-1">✅ Timing optimal</p>
                        <p>Envoyez des rappels au moment où ils sont habituellement revenus.</p>
                    </div>
                    <div>
                        <p class="font-medium mb-1">✅ Offres ciblées</p>
                        <p>Proposez des offres adaptées au profil de chaque client (régulier, occasionnel, nouveau).</p>
                    </div>
                    <div>
                        <p class="font-medium mb-1">✅ Suivez les résultats</p>
                        <p>Analysez l'efficacité de vos campagnes pour améliorer continuellement.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des sous-onglets de fidélisation
        function showFidelisationSubTab(subtabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.fidelisation-subtab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Réinitialiser tous les boutons
            document.querySelectorAll('.fidelisation-subtab-btn').forEach(button => {
                button.classList.remove('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
                button.classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent');
            });

            // Afficher le contenu sélectionné
            const subtabContent = document.getElementById('fidelisation-subtab-content-' + subtabName);
            if (subtabContent) {
                subtabContent.classList.remove('hidden');
            }

            // Activer le bouton sélectionné
            const activeButton = document.getElementById('fidelisation-subtab-' + subtabName);
            if (activeButton) {
                activeButton.classList.remove('text-slate-500', 'dark:text-slate-400', 'border-transparent');
                activeButton.classList.add('bg-green-100', 'dark:bg-green-900/30', 'text-green-700', 'dark:text-green-400', 'border-green-500', 'dark:border-green-400');
            }

            // Initialiser les graphiques si on affiche l'onglet statistiques
            if (subtabName === 'statistiques') {
                setTimeout(() => {
                    initStatistiquesCharts();
                }, 100);
            }
        }

        // Initialiser le premier sous-onglet
        document.addEventListener('DOMContentLoaded', function() {
            showFidelisationSubTab('clients-reguliers');
            initStatistiquesCharts();
        });

        // Initialiser les graphiques de statistiques
        function initStatistiquesCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e2e8f0' : '#1e293b';
            const gridColor = isDark ? '#334155' : '#e2e8f0';

            const statsData = @json($fidelisationStats['stats'] ?? []);

            // Graphique répartition des statuts
            const repartitionCtx = document.getElementById('repartitionStatutsChart');
            if (repartitionCtx && statsData) {
                new Chart(repartitionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Réguliers', 'Occasionnels', 'Nouveaux'],
                        datasets: [{
                            data: [
                                statsData.clients_reguliers ?? 0,
                                statsData.clients_occasionnels ?? 0,
                                statsData.clients_nouveaux ?? 0
                            ],
                            backgroundColor: [
                                '#22c55e',
                                '#eab308',
                                '#3b82f6'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor, padding: 15 }
                            }
                        }
                    }
                });
            }

            // Graphique évolution des clients
            const evolutionCtx = document.getElementById('evolutionClientsChart');
            if (evolutionCtx && statsData.evolution_mois) {
                new Chart(evolutionCtx, {
                    type: 'line',
                    data: {
                        labels: statsData.evolution_mois.map(m => m.mois),
                        datasets: [{
                            label: 'Nouveaux clients',
                            data: statsData.evolution_mois.map(m => m.clients),
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { labels: { color: textColor } }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: textColor, stepSize: 1 },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }

            // Graphique évolution des statuts
            const evolutionStatutsCtx = document.getElementById('evolutionStatutsChart');
            if (evolutionStatutsCtx && statsData.evolution_statuts) {
                new Chart(evolutionStatutsCtx, {
                    type: 'line',
                    data: {
                        labels: statsData.evolution_statuts.map(e => e.mois),
                        datasets: [
                            {
                                label: 'Réguliers',
                                data: statsData.evolution_statuts.map(e => e.reguliers),
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Occasionnels',
                                data: statsData.evolution_statuts.map(e => e.occasionnels),
                                borderColor: '#eab308',
                                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Nouveaux',
                                data: statsData.evolution_statuts.map(e => e.nouveaux),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { labels: { color: textColor } }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: textColor, stepSize: 1 },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }
        }

        // Fonctions d'actions
        function envoyerRappel(clientId, email) {
            if (confirm('Voulez-vous envoyer un rappel par email à ce client ?')) {
                // TODO: Implémenter l'envoi de rappel
                alert('Fonctionnalité à venir : envoi de rappel personnalisé à ' + email);
            }
        }

        function ouvrirMessagerie(clientId) {
            // Rediriger vers la messagerie avec ce client
            window.location.href = '{{ route("entreprise.dashboard", ["slug" => $entreprise->slug, "tab" => "messagerie"]) }}&client=' + clientId;
        }

        function envoyerRappelsClientsRisque() {
            const count = {{ count($fidelisationClientsARisque['clients'] ?? []) }};
            if (count === 0) {
                alert('Aucun client à risque pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous envoyer un rappel à ${count} client(s) à risque ?`)) {
                // TODO: Implémenter l'envoi groupé de rappels
                alert('Fonctionnalité à venir : envoi groupé de rappels');
            }
        }

        function envoyerOffreReguliers() {
            const count = {{ $fidelisationStats['stats']['clients_reguliers'] ?? 0 }};
            if (count === 0) {
                alert('Aucun client régulier pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous créer une offre spéciale pour ${count} client(s) régulier(s) ?`)) {
                // TODO: Implémenter la création d'offre spéciale
                alert('Fonctionnalité à venir : création d\'offre spéciale');
            }
        }

        function envoyerBienvenueNouveaux() {
            const count = {{ $fidelisationStats['stats']['clients_nouveaux'] ?? 0 }};
            if (count === 0) {
                alert('Aucun nouveau client pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous envoyer un message de bienvenue à ${count} nouveau(x) client(s) ?`)) {
                // TODO: Implémenter l'envoi de message de bienvenue
                alert('Fonctionnalité à venir : envoi de message de bienvenue');
            }
        }

        function relancerOccasionnels() {
            const count = {{ $fidelisationStats['stats']['clients_occasionnels'] ?? 0 }};
            if (count === 0) {
                alert('Aucun client occasionnel pour le moment.');
                return;
            }
            if (confirm(`Voulez-vous relancer ${count} client(s) occasionnel(s) pour les encourager à revenir plus souvent ?`)) {
                // TODO: Implémenter la relance des occasionnels
                alert('Fonctionnalité à venir : relance des clients occasionnels');
            }
        }
    </script>
</div>
