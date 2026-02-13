<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Éditeur - {{ $lesson->titre }} - Allo Tata</title>
    
    @vite(['resources/css/app.css', 'resources/js/course-lesson-editor.js'])
    @include('partials.theme-script')
    
    {{-- Media Library CSS et JS --}}
    <link href="{{ Vite::asset('resources/css/media-library.css') }}" rel="stylesheet">
    <script src="{{ Vite::asset('resources/js/media-library.js') }}"></script>
    
    {{-- SortableJS pour le drag & drop --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    
    {{-- Quill.js pour l'éditeur de texte riche --}}
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <style>
        /* Styles spécifiques à l'éditeur de cours */
        html, body {
            overflow: hidden;
            height: 100%;
        }
        
        /* Forcer le scroll sur le conteneur des blocs */
        #course-preview {
            display: flex;
            flex-direction: column;
            overflow: visible !important;
        }
        
        #blocks-container {
            flex: 1;
            min-height: 0;
            overflow-y: auto !important;
            overflow-x: hidden;
        }
        
        /* Force le texte blanc en mode sombre */
        .dark .course-preview,
        .dark .course-preview *,
        .dark .course-preview .prose,
        .dark .course-preview .prose *,
        .dark .course-preview .course-block-content,
        .dark .course-preview .course-block-content * {
            color: #ffffff !important;
        }
        
        /* Exceptions pour les liens */
        .dark .course-preview a,
        .dark .course-preview .prose a {
            color: #4ade80 !important;
        }
        
        /* Mode AlloTata */
        .allotata-text {
            font-weight: 900 !important;
            background: linear-gradient(135deg, #22c55e 0%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
        
        .dark .allotata-text {
            background: linear-gradient(135deg, #4ade80 0%, #fb923c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Quill Editor styles */
        .dark .ql-editor {
            color: #ffffff !important;
            background: #1e293b;
        }
        
        .dark .ql-toolbar {
            background: #1e293b;
            border-color: #475569;
        }
        
        .dark .ql-container {
            border-color: #475569;
            background: #1e293b;
        }
        
        .ql-toolbar .ql-allotata {
            width: auto;
            padding: 0 8px;
            font-weight: 900;
            border: none !important;
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-900">
    {{-- Barre d'outils éditeur --}}
    <header class="course-editor-toolbar">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.courses.module.edit', $lesson->module) }}" class="flex items-center gap-2 text-slate-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden sm:inline text-sm">Retour au module</span>
            </a>
            
            <div class="h-6 w-px bg-slate-600"></div>
            
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="font-semibold text-white text-sm">{{ $lesson->titre }}</h1>
                    <p class="text-xs text-slate-400">Éditeur de cours</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Indicateur de statut --}}
            @if($lesson->isPublished())
                <span class="course-status-badge published">
                    <span class="hidden sm:inline">Publié le {{ $lesson->published_at->format('d/m/Y') }}</span>
                    <span class="sm:hidden">Publié</span>
                </span>
            @else
                <span class="course-status-badge draft">
                    Brouillon
                </span>
            @endif
            
            {{-- Indicateur de sauvegarde --}}
            <div id="save-status" class="course-save-status saved">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Sauvegardé</span>
            </div>
            
            {{-- Bouton Remplissage IA --}}
            <button 
                onclick="openBulkFillModal()"
                class="hidden sm:flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-lg transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="hidden md:inline">Remplissage IA</span>
            </button>
            
            {{-- Bouton aperçu --}}
            <a href="{{ route('courses.lesson', ['module' => $lesson->module, 'lesson' => $lesson]) }}" 
               target="_blank"
               class="hidden sm:flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Aperçu
            </a>
            
            {{-- Bouton sauvegarder comme brouillon --}}
            <button 
                id="save-draft-btn"
                type="button"
                class="px-4 py-2 text-sm font-medium text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition course-action-button"
            >
                <span class="hidden sm:inline">Enregistrer comme brouillon</span>
                <span class="sm:hidden">Sauvegarder</span>
            </button>
            
            {{-- Bouton publier --}}
            <button 
                id="publish-btn"
                type="button"
                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-700 hover:to-orange-600 rounded-lg transition course-action-button publish"
            >
                Publier
            </button>
            
            {{-- Toggle sidebar --}}
            <button 
                id="toggle-sidebar-btn"
                type="button" 
                class="p-2 text-slate-400 hover:text-white transition lg:hidden"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <button 
                id="toggle-sidebar-btn-desktop"
                type="button" 
                class="hidden lg:flex p-2 text-slate-400 hover:text-white transition" 
                title="Masquer/Afficher la sidebar"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </header>
    
    {{-- Zone de preview/édition --}}
    <main id="course-preview" class="course-preview">
        {{-- Zone de drag & drop pour les blocs --}}
        <div id="blocks-container" class="min-h-full p-8">
            @if(empty($lesson->contenu_blocks_json) || count($lesson->contenu_blocks_json) === 0)
                <div class="text-center py-20" style="color: #64748b;">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <p class="text-lg mb-2 font-medium" style="color: #475569;">Commencez à éditer votre cours</p>
                    <p class="text-sm" style="color: #64748b;">Ajoutez des blocs depuis la sidebar à droite</p>
                </div>
            @endif
        </div>
    </main>
    
    {{-- Sidebar droite --}}
    <aside id="editor-sidebar" class="course-editor-sidebar">
        {{-- Tabs --}}
        <div class="flex border-b border-slate-700 mb-4">
            <button 
                onclick="switchSidebarTab('blocks')"
                class="flex-1 py-2 text-center text-sm font-medium text-slate-300 border-b-2 border-green-500 transition sidebar-tab active"
                data-tab="blocks"
            >
                Blocs
            </button>
            <button 
                onclick="switchSidebarTab('properties')"
                class="flex-1 py-2 text-center text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 transition sidebar-tab"
                data-tab="properties"
            >
                Propriétés
            </button>
            <button 
                onclick="switchSidebarTab('lesson')"
                class="flex-1 py-2 text-center text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 transition sidebar-tab"
                data-tab="lesson"
            >
                Leçon
            </button>
        </div>

        {{-- Tab: Ajouter des blocs --}}
        <div id="tab-blocks" class="sidebar-tab-content active">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Ajouter un bloc</h3>
            
            <div class="mb-6">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Blocs génériques</h4>
                <div class="course-blocks-list">
                    <button data-add-block="text" class="course-block-type" title="Texte riche">
                        <div class="course-block-type-icon">📝</div>
                        <span>Texte</span>
                    </button>
                    <button data-add-block="heading" class="course-block-type" title="Titre">
                        <div class="course-block-type-icon">📌</div>
                        <span>Titre</span>
                    </button>
                    <button data-add-block="image" class="course-block-type" title="Image">
                        <div class="course-block-type-icon">🖼️</div>
                        <span>Image</span>
                    </button>
                    <button data-add-block="video" class="course-block-type" title="Vidéo">
                        <div class="course-block-type-icon">🎥</div>
                        <span>Vidéo</span>
                    </button>
                    <button data-add-block="iframe" class="course-block-type" title="Iframe">
                        <div class="course-block-type-icon">🔗</div>
                        <span>Iframe</span>
                    </button>
                    <button data-add-block="gallery" class="course-block-type" title="Galerie">
                        <div class="course-block-type-icon">🖼️</div>
                        <span>Galerie</span>
                    </button>
                    <button data-add-block="columns" class="course-block-type" title="Colonnes">
                        <div class="course-block-type-icon">📑</div>
                        <span>Colonnes</span>
                    </button>
                    <button data-add-block="divider" class="course-block-type" title="Séparateur">
                        <div class="course-block-type-icon">➖</div>
                        <span>Séparateur</span>
                    </button>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Blocs spécifiques cours</h4>
                <div class="course-blocks-list">
                    <button data-add-block="code" class="course-block-type" title="Code">
                        <div class="course-block-type-icon">💻</div>
                        <span>Code</span>
                    </button>
                    <button data-add-block="callout" class="course-block-type" title="Encadré">
                        <div class="course-block-type-icon">💡</div>
                        <span>Encadré</span>
                    </button>
                    <button data-add-block="steps" class="course-block-type" title="Étapes">
                        <div class="course-block-type-icon">📋</div>
                        <span>Étapes</span>
                    </button>
                    <button data-add-block="checklist" class="course-block-type" title="Checklist">
                        <div class="course-block-type-icon">✅</div>
                        <span>Checklist</span>
                    </button>
                    <button data-add-block="exercise" class="course-block-type" title="Exercice">
                        <div class="course-block-type-icon">✍️</div>
                        <span>Exercice</span>
                    </button>
                    <button data-add-block="quiz_block" class="course-block-type" title="Question quiz">
                        <div class="course-block-type-icon">❓</div>
                        <span>Quiz</span>
                    </button>
                    <button data-add-block="embed" class="course-block-type" title="Document">
                        <div class="course-block-type-icon">📄</div>
                        <span>Document</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tab: Propriétés du bloc --}}
        <div id="tab-properties" class="sidebar-tab-content" style="display: none;">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Propriétés du bloc</h3>
            <div id="block-properties">
                <p class="text-slate-400 text-center py-8 text-sm">Sélectionnez un bloc pour voir ses propriétés</p>
            </div>
        </div>

        {{-- Tab: Paramètres de la leçon --}}
        <div id="tab-lesson" class="sidebar-tab-content" style="display: none;">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Paramètres de la leçon</h3>
            
            <form id="lesson-settings-form" class="space-y-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-2">Titre *</label>
                    <input 
                        type="text" 
                        id="lesson-title"
                        value="{{ old('titre', $lesson->titre) }}"
                        required
                        class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs text-slate-400 mb-2">Description</label>
                    <textarea 
                        id="lesson-description"
                        rows="3"
                        class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm resize-none"
                    >{{ old('description', $lesson->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs text-slate-400 mb-2">Type *</label>
                    <select 
                        id="lesson-type"
                        required
                        onchange="toggleQuizFields()"
                        class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm"
                    >
                        <option value="course" {{ $lesson->type === 'course' ? 'selected' : '' }}>Cours</option>
                        <option value="quiz" {{ $lesson->type === 'quiz' ? 'selected' : '' }}>Quiz</option>
                    </select>
                </div>

                <div id="quiz-fields" class="{{ $lesson->type === 'quiz' ? '' : 'hidden' }}">
                    <label class="block text-xs text-slate-400 mb-2">Points quiz</label>
                    <input 
                        type="number" 
                        id="lesson-points"
                        value="{{ old('points_quiz', $lesson->points_quiz) }}"
                        min="0"
                        class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs text-slate-400 mb-2">Ordre</label>
                    <input 
                        type="number" 
                        id="lesson-ordre"
                        value="{{ old('ordre', $lesson->ordre) }}"
                        min="0"
                        class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Page liée</label>
                    <select id="lesson-page-key"
                        class="w-full px-3 py-1.5 text-sm border border-slate-600 rounded-lg bg-slate-700 text-white focus:ring-green-500 focus:border-green-500">
                        <option value="">— Aucune —</option>
                        <optgroup label="Dashboard utilisateur">
                            <option value="dashboard.accueil" {{ $lesson->page_key === 'dashboard.accueil' ? 'selected' : '' }}>Accueil</option>
                            <option value="dashboard.entreprises" {{ $lesson->page_key === 'dashboard.entreprises' ? 'selected' : '' }}>Mes entreprises</option>
                            <option value="dashboard.reservations" {{ $lesson->page_key === 'dashboard.reservations' ? 'selected' : '' }}>Réservations</option>
                            <option value="dashboard.factures" {{ $lesson->page_key === 'dashboard.factures' ? 'selected' : '' }}>Factures</option>
                            <option value="dashboard.messagerie" {{ $lesson->page_key === 'dashboard.messagerie' ? 'selected' : '' }}>Messagerie</option>
                            <option value="dashboard.securite" {{ $lesson->page_key === 'dashboard.securite' ? 'selected' : '' }}>Sécurité</option>
                        </optgroup>
                        <optgroup label="Dashboard entreprise">
                            <option value="entreprise.accueil" {{ $lesson->page_key === 'entreprise.accueil' ? 'selected' : '' }}>Accueil</option>
                            <option value="entreprise.agenda" {{ $lesson->page_key === 'entreprise.agenda' ? 'selected' : '' }}>Agenda</option>
                            <option value="entreprise.mes-services" {{ $lesson->page_key === 'entreprise.mes-services' ? 'selected' : '' }}>Services</option>
                            <option value="entreprise.stock" {{ $lesson->page_key === 'entreprise.stock' ? 'selected' : '' }}>Stock</option>
                            <option value="entreprise.commandes" {{ $lesson->page_key === 'entreprise.commandes' ? 'selected' : '' }}>Commandes</option>
                            <option value="entreprise.equipe" {{ $lesson->page_key === 'entreprise.equipe' ? 'selected' : '' }}>Équipe</option>
                            <option value="entreprise.reservations" {{ $lesson->page_key === 'entreprise.reservations' ? 'selected' : '' }}>Réservations</option>
                            <option value="entreprise.factures" {{ $lesson->page_key === 'entreprise.factures' ? 'selected' : '' }}>Factures</option>
                            <option value="entreprise.finances" {{ $lesson->page_key === 'entreprise.finances' ? 'selected' : '' }}>Finances</option>
                            <option value="entreprise.statistiques" {{ $lesson->page_key === 'entreprise.statistiques' ? 'selected' : '' }}>Statistiques</option>
                            <option value="entreprise.outils" {{ $lesson->page_key === 'entreprise.outils' ? 'selected' : '' }}>Outils</option>
                            <option value="entreprise.messagerie" {{ $lesson->page_key === 'entreprise.messagerie' ? 'selected' : '' }}>Messagerie</option>
                            <option value="entreprise.fidelisation" {{ $lesson->page_key === 'entreprise.fidelisation' ? 'selected' : '' }}>Fidélisation</option>
                            <option value="entreprise.parametres" {{ $lesson->page_key === 'entreprise.parametres' ? 'selected' : '' }}>Paramètres</option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            id="lesson-actif"
                            {{ $lesson->est_actif ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-slate-600 text-green-600 focus:ring-green-500 bg-slate-700"
                        >
                        <span class="text-sm text-slate-300">Leçon active</span>
                    </label>
                </div>
            </form>
        </div>
    </aside>

    {{-- Scripts --}}
    @vite(['resources/js/app.js', 'resources/js/course-lesson-editor.js'])
    
    <script>
        // Ajuster le padding-top du conteneur de blocs selon la hauteur de la toolbar sur mobile
        function adjustBlocksContainerPadding() {
            const toolbar = document.querySelector('.course-editor-toolbar');
            const blocksContainer = document.getElementById('blocks-container');
            
            if (toolbar && blocksContainer && window.innerWidth <= 768) {
                const toolbarHeight = toolbar.offsetHeight;
                blocksContainer.style.paddingTop = (toolbarHeight + 16) + 'px';
            }
        }
        
        // Ajuster au chargement et au redimensionnement
        window.addEventListener('resize', adjustBlocksContainerPadding);
        
        // Initialiser l'éditeur après le chargement (UNE SEULE FOIS)
        let courseEditor = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Ajuster le padding pour mobile
            adjustBlocksContainerPadding();
            // Attendre que CourseLessonEditor soit disponible
            if (typeof CourseLessonEditor === 'undefined') {
                console.error('CourseLessonEditor non trouvé. Vérifiez que le fichier JS est bien chargé.');
                return;
            }
            
            // Vérifier qu'on n'a pas déjà initialisé
            if (window.courseEditor) {
                console.warn('CourseEditor déjà initialisé, on ignore cette seconde initialisation.');
                return;
            }
            
            const blocks = @json($lesson->getBlocks());
            
            courseEditor = new CourseLessonEditor({
                lessonId: {{ $lesson->id }},
                csrfToken: document.querySelector('meta[name="csrf-token"]').content,
                initialBlocks: blocks
            });
            
            // Exposer globalement pour les appels depuis les boutons
            window.courseEditor = courseEditor;
            
            // Initialiser toggle quiz fields
            toggleQuizFields();
        });

        // Switch entre les tabs de la sidebar
        function switchSidebarTab(tab) {
            // Désactiver tous les tabs
            document.querySelectorAll('.sidebar-tab').forEach(t => {
                t.classList.remove('active', 'text-green-400', 'border-green-500');
                t.classList.add('text-slate-400', 'border-transparent');
            });
            
            // Masquer tous les contenus de tabs
            document.querySelectorAll('.sidebar-tab-content').forEach(c => {
                c.classList.remove('active');
                c.style.display = 'none';
            });

            // Activer le tab sélectionné
            const tabBtn = document.querySelector(`[data-tab="${tab}"]`);
            const tabContent = document.getElementById(`tab-${tab}`);
            
            if (tabBtn && tabContent) {
                tabBtn.classList.add('active', 'text-green-400', 'border-green-500');
                tabBtn.classList.remove('text-slate-400', 'border-transparent');
                tabContent.classList.add('active');
                tabContent.style.display = 'block';
            }
        }
        
        // Exposer la fonction globalement
        window.switchSidebarTab = switchSidebarTab;

        // Toggle quiz fields
        function toggleQuizFields() {
            const typeEl = document.getElementById('lesson-type');
            const quizFields = document.getElementById('quiz-fields');
            if (!typeEl || !quizFields) return;
            
            const type = typeEl.value;
            if (type === 'quiz') {
                quizFields.classList.remove('hidden');
            } else {
                quizFields.classList.add('hidden');
            }
        }
    </script>

    {{-- Modale de sélection de fichiers (Médiathèque) --}}
    <div id="media-selector-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col">
            <!-- En-tête -->
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sélectionner un fichier</h2>
                <button id="close-selector-modal" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Contenu -->
            <div class="flex-1 overflow-hidden flex">
                <!-- Sidebar de la modale -->
                <div class="w-64 border-r border-slate-200 dark:border-slate-700 p-4 overflow-y-auto">
                    <div class="mb-4">
                        <input 
                            type="text" 
                            id="modal-search"
                            placeholder="Rechercher..."
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                        >
                    </div>
                    <div id="modal-folder-tree" class="space-y-1">
                        <!-- Arborescence chargée dynamiquement -->
                    </div>
                </div>

                <!-- Zone de sélection -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                        <nav id="modal-breadcrumb" class="flex items-center gap-2 text-sm">
                            <!-- Breadcrumb chargé dynamiquement -->
                        </nav>
                    </div>
                    <div id="modal-media-grid" class="flex-1 p-4 overflow-y-auto grid grid-cols-4 gap-4">
                        <!-- Fichiers chargés dynamiquement -->
                    </div>
                </div>
            </div>

            <!-- Pied -->
            <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                <button id="cancel-selector" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    Annuler
                </button>
                <button id="confirm-selector" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    Sélectionner
                </button>
            </div>
        </div>
    </div>

    {{-- Modale Remplissage IA --}}
    @include('admin.courses._bulk-fill-modal', [
        'bulkFillMode' => 'lesson',
        'bulkFillTargetId' => $lesson->id,
        'bulkFillContext' => [
            'lesson_titre' => $lesson->titre,
            'lesson_description' => $lesson->description,
            'module_titre' => $lesson->module->titre ?? '',
        ],
    ])
</body>
</html>
