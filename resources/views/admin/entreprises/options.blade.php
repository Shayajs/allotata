@extends('admin.layout')

@section('title', 'Options - ' . $entreprise->nom . ' - Administration')
@section('header', 'Options de l\'Entreprise')
@section('subheader', 'Gestion des abonnements et des membres pour ' . $entreprise->nom)

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('admin.entreprises.show', $entreprise) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition-colors">
        ← <span class="ml-2">Retour à la fiche entreprise</span>
    </a>
</div>

<!-- Onglets -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
        <nav class="flex overflow-x-auto scrollbar-hide" aria-label="Tabs">
            <button 
                onclick="showTab('abonnements')"
                class="tab-button px-4 lg:px-8 py-5 text-sm font-bold whitespace-nowrap border-b-2 border-green-500 text-green-600 flex items-center gap-2 transition-all"
                data-tab="abonnements"
            >
                <span class="text-lg">💳</span> Abonnements
            </button>
            <button 
                onclick="showTab('membres')"
                class="tab-button px-4 lg:px-8 py-5 text-sm font-bold whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-2 transition-all"
                data-tab="membres"
            >
                <span class="text-lg">👥</span> Membres & Accès
            </button>
        </nav>
    </div>

    <div class="p-4 lg:p-8">
        <!-- Onglet Abonnements -->
        <div id="tab-abonnements" class="tab-content space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Site Web Vitrine -->
                @php
                    $aSiteWebActif = $entreprise->aSiteWebActif();
                    $aGestionMultiPersonnes = $entreprise->aGestionMultiPersonnes();
                @endphp
                
                <div class="rounded-3xl border-2 transition-all overflow-hidden {{ $aSiteWebActif ? 'border-green-100 dark:border-green-900/30' : 'border-slate-100 dark:border-slate-700' }}">
                    <div class="p-6 lg:p-8 {{ $aSiteWebActif ? 'bg-green-50/50 dark:bg-green-900/10' : 'bg-white dark:bg-slate-800' }}">
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-4xl">🌐</span>
                            @if($aSiteWebActif)
                                <span class="px-2 py-1 text-[10px] uppercase font-bold bg-green-500 text-white rounded-lg">Option Active</span>
                            @else
                                <span class="px-2 py-1 text-[10px] uppercase font-bold bg-slate-400 dark:bg-slate-600 text-white rounded-lg">Option Inactive</span>
                            @endif
                        </div>
                        <h3 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white mb-2">Site Web Vitrine</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs lg:text-sm mb-6">Plateforme dédiée pour présenter l'activité de l'entreprise avec URL personnalisée.</p>
                        <div class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white mb-8">{{ $subscriptionPrices['site_web']['formatted'] }} <span class="text-sm text-slate-400 font-normal">/ mois</span></div>

                        @if($abonnementSiteWeb)
                            <div class="bg-white dark:bg-slate-900 p-4 lg:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 mb-6">
                                <dl class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-slate-500 dark:text-slate-400">Mode :</dt>
                                        <dd class="font-bold text-slate-900 dark:text-white">{{ $abonnementSiteWeb->est_manuel ? 'Manuel (Admin)' : 'Stripe 自動' }}</dd>
                                    </div>
                                    @if($abonnementSiteWeb->actif_jusqu)
                                        <div class="flex justify-between">
                                            <dt class="text-slate-500 dark:text-slate-400">Expire le :</dt>
                                            <dd class="font-bold text-green-600 dark:text-green-400">{{ $abonnementSiteWeb->actif_jusqu->format('d/m/Y') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                                @if($abonnementSiteWeb->est_manuel)
                                    <form action="{{ route('admin.entreprises.options.desactiver', [$entreprise, 'site_web']) }}" method="POST" onsubmit="return confirm('Désactiver cette option ?');" class="mt-4">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 text-xs font-bold rounded-xl transition-all dark:border-red-900/30 border border-red-100">
                                            Révoquer l'accès manuel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(!$aSiteWebActif)
                        <div class="p-6 lg:p-8 border-t border-slate-100 dark:border-slate-700">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-4 text-sm lg:text-base">Offrir un accès manuel</h4>
                            <form action="{{ route('admin.entreprises.options.activer', $entreprise) }}" method="POST" class="space-y-4" onsubmit="return confirm('Activer cette option pour cette entreprise ?')">
                                @csrf
                                <input type="hidden" name="type" value="site_web">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Validité jusqu'au</label>
                                    <input 
                                        type="date" 
                                        name="date_fin" 
                                        value="{{ old('date_fin', now()->addMonth()->format('Y-m-d')) }}"
                                        min="{{ now()->addDay()->format('Y-m-d') }}"
                                        required
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                                    >
                                </div>
                                <textarea 
                                    name="notes" 
                                    rows="2"
                                    placeholder="Notes administratives..."
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                                >{{ old('notes') }}</textarea>
                                <button type="submit" class="w-full px-6 py-4 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-2xl hover:scale-[1.02] transition-transform active:scale-95 shadow-lg text-sm">
                                    Offrir l'accès
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Multi-Personnes -->
                <div class="rounded-3xl border-2 transition-all overflow-hidden {{ $aGestionMultiPersonnes ? 'border-green-100 dark:border-green-900/30' : 'border-slate-100 dark:border-slate-700' }}">
                    <div class="p-6 lg:p-8 {{ $aGestionMultiPersonnes ? 'bg-green-50/50 dark:bg-green-900/10' : 'bg-white dark:bg-slate-800' }}">
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-4xl">👥</span>
                            @if($aGestionMultiPersonnes)
                                <span class="px-2 py-1 text-[10px] uppercase font-bold bg-green-500 text-white rounded-lg">Option Active</span>
                            @else
                                <span class="px-2 py-1 text-[10px] uppercase font-bold bg-slate-400 dark:bg-slate-600 text-white rounded-lg">Option Inactive</span>
                            @endif
                        </div>
                        <h3 class="text-xl lg:text-2xl font-bold text-slate-900 dark:text-white mb-2">Multi-Personnes</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs lg:text-sm mb-6">Permet d'ajouter des collaborateurs et de gérer des agendas multiples.</p>
                        <div class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white mb-8">{{ $subscriptionPrices['multi_personnes']['formatted'] }} <span class="text-sm text-slate-400 font-normal">/ mois</span></div>

                        @if($abonnementMultiPersonnes)
                            <div class="bg-white dark:bg-slate-900 p-4 lg:p-6 rounded-2xl border border-slate-100 dark:border-slate-700 mb-6">
                                <dl class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-slate-500 dark:text-slate-400">Mode :</dt>
                                        <dd class="font-bold text-slate-900 dark:text-white">{{ $abonnementMultiPersonnes->est_manuel ? 'Manuel (Admin)' : 'Paiement Automatique' }}</dd>
                                    </div>
                                    @if($abonnementMultiPersonnes->actif_jusqu)
                                        <div class="flex justify-between">
                                            <dt class="text-slate-500 dark:text-slate-400">Fin d'accès :</dt>
                                            <dd class="font-bold text-green-600 dark:text-green-400">{{ $abonnementMultiPersonnes->actif_jusqu->format('d/m/Y') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                                @if($abonnementMultiPersonnes->est_manuel)
                                    <form action="{{ route('admin.entreprises.options.desactiver', [$entreprise, 'multi_personnes']) }}" method="POST" onsubmit="return confirm('Désactiver cette option ?');" class="mt-4">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 text-xs font-bold rounded-xl transition-all dark:border-red-900/30 border border-red-100">
                                            Révoquer l'accès manuel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(!$aGestionMultiPersonnes)
                        <div class="p-6 lg:p-8 border-t border-slate-100 dark:border-slate-700">
                            <h4 class="font-bold text-slate-900 dark:text-white mb-4 text-sm lg:text-base">Offrir un accès manuel</h4>
                            <form action="{{ route('admin.entreprises.options.activer', $entreprise) }}" method="POST" class="space-y-4" onsubmit="return confirm('Activer l\'option Multi-Personnes pour cette entreprise ?')">
                                @csrf
                                <input type="hidden" name="type" value="multi_personnes">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Validité jusqu'au</label>
                                    <input 
                                        type="date" 
                                        name="date_fin" 
                                        value="{{ old('date_fin', now()->addMonth()->format('Y-m-d')) }}"
                                        min="{{ now()->addDay()->format('Y-m-d') }}"
                                        required
                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                                    >
                                </div>
                                <textarea 
                                    name="notes" 
                                    rows="2"
                                    placeholder="Notes administratives..."
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-sm"
                                >{{ old('notes') }}</textarea>
                                <button type="submit" class="w-full px-6 py-4 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-2xl hover:scale-[1.02] transition-transform active:scale-95 shadow-lg text-sm">
                                    Offrir l'accès
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Onglet Membres -->
        <div id="tab-membres" class="tab-content hidden space-y-8">
            @if(!$entreprise->aGestionMultiPersonnes())
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 lg:p-6 flex gap-4 items-center">
                    <span class="text-2xl lg:text-3xl">⚠️</span>
                    <div class="flex-1">
                        <p class="text-amber-800 dark:text-amber-400 font-bold mb-1 text-sm lg:text-base">Abonnement Inactif</p>
                        <p class="text-xs lg:text-sm text-amber-700 dark:text-amber-500">L'entreprise n'a pas souscrit à l'option multi-personnes. En tant qu'administrateur, vous pouvez tout de même forcer l'ajout de membres.</p>
                    </div>
                </div>
            @endif

            <!-- Formulaire d'ajout -->
            <div class="bg-slate-50 dark:bg-slate-900 rounded-3xl p-6 lg:p-8 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg lg:text-xl font-bold text-slate-900 dark:text-white mb-6">➕ Ajouter un nouveau membre</h3>
                <form action="{{ route('admin.entreprises.membres.store', $entreprise) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Email de l'utilisateur</label>
                            <input 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required
                                placeholder="ex: jean@entreprise.com"
                                class="w-full px-4 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all shadow-sm text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Rôle attribué</label>
                            <select 
                                name="role" 
                                required
                                class="w-full px-4 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all shadow-sm appearance-none cursor-pointer text-sm"
                            >
                                <option value="membre">📁 Membre standard</option>
                                <option value="administrateur" {{ old('role') === 'administrateur' ? 'selected' : '' }}>🛡️ Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-2xl transition-all hover:scale-105 active:scale-95 shadow-lg text-sm">
                            Enregistrer le membre
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des membres -->
            <div class="border border-slate-100 dark:border-slate-700 rounded-3xl overflow-x-auto shadow-sm">
                <table class="w-full text-left min-w-[600px]">
                    <thead class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="px-6 lg:px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Collaborateur</th>
                            <th class="px-6 lg:px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Rôle & Droits</th>
                            <th class="px-6 lg:px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        <!-- Propriétaire -->
                        <tr class="bg-blue-50/20 dark:bg-blue-900/10">
                            <td class="px-6 lg:px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <x-avatar :user="$entreprise->user" size="sm" />
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-sm lg:text-base">{{ $entreprise->user->name }}</p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $entreprise->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 lg:px-8 py-6 text-sm">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded-lg font-bold text-[10px] uppercase tracking-wider">👑 Propriétaire</span>
                            </td>
                            <td class="px-6 lg:px-8 py-6 text-right">
                                <a href="{{ route('admin.users.show', $entreprise->user) }}" class="text-[10px] font-bold text-slate-400 hover:text-green-600 transition-colors uppercase tracking-widest">Voir profil</a>
                            </td>
                        </tr>

                        <!-- Autres membres -->
                        @foreach($membres as $membre)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-6 lg:px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <x-avatar :user="$membre->user" size="sm" />
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-sm lg:text-base">{{ $membre->user->name }}</p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $membre->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 lg:px-8 py-6">
                                    <form action="{{ route('admin.entreprises.membres.update', [$entreprise, $membre]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <select name="role" onchange="this.form.submit()" class="px-2 py-1 text-[10px] border border-transparent bg-slate-100 dark:bg-slate-700 rounded-lg text-slate-700 dark:text-slate-300 font-bold focus:ring-0 cursor-pointer uppercase tracking-wider">
                                            <option value="membre" {{ $membre->role === 'membre' ? 'selected' : '' }}>Membre</option>
                                            <option value="administrateur" {{ $membre->role === 'administrateur' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </form>
                                    @if(!$membre->est_actif)
                                        <span class="ml-2 px-2 py-1 bg-red-100 text-red-700 rounded-md text-[10px] font-bold uppercase">Invit.</span>
                                    @endif
                                </td>
                                <td class="px-6 lg:px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.users.show', $membre->user) }}" class="text-[10px] font-bold text-slate-400 hover:text-green-600 transition-colors uppercase tracking-widest">Détails</a>
                                        <form action="{{ route('admin.entreprises.membres.destroy', [$entreprise, $membre]) }}" method="POST" class="inline" onsubmit="return confirm('Retirer ce membre ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] font-bold text-red-500 hover:text-red-700 transition-colors uppercase tracking-widest">Révoquer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        // Masquer tous les contenus
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Réinitialiser tous les boutons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-green-500', 'text-green-600');
            button.classList.add('border-transparent', 'text-slate-500');
        });

        // Afficher le contenu
        document.getElementById('tab-' + tabName)?.classList.remove('hidden');

        // Activer le bouton
        const btn = document.querySelector(`[data-tab="${tabName}"]`);
        if (btn) {
            btn.classList.remove('border-transparent', 'text-slate-500');
            btn.classList.add('border-green-500', 'text-green-600');
        }
    }
</script>
@endsection
