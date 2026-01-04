@extends('admin.layout')

@section('title', $entreprise->nom . ' - Administration')
@section('header', 'Détails de l\'Entreprise')
@section('subheader', $entreprise->nom . ' (' . $entreprise->type_activite . ')')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white mb-2 truncate">{{ $entreprise->nom }}</h1>
        <div class="flex flex-wrap gap-2">
            @if($entreprise->est_verifiee)
                <span class="px-3 py-1 text-xs lg:text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full border border-green-200 dark:border-green-800 font-medium">
                    ✓ <span class="hidden lg:inline">Entreprise</span> vérifiée
                </span>
            @elseif($entreprise->aDesRefus())
                <span class="px-3 py-1 text-xs lg:text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full border border-red-200 dark:border-red-800 font-medium">
                    ✗ Refusée
                </span>
            @else
                <span class="px-3 py-1 text-xs lg:text-sm bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800 font-medium">
                    ⏳ <span class="hidden lg:inline">En attente de vérification</span><span class="lg:hidden">En attente</span>
                </span>
            @endif
        </div>
    </div>
    <a href="{{ route('admin.entreprises.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-50 transition-all shadow-sm text-sm">
        ← <span class="ml-2">Retour</span> <span class="hidden md:inline ml-1">à la liste</span>
    </a>
</div>

