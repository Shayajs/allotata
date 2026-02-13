@extends('admin.layout')

@section('title', 'Édition Module: ' . $module->titre)
@section('header', 'Édition du Module')
@section('subheader', $module->titre)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <a 
            href="{{ route('admin.courses.index') }}"
            class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300"
        >
            ← Retour aux modules
        </a>
        <div class="flex items-center gap-3">
            <button 
                onclick="openBulkActionModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold rounded-lg transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Commandes IA
            </button>
            <button 
                onclick="openBulkFillModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Remplissage IA
            </button>
            <button 
                onclick="openLessonModal()"
                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
            >
                ➕ Nouvelle Leçon
            </button>
        </div>
    </div>

    <!-- Informations du module -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations du module</h2>
        <form method="POST" action="{{ route('admin.courses.modules.update', $module) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Titre *</label>
                    <input type="text" name="titre" value="{{ old('titre', $module->titre) }}" required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ordre</label>
                    <input type="number" name="ordre" value="{{ old('ordre', $module->ordre) }}" min="0"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">{{ old('description', $module->description) }}</textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Image</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    @if($module->image_path)
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Image actuelle: <a href="{{ asset('storage/' . $module->image_path) }}" target="_blank" class="text-green-600 hover:underline">Voir</a></p>
                    @endif
                </div>

                @php
                    $currentVideoUrl = old('video_url', $module->video_url);
                    $hasVideo = !empty($currentVideoUrl);
                    $isInternalVideo = $hasVideo && !str_starts_with($currentVideoUrl, 'http');
                    $currentVideoType = $isInternalVideo ? 'internal' : 'external';
                @endphp
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Vidéo de présentation</label>
                    
                    {{-- Type selector --}}
                    <div class="flex items-center gap-4 mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="video_type" value="none" {{ !$hasVideo ? 'checked' : '' }}
                                class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300 dark:border-slate-600"
                                onchange="toggleVideoType('none')">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Aucune</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="video_type" value="external" {{ $hasVideo && !$isInternalVideo ? 'checked' : '' }}
                                class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300 dark:border-slate-600"
                                onchange="toggleVideoType('external')">
                            <span class="text-sm text-slate-700 dark:text-slate-300">URL externe</span>
                            <span class="text-xs text-slate-400">(YouTube, Dailymotion, Vimeo...)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="video_type" value="internal" {{ $isInternalVideo ? 'checked' : '' }}
                                class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300 dark:border-slate-600"
                                onchange="toggleVideoType('internal')">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Fichier interne</span>
                            <span class="text-xs text-slate-400">(lecteur Allotata)</span>
                        </label>
                    </div>

                    {{-- External URL input --}}
                    <div id="video-external-field" class="{{ ($hasVideo && !$isInternalVideo) ? '' : 'hidden' }}">
                        <input type="text" name="video_url" id="video-url-input" value="{{ !$isInternalVideo ? $currentVideoUrl : '' }}"
                            placeholder="https://youtube.com/watch?v=... ou https://dailymotion.com/video/..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            YouTube, Dailymotion, Vimeo (iframe auto) ou URL directe vers un fichier vidéo.
                        </p>
                    </div>

                    {{-- Internal upload input --}}
                    <div id="video-internal-field" class="{{ $isInternalVideo ? '' : 'hidden' }}">
                        <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Formats acceptés : MP4, WebM, OGG. Max 100 Mo. Sera lu avec le lecteur Allotata personnalisé.
                        </p>
                        @if($isInternalVideo)
                            <div class="mt-2 flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800/50">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-green-700 dark:text-green-400">Vidéo actuelle :</span>
                                <a href="{{ asset('storage/' . $currentVideoUrl) }}" target="_blank" class="text-sm text-green-600 hover:underline truncate">{{ basename($currentVideoUrl) }}</a>
                                <span class="text-xs text-slate-500 dark:text-slate-400 ml-auto whitespace-nowrap">Uploadez un nouveau fichier pour remplacer</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Page liée</label>
                    <select name="page_key"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">— Aucune —</option>
                        <optgroup label="Dashboard utilisateur">
                            <option value="dashboard.accueil" {{ old('page_key', $module->page_key) === 'dashboard.accueil' ? 'selected' : '' }}>Accueil</option>
                            <option value="dashboard.entreprises" {{ old('page_key', $module->page_key) === 'dashboard.entreprises' ? 'selected' : '' }}>Mes entreprises</option>
                            <option value="dashboard.abonnements" {{ old('page_key', $module->page_key) === 'dashboard.abonnements' ? 'selected' : '' }}>Abonnements</option>
                            <option value="dashboard.reservations" {{ old('page_key', $module->page_key) === 'dashboard.reservations' ? 'selected' : '' }}>Réservations</option>
                            <option value="dashboard.factures" {{ old('page_key', $module->page_key) === 'dashboard.factures' ? 'selected' : '' }}>Factures</option>
                            <option value="dashboard.messagerie" {{ old('page_key', $module->page_key) === 'dashboard.messagerie' ? 'selected' : '' }}>Messagerie</option>
                            <option value="dashboard.securite" {{ old('page_key', $module->page_key) === 'dashboard.securite' ? 'selected' : '' }}>Sécurité</option>
                        </optgroup>
                        <optgroup label="Dashboard entreprise">
                            <option value="entreprise.accueil" {{ old('page_key', $module->page_key) === 'entreprise.accueil' ? 'selected' : '' }}>Accueil entreprise</option>
                            <option value="entreprise.agenda" {{ old('page_key', $module->page_key) === 'entreprise.agenda' ? 'selected' : '' }}>Agenda</option>
                            <option value="entreprise.mes-services" {{ old('page_key', $module->page_key) === 'entreprise.mes-services' ? 'selected' : '' }}>Services</option>
                            <option value="entreprise.stock" {{ old('page_key', $module->page_key) === 'entreprise.stock' ? 'selected' : '' }}>Stock</option>
                            <option value="entreprise.commandes" {{ old('page_key', $module->page_key) === 'entreprise.commandes' ? 'selected' : '' }}>Commandes</option>
                            <option value="entreprise.equipe" {{ old('page_key', $module->page_key) === 'entreprise.equipe' ? 'selected' : '' }}>Équipe</option>
                            <option value="entreprise.reservations" {{ old('page_key', $module->page_key) === 'entreprise.reservations' ? 'selected' : '' }}>Réservations</option>
                            <option value="entreprise.factures" {{ old('page_key', $module->page_key) === 'entreprise.factures' ? 'selected' : '' }}>Factures</option>
                            <option value="entreprise.finances" {{ old('page_key', $module->page_key) === 'entreprise.finances' ? 'selected' : '' }}>Finances</option>
                            <option value="entreprise.statistiques" {{ old('page_key', $module->page_key) === 'entreprise.statistiques' ? 'selected' : '' }}>Statistiques</option>
                            <option value="entreprise.outils" {{ old('page_key', $module->page_key) === 'entreprise.outils' ? 'selected' : '' }}>Outils</option>
                            <option value="entreprise.messagerie" {{ old('page_key', $module->page_key) === 'entreprise.messagerie' ? 'selected' : '' }}>Messagerie</option>
                            <option value="entreprise.fidelisation" {{ old('page_key', $module->page_key) === 'entreprise.fidelisation' ? 'selected' : '' }}>Fidélisation</option>
                            <option value="entreprise.abonnements" {{ old('page_key', $module->page_key) === 'entreprise.abonnements' ? 'selected' : '' }}>Abonnements</option>
                            <option value="entreprise.parametres" {{ old('page_key', $module->page_key) === 'entreprise.parametres' ? 'selected' : '' }}>Paramètres</option>
                        </optgroup>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center gap-3 mt-8">
                        <input type="checkbox" name="est_actif" value="1" {{ $module->est_actif ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Module actif</span>
                    </label>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des leçons avec drag & drop -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Leçons (glissez-déposez pour réorganiser)
        </h2>
        
        <div id="lessons-list" class="space-y-4">
            @foreach($module->lessons as $lesson)
                <div 
                    class="lesson-item bg-slate-50 dark:bg-slate-700 rounded-lg p-4 border border-slate-200 dark:border-slate-600 cursor-move hover:shadow-md transition-shadow"
                    data-lesson-id="{{ $lesson->id }}"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center cursor-move">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $lesson->titre }}</h3>
                                    @if($lesson->isQuiz())
                                        <span class="px-2 py-0.5 text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded">Quiz</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded">Cours</span>
                                    @endif
                                </div>
                                @if($lesson->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1">{{ $lesson->description }}</p>
                                @endif
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Ordre: {{ $lesson->ordre }} 
                                    @if($lesson->isQuiz() && $lesson->quizQuestions->count() > 0)
                                        • {{ $lesson->quizQuestions->count() }} question{{ $lesson->quizQuestions->count() > 1 ? 's' : '' }}
                                        • {{ $lesson->points_quiz }} points
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 ml-4">
                            <a 
                                href="{{ route('admin.courses.lessons.edit', $lesson) }}"
                                class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
                            >
                                Modifier
                            </a>
                            <form method="POST" action="{{ route('admin.courses.lessons.destroy', ['module' => $module, 'lesson' => $lesson]) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette leçon ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($module->lessons->count() === 0)
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                Aucune leçon. Créez votre première leçon pour commencer.
            </p>
        @endif
    </div>

    <!-- Modal création/édition leçon -->
    <div id="lesson-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="overflow-y: auto;" onclick="if(event.target === this) closeLessonModal()">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-4xl p-6 my-8 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 id="lesson-modal-title" class="text-xl font-bold text-slate-900 dark:text-white">Nouvelle Leçon</h3>
                <button onclick="closeLessonModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="lesson-form" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="lesson-form-method"></div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Titre *</label>
                            <input type="text" name="titre" id="lesson-titre" required
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Type *</label>
                            <select name="type" id="lesson-type" required onchange="toggleQuizFields()"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="course">Cours</option>
                                <option value="quiz">Quiz</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description</label>
                        <textarea name="description" id="lesson-description" rows="3"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            placeholder="Brève description de la leçon (optionnel)"
                        ></textarea>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Le contenu complet sera édité après la création via l'éditeur visuel.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Ordre</label>
                            <input type="number" name="ordre" id="lesson-ordre" min="0"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        
                        <div id="points-field" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Points quiz</label>
                            <input type="number" name="points_quiz" id="lesson-points" min="0" value="0"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Image</label>
                            <input type="file" name="image" accept="image/*"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="est_actif" value="1" id="lesson-actif" checked
                                class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Leçon active</span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Créer et éditer
                    </button>
                    <button type="button" onclick="closeLessonModal()" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modale Remplissage IA --}}
    @include('admin.courses._bulk-fill-modal', [
        'bulkFillMode' => 'module',
        'bulkFillTargetId' => $module->id,
        'bulkFillContext' => [
            'module_titre' => $module->titre,
            'module_description' => $module->description,
            'existing_lessons' => $module->lessons->pluck('titre')->toArray(),
        ],
    ])

    {{-- Modale Commandes IA Bulk --}}
    @php
        $moduleForBulk = $module->loadMissing('lessons.quizQuestions');
    @endphp
    @include('admin.courses._bulk-action-modal', [
        'bulkActionContext' => [
            'modules' => collect([$moduleForBulk]),
            'all_lessons' => $moduleForBulk->lessons,
            'all_questions' => $moduleForBulk->lessons->flatMap->quizQuestions,
        ],
    ])
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    let currentLessonId = null;

    // Drag & drop pour les leçons
    document.addEventListener('DOMContentLoaded', function() {
        const lessonsList = document.getElementById('lessons-list');
        if (lessonsList) {
            new Sortable(lessonsList, {
                animation: 150,
                handle: '.lesson-item',
                onEnd: async function(evt) {
                    const order = Array.from(lessonsList.children).map((item, index) => {
                        return item.dataset.lessonId;
                    });

                    try {
                        const response = await fetch('{{ route("admin.courses.lessons.order", $module) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ order: order })
                        });

                        const data = await response.json();
                        if (data.success) {
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Erreur lors de la mise à jour de l\'ordre');
                    }
                }
            });
        }
    });

    // Fonction pour ouvrir la modal de création de leçon
    function openLessonModal() {
        const modal = document.getElementById('lesson-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.getElementById('lesson-modal-title').textContent = 'Nouvelle Leçon';
            document.getElementById('lesson-form').action = '{{ route("admin.courses.lessons.store", $module) }}';
            document.getElementById('lesson-form-method').innerHTML = '';
            currentLessonId = null;
            resetLessonForm();
        }
    }

    function closeLessonModal() {
        const modal = document.getElementById('lesson-modal');
        if (modal) {
            modal.classList.add('hidden');
            resetLessonForm();
        }
    }

    function resetLessonForm() {
        const form = document.getElementById('lesson-form');
        if (form) {
            form.reset();
        }
        
        const titre = document.getElementById('lesson-titre');
        const description = document.getElementById('lesson-description');
        const type = document.getElementById('lesson-type');
        const ordre = document.getElementById('lesson-ordre');
        const points = document.getElementById('lesson-points');
        const actif = document.getElementById('lesson-actif');
        
        if (titre) titre.value = '';
        if (description) description.value = '';
        if (type) type.value = 'course';
        if (ordre) ordre.value = '{{ ($module->lessons->count() > 0) ? $module->lessons->max("ordre") + 1 : 0 }}';
        if (points) points.value = '0';
        if (actif) actif.checked = true;
        
        toggleQuizFields();
    }

    function toggleQuizFields() {
        const type = document.getElementById('lesson-type').value;
        const pointsField = document.getElementById('points-field');
        if (type === 'quiz') {
            pointsField.classList.remove('hidden');
        } else {
            pointsField.classList.add('hidden');
        }
    }

</script>
@endpush
