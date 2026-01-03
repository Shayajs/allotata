{{-- Onglet Abonnements --}}
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
            Mes Abonnements
        </h2>
        <p class="text-slate-600 dark:text-slate-400">
            Gérez les abonnements et options de vos entreprises.
        </p>
    </div>

    @if($entreprises->count() > 0)
        <div class="space-y-12">
            @foreach($entreprises as $entreprise)
                @if(!$entreprise->trashed())
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                @if($entreprise->logo)
                                    <img src="{{ asset('media/' . $entreprise->logo) }}" alt="Logo" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <span class="w-8 h-8 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($entreprise->nom, 0, 1)) }}
                                    </span>
                                @endif
                                {{ $entreprise->nom }}
                            </h3>
                            <a href="{{ route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres']) }}" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                Accéder aux paramètres &rarr;
                            </a>
                        </div>
                        
                        <div class="p-6">
                            @include('entreprise.dashboard.tabs.abonnements', ['entreprise' => $entreprise])
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Aucun abonnement disponible</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Vous devez d'abord créer une entreprise pour souscrire à des abonnements.
            </p>
            <div class="mt-6">
                <a href="{{ route('entreprise.create') }}" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600">
                    + Créer mon entreprise
                </a>
            </div>
        </div>
    @endif
</div>
