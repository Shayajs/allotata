<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis - {{ $entreprise->nom }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
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
            <form action="{{ $avisExistant ? route('avis.update', [$entreprise->slug, $avisExistant->id]) : route('avis.store', $entreprise->slug) }}" method="POST" enctype="multipart/form-data">
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

                    <!-- Photos -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            📸 Photos de votre expérience (optionnel)
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                            Ajoutez jusqu'à 5 photos qui seront visibles dans les "Dernières réalisations" de l'entreprise.
                        </p>

                        @if($avisExistant && $avisExistant->photos->count() > 0)
                            <div class="mb-4">
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Photos existantes :</p>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="photos-existantes">
                                    @foreach($avisExistant->photos as $photo)
                                        <div class="relative group" id="photo-{{ $photo->id }}">
                                            <img 
                                                src="{{ asset('media/' . $photo->photo_path) }}" 
                                                alt="Photo avis"
                                                class="w-full h-32 object-cover rounded-lg border border-slate-200 dark:border-slate-700"
                                            >
                                            <button 
                                                type="button" 
                                                onclick="supprimerPhoto({{ $photo->id }})"
                                                class="absolute top-2 right-2 p-1 bg-red-500 hover:bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition"
                                                title="Supprimer cette photo"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="photos-a-supprimer-container"></div>
                            </div>
                        @endif

                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-6 text-center hover:border-green-500 dark:hover:border-green-400 transition cursor-pointer" onclick="document.getElementById('photos-input').click()">
                            <svg class="mx-auto h-12 w-12 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-slate-600 dark:text-slate-400">
                                Cliquez ou glissez-déposez vos photos ici
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                                JPG, PNG, GIF ou WebP • Max 5 Mo par image
                            </p>
                        </div>
                        <input 
                            type="file" 
                            name="photos[]" 
                            id="photos-input"
                            multiple
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            class="hidden"
                            onchange="previewPhotos(this)"
                        >
                        
                        <!-- Prévisualisation des nouvelles photos -->
                        <div id="photos-preview" class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-4 hidden"></div>
                        
                        @error('photos')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('photos.*')
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

        // Prévisualisation des photos sélectionnées
        function previewPhotos(input) {
            const preview = document.getElementById('photos-preview');
            preview.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                preview.classList.remove('hidden');
                
                // Vérifier le nombre de fichiers (max 5)
                const maxFiles = 5;
                @if($avisExistant && $avisExistant->photos)
                    const existingPhotos = {{ $avisExistant->photos->count() }};
                @else
                    const existingPhotos = 0;
                @endif
                const photosASupprimer = document.querySelectorAll('input[name="photos_a_supprimer[]"]').length;
                const remainingSlots = maxFiles - existingPhotos + photosASupprimer;
                
                if (input.files.length > remainingSlots) {
                    alert(`Vous ne pouvez ajouter que ${remainingSlots} photo(s) supplémentaire(s).`);
                    input.value = '';
                    preview.classList.add('hidden');
                    return;
                }
                
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Prévisualisation" class="w-full h-32 object-cover rounded-lg border-2 border-green-500">
                            <span class="absolute top-2 left-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full">Nouveau</span>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                preview.classList.add('hidden');
            }
        }

        // Supprimer une photo existante
        function supprimerPhoto(photoId) {
            if (confirm('Voulez-vous supprimer cette photo ?')) {
                // Masquer la photo visuellement
                const photoElement = document.getElementById('photo-' + photoId);
                if (photoElement) {
                    photoElement.style.display = 'none';
                }
                
                // Ajouter un champ hidden pour marquer la photo comme à supprimer
                const container = document.getElementById('photos-a-supprimer-container');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'photos_a_supprimer[]';
                input.value = photoId;
                container.appendChild(input);
            }
        }

        // Drag and drop support
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        }

        function unhighlight() {
            dropZone.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const input = document.getElementById('photos-input');
            input.files = files;
            previewPhotos(input);
        }
    </script>
</body>
</html>

