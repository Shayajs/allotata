@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">🎁 Essais Gratuits</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Gérez et analysez les essais gratuits accordés aux utilisateurs et entreprises.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.essais-gratuits.export') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition text-sm">
                📥 Export CSV
            </a>
            <button onclick="openAccorderModal()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition text-sm">
                + Accorder un essai
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-800 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Statistiques -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Actifs</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['actifs'] }}</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Convertis</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['convertis'] }}</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Expirés</p>
            <p class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ $stats['expires'] }}</p>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Taux conversion</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['taux_conversion'] }}%</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-4">
            <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Expirent bientôt</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['expirent_bientot'] }}</p>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="mb-6 flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'all']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'all' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                Tous
            </a>
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'actifs']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'actifs' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                🟢 Actifs
            </a>
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'convertis']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'convertis' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                💰 Convertis
            </a>
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'expires']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'expires' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                ⏰ Expirés
            </a>
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'users']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'users' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                👤 Utilisateurs
            </a>
            <a href="{{ route('admin.essais-gratuits.index', ['filter' => 'entreprises']) }}" 
               class="px-3 py-1.5 rounded-lg transition text-sm {{ $filter === 'entreprises' ? 'bg-green-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                🏢 Entreprises
            </a>
        </div>
        
        <form action="{{ route('admin.essais-gratuits.index') }}" method="GET" class="flex gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}"
                placeholder="Rechercher..."
                class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
            >
            <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm">
                🔍
            </button>
        </form>
    </div>

    <!-- Liste des essais -->
    @if($essais->count() > 0)
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Cible</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Période</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Engagement</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($essais as $essai)
                            @php
                                $cible = $essai->essayable;
                                $isUser = $essai->essayable_type === 'App\\Models\\User';
                                $nomCible = $isUser ? ($cible->name ?? 'N/A') : ($cible->nom ?? 'N/A');
                                $emailCible = $cible->email ?? 'N/A';
                                $statuts = \App\Models\EssaiGratuit::getStatuts();
                                $statutInfo = $statuts[$essai->statut] ?? ['label' => $essai->statut, 'color' => 'gray'];
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $isUser ? '👤' : '🏢' }}</span>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $nomCible }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $emailCible }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                        {{ \App\Models\EssaiGratuit::getTypesAbonnement()[$essai->type_abonnement]['label'] ?? $essai->type_abonnement }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-slate-900 dark:text-white">{{ $essai->date_debut->format('d/m/Y') }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">→ {{ $essai->date_fin->format('d/m/Y') }}</p>
                                    @if($essai->estEnCours())
                                        <p class="text-xs text-green-600 dark:text-green-400 font-medium">{{ $essai->joursRestants() }}j restants</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $statutInfo['color'] === 'green' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : '' }}
                                        {{ $statutInfo['color'] === 'blue' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400' : '' }}
                                        {{ $statutInfo['color'] === 'gray' ? 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' : '' }}
                                        {{ $statutInfo['color'] === 'yellow' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : '' }}
                                        {{ $statutInfo['color'] === 'red' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' : '' }}
                                    ">
                                        {{ $statutInfo['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ \App\Models\EssaiGratuit::getSources()[$essai->source] ?? $essai->source ?? '-' }}
                                    </p>
                                    @if($essai->accorde_par_admin_id)
                                        <p class="text-xs text-slate-500">Par: {{ $essai->accordeParAdmin->name ?? 'Admin' }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 text-xs">
                                        <span title="Connexions" class="flex items-center gap-1">
                                            🔗 {{ $essai->nb_connexions }}
                                        </span>
                                        <span title="Actions" class="flex items-center gap-1">
                                            ⚡ {{ $essai->nb_actions }}
                                        </span>
                                        @if($essai->note_satisfaction)
                                            <span title="Satisfaction" class="flex items-center gap-1">
                                                ⭐ {{ $essai->note_satisfaction }}/5
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($essai->statut === 'actif')
                                            <button 
                                                onclick="openProlongerModal({{ $essai->id }})"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 text-xs font-medium"
                                            >
                                                ➕ Prolonger
                                            </button>
                                            <button 
                                                onclick="openRevoquerModal({{ $essai->id }})"
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs font-medium"
                                            >
                                                ❌ Révoquer
                                            </button>
                                        @elseif($essai->statut === 'expire')
                                            <button 
                                                onclick="openProlongerModal({{ $essai->id }})"
                                                class="text-green-600 dark:text-green-400 hover:text-green-800 text-xs font-medium"
                                            >
                                                🔄 Réactiver
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $essais->links() }}
        </div>
    @else
        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-200 dark:border-slate-700 p-8 text-center">
            <p class="text-slate-600 dark:text-slate-400">Aucun essai gratuit trouvé.</p>
        </div>
    @endif
</div>

<!-- Modal Accorder un essai -->
<div id="accorder-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">🎁 Accorder un essai gratuit</h3>
            <button onclick="closeAccorderModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="{{ route('admin.essais-gratuits.accorder') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type de cible *</label>
                <select name="type_cible" id="type_cible" required onchange="updateCibleSelect()" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="user">Utilisateur</option>
                    <option value="entreprise">Entreprise</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Cible *</label>
                <input type="number" name="cible_id" placeholder="ID de l'utilisateur ou entreprise" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <p class="mt-1 text-xs text-slate-500">Entrez l'ID de l'utilisateur ou de l'entreprise</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type d'abonnement *</label>
                <select name="type_abonnement" id="type_abonnement_select" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="premium">Premium (Utilisateur)</option>
                    <option value="site_web">Site Web Vitrine</option>
                    <option value="multi_personnes">Gestion Multi-Personnes</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Durée (jours) *</label>
                <input type="number" name="duree_jours" value="7" min="1" max="90" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Notes (optionnel)</label>
                <textarea name="notes" rows="2" placeholder="Raison de l'octroi..." class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAccorderModal()" class="flex-1 px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-medium rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Accorder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Prolonger -->
<div id="prolonger-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">➕ Prolonger l'essai</h3>
        <form id="prolonger-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Jours supplémentaires</label>
                <input type="number" name="jours_supplementaires" value="7" min="1" max="30" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeProlongerModal()" class="flex-1 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg">Annuler</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Prolonger</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Révoquer -->
<div id="revoquer-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">❌ Révoquer l'essai</h3>
        <form id="revoquer-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Raison *</label>
                <input type="text" name="raison" required placeholder="Raison de la révocation..." class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeRevoquerModal()" class="flex-1 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg">Annuler</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">Révoquer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAccorderModal() {
        document.getElementById('accorder-modal').classList.remove('hidden');
    }
    function closeAccorderModal() {
        document.getElementById('accorder-modal').classList.add('hidden');
    }
    
    function openProlongerModal(id) {
        document.getElementById('prolonger-form').action = `/admin/essais-gratuits/${id}/prolonger`;
        document.getElementById('prolonger-modal').classList.remove('hidden');
    }
    function closeProlongerModal() {
        document.getElementById('prolonger-modal').classList.add('hidden');
    }
    
    function openRevoquerModal(id) {
        document.getElementById('revoquer-form').action = `/admin/essais-gratuits/${id}/revoquer`;
        document.getElementById('revoquer-modal').classList.remove('hidden');
    }
    function closeRevoquerModal() {
        document.getElementById('revoquer-modal').classList.add('hidden');
    }

    function updateCibleSelect() {
        const typeCible = document.getElementById('type_cible').value;
        const typeAbonnementSelect = document.getElementById('type_abonnement_select');
        
        if (typeCible === 'user') {
            typeAbonnementSelect.innerHTML = '<option value="premium">Premium (Utilisateur)</option>';
        } else {
            typeAbonnementSelect.innerHTML = `
                <option value="site_web">Site Web Vitrine</option>
                <option value="multi_personnes">Gestion Multi-Personnes</option>
            `;
        }
    }

    // Fermer les modales en cliquant dehors
    document.querySelectorAll('#accorder-modal, #prolonger-modal, #revoquer-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
</script>
@endsection