<!-- Panneau de vérification -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 mb-8">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600">🔍</span>
        Processus de vérification
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Vérification du nom -->
        <div class="p-6 border-2 rounded-2xl transition-all {{ $entreprise->nom_valide === true ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' : ($entreprise->nom_valide === false ? 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50') }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 dark:text-white">Nom commercial</h3>
                @if($entreprise->nom_valide === true)
                    <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-green-500 text-white rounded-lg">Validé</span>
                @elseif($entreprise->nom_valide === false)
                    <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-red-500 text-white rounded-lg">Refusé</span>
                @else
                    <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-yellow-500 text-white rounded-lg">À vérifier</span>
                @endif
            </div>
            
            <p class="text-lg font-bold text-slate-900 dark:text-white mb-4">{{ $entreprise->nom }}</p>
            
            @if($entreprise->nom_valide !== true)
                <div class="flex gap-2">
                    <form action="{{ route('admin.entreprises.validate-nom', $entreprise) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition-transform active:scale-95">
                            Valider
                        </button>
                    </form>
                    <button 
                        onclick="document.getElementById('modal-refus-nom').classList.remove('hidden')"
                        class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-transform active:scale-95"
                    >
                        Refuser
                    </button>
                </div>
            @endif
            
            @if($entreprise->nom_valide === false && $entreprise->nom_refus_raison)
                <div class="mt-4 p-3 bg-white/50 dark:bg-slate-900/50 rounded-xl border border-red-100 dark:border-red-900/30">
                    <p class="text-xs text-red-600 dark:text-red-400"><strong>Motif :</strong> {{ $entreprise->nom_refus_raison }}</p>
                </div>
            @endif
        </div>

        <!-- Vérification du SIREN -->
        @if($entreprise->siren)
            <div class="p-6 border-2 rounded-2xl transition-all {{ $entreprise->siren_valide === true ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' : ($entreprise->siren_valide === false ? 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50') }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900 dark:text-white">SIREN / SIRET</h3>
                    @if($entreprise->siren_valide === true)
                        <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-green-500 text-white rounded-lg">Validé</span>
                    @elseif($entreprise->siren_valide === false)
                        <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-red-500 text-white rounded-lg">Refusé</span>
                    @else
                        <span class="px-2 py-1 text-[10px] uppercase tracking-wider font-bold bg-yellow-500 text-white rounded-lg">À vérifier</span>
                    @endif
                </div>
                
                <p class="text-lg font-mono font-bold text-slate-900 dark:text-white mb-4">{{ $entreprise->siren }}</p>
                
                @if($entreprise->siren_valide !== true)
                    <div class="flex gap-2">
                        <form action="{{ route('admin.entreprises.validate-siren', $entreprise) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition-transform active:scale-95">
                                Valider
                            </button>
                        </form>
                        <button 
                            onclick="document.getElementById('modal-refus-siren').classList.remove('hidden')"
                            class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-transform active:scale-95"
                        >
                            Refuser
                        </button>
                    </div>
                @endif

                @if($entreprise->siren_valide === false && $entreprise->siren_refus_raison)
                    <div class="mt-4 p-3 bg-white/50 dark:bg-slate-900/50 rounded-xl border border-red-100 dark:border-red-900/30">
                        <p class="text-xs text-red-600 dark:text-red-400"><strong>Motif :</strong> {{ $entreprise->siren_refus_raison }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Actions globales -->
    <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700 flex flex-wrap gap-4">
        @if($entreprise->tousElementsValides() && !$entreprise->est_verifiee)
            <form action="{{ route('admin.entreprises.validate', $entreprise) }}" method="POST" onsubmit="return confirm('Valider cette entreprise ? Elle sera immédiatement visible.');">
                @csrf
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-bold rounded-xl shadow-lg shadow-green-200 dark:shadow-none transition-all hover:scale-105 active:scale-95">
                    🚀 Activer l'entreprise
                </button>
            </form>
        @endif
        
        @if(!$entreprise->tousElementsValides() || $entreprise->aDesRefus())
            <button 
                onclick="document.getElementById('modal-refus-global').classList.remove('hidden')"
                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all hover:scale-105 active:scale-95 shadow-lg shadow-red-200 dark:shadow-none"
            >
                🛑 Refuser l'entreprise
            </button>
        @endif

        <form action="{{ route('admin.entreprises.renvoyer', $entreprise) }}" method="POST" onsubmit="return confirm('Renvoyer cette entreprise pour correction ? Le gérant recevra une notification.');">
            @csrf
            <button type="submit" class="px-6 py-3 bg-slate-900 dark:bg-white dark:text-slate-900 text-white font-bold rounded-xl transition-all hover:scale-105 active:scale-95">
                🔄 Renvoyer pour modification
            </button>
        </form>
    </div>
    
    @if($entreprise->raison_refus_globale)
        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl">
            <p class="text-sm text-red-800 dark:text-red-400 font-medium"><strong>⚠️ Refus Global :</strong> {{ $entreprise->raison_refus_globale }}</p>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <!-- Informations détaillées -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Informations Générales</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div>
                        <dt class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">Email professionnel</dt>
                        <dd class="text-slate-900 dark:text-white font-medium">{{ $entreprise->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">Téléphone</dt>
                        <dd class="text-slate-900 dark:text-white font-medium">{{ $entreprise->telephone ?? 'Non renseigné' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">Ville</dt>
                        <dd class="text-slate-900 dark:text-white font-medium font-bold">{{ $entreprise->ville ?? 'Non renseignée' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">Statut Juridique</dt>
                        <dd class="text-slate-900 dark:text-white font-medium">{{ $entreprise->status_juridique ?? 'Non renseigné' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-bold text-slate-500 dark:text-slate-400 mb-1">Description</dt>
                        <dd class="text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700 italic">
                            {{ $entreprise->description ?? 'Aucune description fournie.' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Réservations -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20 flex justify-between items-center">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Dernières Réservations</h2>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 text-xs font-bold rounded-full">Total : {{ $entreprise->reservations->count() }}</span>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Client</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Prix</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($entreprise->reservations->sortByDesc('date_reservation')->take(8) as $reservation)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ $reservation->user->name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">{{ $reservation->date_reservation->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 font-bold text-green-600">{{ number_format($reservation->prix, 2, ',', ' ') }} €</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-xs font-bold text-slate-400 hover:text-green-600 uppercase tracking-widest transition-colors">Détails →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-8">
        <!-- Gérant -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col items-center text-center">
            <x-avatar :user="$entreprise->user" size="2xl" />
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-4">{{ $entreprise->user->name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Gérant d'entreprise</p>
            <div class="w-full flex flex-col gap-2">
                <a href="{{ route('admin.users.show', $entreprise->user) }}" class="px-6 py-3 bg-slate-900 dark:bg-white dark:text-slate-900 text-white text-sm font-bold rounded-xl hover:scale-105 transition-transform active:scale-95">
                    Consulter le gérant
                </a>
                <a href="mailto:{{ $entreprise->user->email }}" class="px-6 py-3 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                    Envoyer un email
                </a>
            </div>
        </div>

        <!-- Options Panel -->
        <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-100 dark:shadow-none relative overflow-hidden group">
            <div class="absolute right-[-20px] top-[-20px] opacity-10 rotate-12 transition-transform group-hover:scale-110">
                <span class="text-9xl">⚡</span>
            </div>
            <h2 class="text-xl font-bold mb-2">Options & Forfait</h2>
            <p class="text-sm opacity-80 mb-6">Gérez les plafonds de réservations et l'accès aux outils marketing de cette entreprise.</p>
            <a href="{{ route('admin.entreprises.options', $entreprise) }}" class="inline-block px-6 py-3 bg-white text-indigo-600 text-sm font-bold rounded-xl hover:scale-105 transition-transform active:scale-95">
                Gérer les limites
            </a>
        </div>

        @if($entreprise->siren_verifie)
            <!-- Billing -->
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-emerald-900 dark:text-white mb-2 flex items-center gap-2">
                    <span>🧾</span> Facturation
                </h2>
                <p class="text-sm text-emerald-800/80 dark:text-emerald-400/80 mb-6">
                    Cette entreprise génère des factures automatiquement pour ses clients.
                </p>
                <a href="{{ route('factures.entreprise', $entreprise->slug) }}" class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold hover:gap-3 transition-all">
                    Voir les rapports <span class="text-xl">→</span>
                </a>
            </div>
        @endif
    </div>
</div>

{{-- Modals remain the same but with better styling --}}
<div id="modal-refus-nom" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 text-left">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl p-8 max-w-md w-full animate-in fade-in zoom-in duration-200">
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Refuser le nom</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Précisez au gérant pourquoi le nom ne peut pas être accepté en l'état.</p>
        <form action="{{ route('admin.entreprises.reject-nom', $entreprise) }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Raison du refus</label>
                <textarea 
                    name="raison" 
                    rows="4"
                    required
                    placeholder="Ex: Le nom contient des caractères interdits ou est déjà pris..."
                    class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition-all placeholder:text-slate-400"
                ></textarea>
            </div>
            <div class="flex flex-col gap-3">
                <button type="submit" class="w-full px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl shadow-lg shadow-red-100 dark:shadow-none transition-all hover:scale-[1.02]">
                    Confirmer le refus
                </button>
                <button 
                    type="button"
                    onclick="this.closest('#modal-refus-nom').classList.add('hidden')"
                    class="w-full px-6 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                >
                    Fermer la fenêtre
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-refus-siren" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 text-left">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl p-8 max-w-md w-full animate-in fade-in zoom-in duration-200">
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Refuser le SIREN</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Le numéro SIREN/SIRET semble invalide ou ne correspond pas au nom de l'entreprise.</p>
        <form action="{{ route('admin.entreprises.reject-siren', $entreprise) }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Justification</label>
                <textarea 
                    name="raison" 
                    rows="4"
                    required
                    placeholder="Ex: Numéro non trouvé sur l'INSEE..."
                    class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition-all"
                ></textarea>
            </div>
            <div class="flex flex-col gap-3">
                <button type="submit" class="w-full px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl shadow-lg shadow-red-100 dark:shadow-none">
                    Confirmer le refus
                </button>
                <button 
                    type="button"
                    onclick="this.closest('#modal-refus-siren').classList.add('hidden')"
                    class="w-full px-6 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                >
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-refus-global" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 text-left">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl p-8 max-w-md w-full animate-in fade-in zoom-in duration-200">
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2 capitalize">Refus définitif</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Cette action refusera l'intégralité du dossier. Soyez précis dans votre explication.</p>
        <form action="{{ route('admin.entreprises.reject', $entreprise) }}" method="POST">
            @csrf
            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Raison globale</label>
                <textarea 
                    name="raison" 
                    rows="4"
                    required
                    placeholder="Expliquez ici l'ensemble du problème..."
                    class="w-full px-4 py-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition-all"
                ></textarea>
            </div>
            <div class="flex flex-col gap-3">
                <button type="submit" class="w-full px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl shadow-lg shadow-red-100 dark:shadow-none">
                    Confirmer le refus global
                </button>
                <button 
                    type="button"
                    onclick="this.closest('#modal-refus-global').classList.add('hidden')"
                    class="w-full px-6 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                >
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

