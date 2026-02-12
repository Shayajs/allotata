<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modifier FAQ - Admin</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        @include('admin.partials.nav')

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Modifier la FAQ
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">Modifiez cette question fréquemment posée</p>
                </div>
                <a href="{{ route('admin.faqs.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour à la liste
                </a>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <ul class="list-disc list-inside text-red-800 dark:text-red-400">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="question" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Question <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="question" 
                            id="question"
                            value="{{ old('question', $faq->question) }}"
                            required
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Ex: Comment puis-je créer un compte ?"
                        >
                    </div>

                    <div>
                        <label for="reponse" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Réponse <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="reponse" 
                            id="reponse"
                            rows="6"
                            required
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                            placeholder="Rédigez la réponse détaillée à cette question..."
                        >{{ old('reponse', $faq->reponse) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="categorie" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Catégorie
                            </label>
                            <input 
                                type="text" 
                                name="categorie" 
                                id="categorie"
                                value="{{ old('categorie', $faq->categorie) }}"
                                list="categories-list"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                placeholder="Ex: Compte, Réservation, Paiement..."
                            >
                            <datalist id="categories-list">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tapez pour créer ou sélectionner une catégorie existante</p>
                        </div>

                        <div>
                            <label for="ordre" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Ordre d'affichage
                            </label>
                            <input 
                                type="number" 
                                name="ordre" 
                                id="ordre"
                                value="{{ old('ordre', $faq->ordre) }}"
                                min="0"
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Les FAQs sont triées du plus petit au plus grand</p>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-3">
                            <input 
                                type="checkbox" 
                                name="est_actif" 
                                value="1"
                                {{ old('est_actif', $faq->est_actif) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                            >
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">FAQ active (visible sur le site)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette FAQ ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium transition">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Supprimer
                            </button>
                        </form>
                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.faqs.index') }}" class="px-6 py-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Info -->
            <div class="mt-6 p-4 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Créée le {{ $faq->created_at->format('d/m/Y à H:i') }} • Dernière modification le {{ $faq->updated_at->format('d/m/Y à H:i') }}</span>
                </div>
            </div>
        </div>
    </body>
</html>
