<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Créer une facture groupée - {{ $entreprise->nom }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('factures.entreprise', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Retour aux factures
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    Créer une facture groupée
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Sélectionnez plusieurs réservations payées pour créer une facture groupée.
                </p>
            </div>

            <!-- Filtres -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Filtres</h2>
                <form id="filterForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Client
                        </label>
                        <select 
                            id="user_id"
                            name="user_id"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Type de service
                        </label>
                        <select 
                            id="type_service_id"
                            name="type_service_id"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les services</option>
                            @foreach($typesServices as $service)
                                <option value="{{ $service->id }}">{{ $service->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Mois
                        </label>
                        <input 
                            type="month" 
                            id="mois"
                            name="mois"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Date début
                        </label>
                        <input 
                            type="date" 
                            id="date_debut"
                            name="date_debut"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Date fin
                        </label>
                        <input 
                            type="date" 
                            id="date_fin"
                            name="date_fin"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Statut
                        </label>
                        <select 
                            id="statut"
                            name="statut"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="confirmee">Confirmée</option>
                            <option value="terminee">Terminée</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button 
                            type="button" 
                            id="searchBtn"
                            class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                        >
                            🔍 Rechercher
                        </button>
                        <button 
                            type="button" 
                            id="resetBtn"
                            class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition-all"
                        >
                            Réinitialiser
                        </button>
                    </div>
                </form>
            </div>

            <!-- Réservations -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                        Réservations disponibles
                    </h2>
                    <div class="flex items-center gap-4">
                        <span id="selectedCount" class="text-sm text-slate-600 dark:text-slate-400">
                            0 sélectionnée(s)
                        </span>
                        <span id="totalAmount" class="text-sm font-semibold text-green-600 dark:text-green-400">
                            Total : 0,00 €
                        </span>
                    </div>
                </div>
                <div id="reservationsContainer" class="text-center py-8 text-slate-500 dark:text-slate-400">
                    Utilisez les filtres pour rechercher des réservations payées sans facture.
                </div>
            </div>

            <!-- Formulaire de création -->
            <form id="createForm" action="{{ route('factures.store-groupee', $entreprise->slug) }}" method="POST" class="hidden">
                @csrf
                <div id="reservationIdsContainer"></div>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Taux de TVA (%)
                        </label>
                        <input 
                            type="number" 
                            name="taux_tva" 
                            value="0" 
                            min="0" 
                            max="100" 
                            step="0.01"
                            class="w-full md:w-1/3 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>
                    <button 
                        type="submit" 
                        id="createBtn"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white font-semibold rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        📋 Créer la facture groupée
                    </button>
                </div>
            </form>
        </div>

        <script>
            const entrepriseSlug = '{{ $entreprise->slug }}';
            let selectedReservations = new Set();
            let allReservations = [];

            document.getElementById('searchBtn').addEventListener('click', function() {
                searchReservations();
            });

            document.getElementById('resetBtn').addEventListener('click', function() {
                document.getElementById('filterForm').reset();
                selectedReservations.clear();
                allReservations = [];
                updateUI();
            });

            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                searchReservations();
            });

            function searchReservations() {
                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams();
                
                for (const [key, value] of formData.entries()) {
                    if (value) {
                        params.append(key, value);
                    }
                }

                fetch(`/m/${entrepriseSlug}/factures/reservations?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    allReservations = data.reservations;
                    selectedReservations.clear();
                    updateUI();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la recherche.');
                });
            }

            function toggleReservation(id) {
                if (selectedReservations.has(id)) {
                    selectedReservations.delete(id);
                } else {
                    selectedReservations.add(id);
                }
                updateUI();
            }

            function updateUI() {
                const container = document.getElementById('reservationsContainer');
                
                if (allReservations.length === 0) {
                    container.innerHTML = '<p class="text-slate-500 dark:text-slate-400">Aucune réservation trouvée.</p>';
                    document.getElementById('createForm').classList.add('hidden');
                    return;
                }

                let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700"><thead class="bg-slate-50 dark:bg-slate-700"><tr>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider"><input type="checkbox" id="selectAll" class="rounded"></th>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Client</th>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Service</th>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prix</th>';
                html += '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>';
                html += '</tr></thead><tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">';

                allReservations.forEach(reservation => {
                    const isSelected = selectedReservations.has(reservation.id);
                    html += `<tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 ${isSelected ? 'bg-green-50 dark:bg-green-900/20' : ''}">`;
                    html += `<td class="px-6 py-4 whitespace-nowrap"><input type="checkbox" class="reservation-checkbox rounded" data-id="${reservation.id}" ${isSelected ? 'checked' : ''}></td>`;
                    html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">${reservation.date}</td>`;
                    html += `<td class="px-6 py-4 text-sm text-slate-900 dark:text-white">${reservation.client}<br><span class="text-slate-500 dark:text-slate-400">${reservation.client_email}</span></td>`;
                    html += `<td class="px-6 py-4 text-sm text-slate-900 dark:text-white">${reservation.service}</td>`;
                    html += `<td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white">${reservation.prix}</td>`;
                    html += `<td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">${reservation.statut}</span></td>`;
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;

                // Ajouter les event listeners
                document.querySelectorAll('.reservation-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        toggleReservation(parseInt(this.dataset.id));
                    });
                });

                document.getElementById('selectAll').addEventListener('change', function() {
                    if (this.checked) {
                        allReservations.forEach(r => selectedReservations.add(r.id));
                    } else {
                        selectedReservations.clear();
                    }
                    updateUI();
                });

                // Mettre à jour le compteur et le total
                const selected = Array.from(selectedReservations);
                document.getElementById('selectedCount').textContent = `${selected.length} sélectionnée(s)`;
                
                const total = allReservations
                    .filter(r => selectedReservations.has(r.id))
                    .reduce((sum, r) => {
                        // Extraire le nombre du prix (format: "XX,XX €")
                        const prixStr = r.prix.replace(/[^\d,]/g, '').replace(',', '.');
                        return sum + parseFloat(prixStr || 0);
                    }, 0);
                document.getElementById('totalAmount').textContent = `Total : ${total.toFixed(2).replace('.', ',')} €`;

                // Afficher/masquer le formulaire de création
                const container = document.getElementById('reservationIdsContainer');
                container.innerHTML = '';
                if (selected.length > 0) {
                    document.getElementById('createForm').classList.remove('hidden');
                    document.getElementById('createBtn').disabled = false;
                    // Créer les inputs cachés pour chaque réservation sélectionnée
                    selected.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'reservation_ids[]';
                        input.value = id;
                        container.appendChild(input);
                    });
                } else {
                    document.getElementById('createForm').classList.add('hidden');
                    document.getElementById('createBtn').disabled = true;
                }
            }
        </script>
    </body>
</html>

