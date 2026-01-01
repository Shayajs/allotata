<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition - {{ $entreprise->nom }} - Site Web</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <!-- Barre d'édition en haut -->
    <div class="bg-gradient-to-r from-green-600 to-green-500 text-white py-3 px-4 shadow-lg">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="font-semibold">✏️ Mode édition</span>
                <span class="text-sm opacity-90">Vous modifiez votre site web vitrine</span>
            </div>
            <div class="flex items-center gap-3">
                @if(!empty($entreprise->slug_web))
                    <a 
                        href="{{ route('site-web.show', ['slug' => $entreprise->slug_web, 'mode' => 'view']) }}" 
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition text-sm font-medium"
                    >
                        👁️ Voir en public
                    </a>
                @endif
                <a 
                    href="{{ route('dashboard') }}" 
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition text-sm font-medium"
                >
                    Retour au dashboard
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-4">
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-4">
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                @foreach($errors->all() as $error)
                    <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $aSiteWebActif = $entreprise->aSiteWebActif();
        $estVerifiee = $entreprise->est_verifiee;
    @endphp

    @if(!$aSiteWebActif || !$estVerifiee)
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-4">
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-yellow-800 dark:text-yellow-400 mb-2">⚠️ Votre site n'est pas encore accessible publiquement</p>
                        <ul class="text-sm text-yellow-700 dark:text-yellow-500 space-y-1">
                            @if(!$aSiteWebActif)
                                <li>• L'abonnement "Site Web Vitrine" n'est pas actif. <a href="{{ route('settings.index') }}" class="underline font-medium">Activez-le dans les paramètres</a></li>
                            @endif
                            @if(!$estVerifiee)
                                <li>• Votre entreprise n'est pas encore vérifiée par l'administration.</li>
                            @endif
                        </ul>
                        <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-2">En tant que propriétaire, vous pouvez toujours configurer votre site, mais il ne sera visible publiquement qu'une fois ces conditions remplies.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Formulaire d'édition -->
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6">
        <form action="{{ route('site-web.update', $entreprise->slug_web ?? $entreprise->slug) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Informations de base -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Informations de base</h2>
                
                <div class="space-y-6">
                    <!-- Slug web -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            URL de votre site (slug) *
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 dark:text-slate-400">/w/</span>
                            <input 
                                type="text" 
                                name="slug_web" 
                                value="{{ old('slug_web', $entreprise->slug_web ?? $entreprise->slug) }}"
                                required
                                pattern="[a-z0-9-]+"
                                placeholder="mon-entreprise"
                                class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Uniquement des lettres minuscules, chiffres et tirets. Votre site sera accessible à : <strong>{{ url('/w/') }}<span id="slug-preview">{{ old('slug_web', $entreprise->slug_web ?? $entreprise->slug) }}</span></strong>
                        </p>
                        @if(empty($entreprise->slug_web))
                            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                                ⚠️ Vous devez définir un slug pour que votre site soit accessible publiquement. Par défaut, votre slug d'entreprise est utilisé.
                            </p>
                        @endif
                        @error('slug_web')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phrase d'accroche -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Phrase d'accroche
                        </label>
                        <input 
                            type="text" 
                            name="phrase_accroche" 
                            value="{{ old('phrase_accroche', $entreprise->phrase_accroche) }}"
                            maxlength="500"
                            placeholder="Votre phrase d'accroche qui apparaîtra sous le nom de l'entreprise"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Cette phrase apparaîtra en dessous du nom de votre entreprise sur votre site vitrine.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Aperçu du site -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Aperçu de votre site</h2>
                
                <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-8 bg-slate-50 dark:bg-slate-700/50">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        💡 <strong>Note :</strong> Le contenu de votre site (logo, description, photos, etc.) est géré depuis les paramètres de votre entreprise. 
                        Les modifications que vous faites ici concernent uniquement la configuration de la page vitrine.
                    </p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Pour modifier le logo, la description, les photos de réalisations, etc., rendez-vous dans les paramètres de votre entreprise.
                    </p>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-between">
                <a 
                    href="{{ route('dashboard') }}" 
                    class="px-6 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg transition"
                >
                    Annuler
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                >
                    💾 Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <script>
        // Mise à jour de l'aperçu du slug en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const slugInput = document.querySelector('input[name="slug_web"]');
            const preview = document.getElementById('slug-preview');
            
            if (slugInput && preview) {
                slugInput.addEventListener('input', function(e) {
                    preview.textContent = e.target.value || 'votre-slug';
                });
            }
        });
    </script>
</body>
</html>
