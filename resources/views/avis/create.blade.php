<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis - {{ $entreprise->nom }}</title>
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
    <div class="max-w-4xl mx-auto py-12 px-6">
        <header class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($entreprise->logo)
                        <img 
                            src="{{ asset('media/' . $entreprise->logo) }}" 
                            alt="Logo {{ $entreprise->nom }}"
                            class="w-16 h-16 rounded-lg object-cover border-2 border-slate-200 dark:border-slate-700"
                        >
                    @endif
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                            {{ $entreprise->nom }}
                        </h1>
                        <p class="text-lg text-slate-600 dark:text-slate-400">
                            Laisser un avis
                        </p>
                    </div>
                </div>
                <a href="{{ route('public.entreprise', $entreprise->slug) }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour
                </a>
            </div>
        </header>

        @if($avisExistant)
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-yellow-800 dark:text-yellow-300">
                    Vous avez déjà laissé un avis pour cette entreprise. Vous pouvez le modifier ci-dessous.
                </p>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <form action="{{ $avisExistant ? route('avis.update', [$entreprise->slug, $avisExistant->id]) : route('avis.store', $entreprise->slug) }}" method="POST">
                @csrf
                @if($avisExistant)
                    @method('PUT')
                @endif

                <div class="space-y-6">
                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Votre note *
                        </label>
                        <div class="flex items-center gap-1" id="rating-container">
                            @for($i = 1; $i <= 5; $i++)
                                <button 
                                    type="button"
                                    class="rating-star text-4xl transition-all duration-150 cursor-pointer select-none"
                                    data-rating="{{ $i }}"
                                    onclick="setRating({{ $i }})"
                                >
                                    <span class="star-empty text-slate-300 dark:text-slate-600">☆</span>
                                    <span class="star-filled text-yellow-400 hidden">★</span>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="note" id="note-input" value="{{ old('note', $avisExistant->note ?? 0) }}" required>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" id="rating-text">
                            @if($avisExistant)
                                Note actuelle : {{ $avisExistant->note }}/5
                            @else
                                Cliquez sur une étoile pour noter
                            @endif
                        </p>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Commentaire -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Votre commentaire (optionnel)
                        </label>
                        <textarea 
                            name="commentaire" 
                            rows="5"
                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Partagez votre expérience avec cette entreprise..."
                        >{{ old('commentaire', $avisExistant->commentaire ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Maximum 1000 caractères
                        </p>
                        @error('commentaire')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Réservation liée (optionnel) -->
                    @if($reservations->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Lier à une réservation (optionnel)
                            </label>
                            <select 
                                name="reservation_id" 
                                class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                                <option value="">Aucune réservation</option>
                                @foreach($reservations as $reservation)
                                    <option value="{{ $reservation->id }}" {{ old('reservation_id', $avisExistant->reservation_id ?? '') == $reservation->id ? 'selected' : '' }}>
                                        {{ $reservation->date_reservation->format('d/m/Y à H:i') }} - {{ $reservation->type_service ?? 'Service' }} ({{ number_format($reservation->prix, 2, ',', ' ') }} €)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Boutons -->
                    <div class="flex gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button 
                            type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
                        >
                            {{ $avisExistant ? 'Modifier mon avis' : 'Publier mon avis' }}
                        </button>
                        <a 
                            href="{{ route('public.entreprise', $entreprise->slug) }}" 
                            class="px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Système de notation avec étoiles
        function setRating(rating) {
            document.getElementById('note-input').value = rating;
            document.getElementById('rating-text').textContent = `Note : ${rating}/5`;
            
            // Mettre à jour l'affichage des étoiles
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                const starRating = parseInt(star.dataset.rating);
                const empty = star.querySelector('.star-empty');
                const filled = star.querySelector('.star-filled');
                
                if (starRating <= rating) {
                    empty.classList.add('hidden');
                    filled.classList.remove('hidden');
                } else {
                    empty.classList.remove('hidden');
                    filled.classList.add('hidden');
                }
            });
        }

        // Initialiser avec la note existante si elle existe
        @if($avisExistant)
            setRating({{ $avisExistant->note }});
        @endif

        // Hover sur les étoiles
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                const stars = document.querySelectorAll('.rating-star');
                stars.forEach((s, index) => {
                    const starRating = parseInt(s.dataset.rating);
                    const empty = s.querySelector('.star-empty');
                    const filled = s.querySelector('.star-filled');
                    
                    if (starRating <= rating) {
                        empty.classList.add('hidden');
                        filled.classList.remove('hidden');
                    }
                });
            });
        });

        document.getElementById('rating-container').addEventListener('mouseleave', function() {
            const currentRating = parseInt(document.getElementById('note-input').value) || 0;
            if (currentRating > 0) {
                setRating(currentRating);
            } else {
                // Réinitialiser toutes les étoiles
                document.querySelectorAll('.rating-star').forEach(star => {
                    star.querySelector('.star-empty').classList.remove('hidden');
                    star.querySelector('.star-filled').classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>

