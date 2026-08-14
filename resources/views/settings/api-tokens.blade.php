@extends('layouts.user')

@section('title', 'Jetons d\'API')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8">
        <div>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Retour aux réglages
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Jetons d'API</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">
                Un jeton donne accès en lecture à vos entreprises via l'API : réservations, services, produits,
                clientèle, finances et statistiques.
                <a href="{{ $documentationUrl }}" class="text-green-600 dark:text-green-400 hover:underline">Voir la documentation</a>.
            </p>
        </div>

        @if(session('jeton_cree'))
            <div class="p-6 border border-amber-300 dark:border-amber-700 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                <h2 class="font-semibold text-amber-900 dark:text-amber-300 mb-2">Votre nouveau jeton</h2>
                <p class="text-sm text-amber-800 dark:text-amber-400 mb-3">
                    Copiez-le maintenant : il n'est stocké que sous forme d'empreinte et ne sera plus jamais affiché.
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <code id="jeton-clair" class="flex-1 px-4 py-3 bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 rounded-lg text-sm break-all text-slate-900 dark:text-slate-100">{{ session('jeton_cree') }}</code>
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('jeton-clair').textContent.trim()); this.textContent = 'Copié';"
                        class="px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition"
                    >
                        Copier
                    </button>
                </div>
            </div>
        @endif

        <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Créer un jeton</h2>
            <form action="{{ route('settings.api.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="nom" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Nom du jeton
                    </label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="{{ old('nom') }}"
                        required
                        maxlength="60"
                        placeholder="Tableau de bord interne"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pour vous souvenir de ce qui l'utilise.</p>
                </div>

                <div>
                    <label for="expiration_jours" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Validité (jours)
                    </label>
                    <input
                        type="number"
                        id="expiration_jours"
                        name="expiration_jours"
                        value="{{ old('expiration_jours') }}"
                        min="1"
                        max="730"
                        placeholder="Illimitée si vide"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                </div>

                <button type="submit" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Créer le jeton
                </button>
            </form>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                Jetons existants
                <span class="text-sm font-normal text-slate-500 dark:text-slate-400">({{ $jetons->count() }} / {{ $maximum }})</span>
            </h2>

            @if($jetons->isEmpty())
                <p class="text-slate-500 dark:text-slate-400">Aucun jeton pour le moment.</p>
            @else
                <ul class="space-y-3">
                    @foreach($jetons as $jeton)
                        <li class="p-4 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 flex flex-wrap items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white">
                                    {{ $jeton->nom }}
                                    @if($jeton->estExpire())
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 rounded-full">expiré</span>
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 font-mono">{{ \App\Models\ApiToken::PREFIXE }}{{ $jeton->apercu }}…</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Créé le {{ $jeton->created_at->format('d/m/Y') }} ·
                                    {{ $jeton->derniere_utilisation_at
                                        ? 'dernier appel le '.$jeton->derniere_utilisation_at->format('d/m/Y à H:i')
                                        : 'jamais utilisé' }}
                                    @if($jeton->expire_at)
                                        · expire le {{ $jeton->expire_at->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                            <form action="{{ route('settings.api.destroy', $jeton->id) }}" method="POST" onsubmit="return confirm('Révoquer ce jeton ? Les appels qui l\'utilisent cesseront de fonctionner.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    Révoquer
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
