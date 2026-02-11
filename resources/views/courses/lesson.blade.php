@extends('layouts.user')

@section('title', $lesson->titre . ' - ' . $module->titre)

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">

    {{-- Header : breadcrumb + badges --}}
    <div class="pt-20 sm:pt-24 pb-4 px-4 sm:px-6 lg:px-8 2xl:px-12 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-5xl xl:max-w-6xl 2xl:max-w-7xl mx-auto">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-3 overflow-x-auto">
                <a href="{{ route('courses.index') }}" class="hover:text-green-600 dark:hover:text-green-400 transition whitespace-nowrap">
                    Apprendre
                </a>
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="{{ route('courses.module', $module) }}" class="hover:text-green-600 dark:hover:text-green-400 transition whitespace-nowrap truncate max-w-[120px] sm:max-w-none">
                    {{ $module->titre }}
                </a>
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-slate-900 dark:text-white font-medium truncate">{{ $lesson->titre }}</span>
            </nav>

            {{-- Titre + badges --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                {{-- Bouton retour mobile --}}
                <a href="{{ route('courses.module', $module) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition sm:hidden mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour au module
                </a>

                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900 dark:text-white flex-1 min-w-0">
                    {{ $lesson->titre }}
                </h1>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($lesson->isQuiz())
                        <span class="px-2.5 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full text-xs font-medium">
                            Quiz
                        </span>
                    @else
                        <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-xs font-medium">
                            Cours
                        </span>
                    @endif
                    @if($lessonProgress && $lessonProgress->completed_at)
                        <span class="px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-xs font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Complété
                        </span>
                    @endif
                </div>
            </div>

            @if($lesson->description)
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-2">
                    {{ $lesson->description }}
                </p>
            @endif
        </div>
    </div>

    {{-- Contenu principal avec sidebar desktop --}}
    <div class="max-w-7xl 2xl:max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 2xl:px-12 py-6 sm:py-8">
        <div class="flex gap-8 xl:gap-10 2xl:gap-12">

            {{-- Contenu principal --}}
            <div class="flex-1 min-w-0 max-w-4xl">

                {{-- Image de couverture --}}
                @if($lesson->image_path)
                    <div class="mb-6 sm:mb-8 rounded-xl overflow-hidden">
                        <img 
                            src="{{ asset('storage/' . $lesson->image_path) }}" 
                            alt="{{ $lesson->titre }}"
                            class="w-full h-48 sm:h-64 xl:h-80 object-cover"
                            loading="lazy"
                        >
                    </div>
                @endif

                {{-- Contenu riche du cours --}}
                @if($lesson->contenu_rich_html)
                    <div id="lesson-content" class="mb-8 sm:mb-10">
                        {!! $lesson->contenu_rich_html !!}
                    </div>
                @endif

                {{-- Quiz --}}
                @if($lesson->isQuiz() && $quizQuestions->count() > 0)
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-8">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6">Questions du quiz</h2>
                        <form id="quiz-form" class="space-y-6">
                            @csrf
                            @foreach($quizQuestions as $index => $question)
                                <div class="border-b border-slate-200 dark:border-slate-700 pb-6 last:border-0 last:pb-0">
                                    <div class="flex items-start justify-between mb-4 gap-3">
                                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white">
                                            Question {{ $index + 1 }}: {{ $question->question }}
                                        </h3>
                                        <span class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap flex-shrink-0">
                                            {{ $question->points }} pt{{ $question->points > 1 ? 's' : '' }}
                                        </span>
                                    </div>

                                    @if($question->type === 'multiple_choice')
                                        <div class="space-y-2">
                                            @foreach($question->getOptions() as $option)
                                                <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer min-h-[48px]">
                                                    <input 
                                                        type="radio" 
                                                        name="answers[{{ $question->id }}]" 
                                                        value="{{ $option }}"
                                                        class="w-4 h-4 text-green-600 focus:ring-green-500 flex-shrink-0"
                                                        {{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) && $lessonProgress->quiz_answers_json[$question->id] === $option ? 'checked' : '' }}
                                                        @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                                    >
                                                    <span class="text-slate-700 dark:text-slate-300 text-sm sm:text-base">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($question->type === 'true_false')
                                        <div class="space-y-2">
                                            <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer min-h-[48px]">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="1" class="w-4 h-4 text-green-600 focus:ring-green-500"
                                                    {{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) && $lessonProgress->quiz_answers_json[$question->id] == 1 ? 'checked' : '' }}
                                                    @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                                >
                                                <span class="text-slate-700 dark:text-slate-300">Vrai</span>
                                            </label>
                                            <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer min-h-[48px]">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="0" class="w-4 h-4 text-green-600 focus:ring-green-500"
                                                    {{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) && $lessonProgress->quiz_answers_json[$question->id] == 0 ? 'checked' : '' }}
                                                    @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                                >
                                                <span class="text-slate-700 dark:text-slate-300">Faux</span>
                                            </label>
                                        </div>
                                    @elseif($question->type === 'text')
                                        <textarea 
                                            name="answers[{{ $question->id }}]"
                                            rows="3"
                                            class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm sm:text-base"
                                            placeholder="Votre réponse..."
                                            @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                        >{{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) ? $lessonProgress->quiz_answers_json[$question->id] : '' }}</textarea>
                                    @endif
                                </div>
                            @endforeach

                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-4">
                                <div>
                                    @if($lessonProgress && $lessonProgress->completed_at)
                                        <p class="text-sm text-green-600 dark:text-green-400 font-medium">
                                            Score: {{ $lessonProgress->score }}% - {{ $lessonProgress->points_earned }} points gagnés
                                        </p>
                                    @endif
                                </div>
                                @if(!$lessonProgress || !$lessonProgress->completed_at)
                                    <button 
                                        type="submit"
                                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition min-h-[48px]"
                                    >
                                        Soumettre le quiz
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Boutons de navigation desktop --}}
                <div class="hidden sm:flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
                    <div>
                        @if($previousLesson && $previousLesson->isAccessibleBy($user))
                            <a 
                                href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $previousLesson]) }}"
                                class="inline-flex items-center gap-2 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                <span class="truncate max-w-[200px] xl:max-w-[300px]">{{ $previousLesson->titre }}</span>
                            </a>
                        @endif
                    </div>

                    <div>
                        @if(!$lesson->isQuiz())
                            @if(!$lessonProgress || !$lessonProgress->completed_at)
                                @if($user)
                                    <button 
                                        id="complete-lesson-btn"
                                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                                    >
                                        Marquer comme complété
                                    </button>
                                @endif
                            @else
                                <span class="px-6 py-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 font-semibold rounded-lg inline-flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Complété
                                </span>
                            @endif
                        @endif
                    </div>

                    <div>
                        @if($nextLesson && $nextLesson->isAccessibleBy($user))
                            <a 
                                href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $nextLesson]) }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                            >
                                <span class="truncate max-w-[200px] xl:max-w-[300px]">{{ $nextLesson->titre }}</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Espace en bas pour la barre mobile --}}
                <div class="h-20 sm:hidden"></div>
            </div>

            {{-- Sidebar droite : sommaire (desktop >= 1024px) --}}
            <aside class="hidden lg:block w-64 xl:w-72 2xl:w-80 flex-shrink-0">
                <div class="sticky top-24">
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 xl:p-5 max-h-[calc(100vh-8rem)] overflow-y-auto">
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                            Sommaire
                        </h3>
                        <nav id="toc-desktop" class="space-y-1">
                            {{-- Rempli dynamiquement par JS --}}
                            <p class="text-xs text-slate-400 dark:text-slate-500 italic">Chargement...</p>
                        </nav>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Barre de navigation mobile sticky bottom --}}
    <div class="sm:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 z-40 safe-area-bottom">
        <div class="flex items-center justify-between px-3 py-2">
            {{-- Précédent --}}
            <div class="w-12">
                @if($previousLesson && $previousLesson->isAccessibleBy($user))
                    <a href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $previousLesson]) }}" class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-green-100 dark:hover:bg-green-900/30 hover:text-green-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                @endif
            </div>

            {{-- Centre : bouton compléter ou statut --}}
            <div class="flex-1 text-center px-2">
                @if(!$lesson->isQuiz())
                    @if(!$lessonProgress || !$lessonProgress->completed_at)
                        @if($user)
                            <button 
                                id="complete-lesson-btn-mobile"
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 text-white text-sm font-semibold rounded-lg transition w-full max-w-[200px]"
                            >
                                Marquer complété
                            </button>
                        @endif
                    @else
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Complété
                        </span>
                    @endif
                @else
                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate block">{{ $lesson->titre }}</span>
                @endif
            </div>

            {{-- Suivant --}}
            <div class="w-12 flex justify-end">
                @if($nextLesson && $nextLesson->isAccessibleBy($user))
                    <a href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $nextLesson]) }}" class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Bouton flottant Sommaire (mobile) --}}
    <button 
        id="toc-fab"
        class="lg:hidden fixed bottom-20 right-4 z-40 w-12 h-12 bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg flex items-center justify-center transition"
        onclick="openTocDrawer()"
        aria-label="Ouvrir le sommaire"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
        </svg>
    </button>

    {{-- Drawer Sommaire (mobile) --}}
    <div id="toc-drawer-overlay" class="hidden fixed inset-0 bg-black/50 z-50 lg:hidden" onclick="closeTocDrawer()"></div>
    <div id="toc-drawer" class="fixed bottom-0 left-0 right-0 z-50 lg:hidden transform translate-y-full transition-transform duration-300 ease-out">
        <div class="bg-white dark:bg-slate-800 rounded-t-2xl shadow-2xl max-h-[70vh] flex flex-col">
            {{-- Handle --}}
            <div class="flex justify-center py-3">
                <div class="w-10 h-1 bg-slate-300 dark:bg-slate-600 rounded-full"></div>
            </div>
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Sommaire</h3>
                <button onclick="closeTocDrawer()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            {{-- Contenu --}}
            <nav id="toc-mobile" class="overflow-y-auto px-5 py-4 space-y-1">
                <p class="text-sm text-slate-400 italic">Chargement...</p>
            </nav>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // Table des matières auto-générée
    // ==========================================
    const lessonContent = document.getElementById('lesson-content');
    const tocDesktop = document.getElementById('toc-desktop');
    const tocMobile = document.getElementById('toc-mobile');

    if (lessonContent) {
        const headings = lessonContent.querySelectorAll('h1, h2, h3');
        
        if (headings.length > 0) {
            let tocHtml = '';
            headings.forEach(function(heading, index) {
                // Ajouter un id s'il n'en a pas
                if (!heading.id) {
                    heading.id = 'heading-' + index;
                }

                const level = parseInt(heading.tagName.charAt(1));
                const indent = level === 1 ? '' : (level === 2 ? 'pl-3' : 'pl-6');
                const textSize = level === 1 ? 'text-sm font-medium' : 'text-xs';

                tocHtml += '<a href="#' + heading.id + '" data-toc-target="' + heading.id + '" class="block py-1.5 px-2 rounded ' + indent + ' ' + textSize + ' text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition truncate toc-link">' + heading.textContent.trim() + '</a>';
            });

            if (tocDesktop) tocDesktop.innerHTML = tocHtml;
            if (tocMobile) tocMobile.innerHTML = tocHtml;

            // Scroll-spy
            const tocLinks = document.querySelectorAll('.toc-link');
            const observerOptions = {
                root: null,
                rootMargin: '-80px 0px -60% 0px',
                threshold: 0
            };

            let activeId = null;

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        activeId = entry.target.id;
                        tocLinks.forEach(function(link) {
                            if (link.getAttribute('data-toc-target') === activeId) {
                                link.classList.add('text-green-600', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20', 'font-medium');
                                link.classList.remove('text-slate-600', 'dark:text-slate-400');
                            } else {
                                link.classList.remove('text-green-600', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20', 'font-medium');
                                link.classList.add('text-slate-600', 'dark:text-slate-400');
                            }
                        });
                    }
                });
            }, observerOptions);

            headings.forEach(function(heading) {
                observer.observe(heading);
            });

            // Smooth scroll pour les liens du sommaire
            document.querySelectorAll('.toc-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-toc-target');
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        // Fermer le drawer mobile si ouvert
                        closeTocDrawer();
                    }
                });
            });
        } else {
            // Pas de headings, cacher le sommaire
            if (tocDesktop) tocDesktop.innerHTML = '<p class="text-xs text-slate-400 italic">Pas de sommaire disponible</p>';
            if (tocMobile) tocMobile.innerHTML = '<p class="text-sm text-slate-400 italic">Pas de sommaire disponible</p>';
            const fab = document.getElementById('toc-fab');
            if (fab) fab.style.display = 'none';
        }
    } else {
        // Pas de contenu, cacher le sommaire
        const fab = document.getElementById('toc-fab');
        if (fab) fab.style.display = 'none';
    }

    // ==========================================
    // Drawer mobile (sommaire)
    // ==========================================
    window.openTocDrawer = function() {
        const overlay = document.getElementById('toc-drawer-overlay');
        const drawer = document.getElementById('toc-drawer');
        if (overlay) overlay.classList.remove('hidden');
        if (drawer) {
            drawer.classList.remove('translate-y-full');
            drawer.classList.add('translate-y-0');
        }
        document.body.style.overflow = 'hidden';
    };

    window.closeTocDrawer = function() {
        const overlay = document.getElementById('toc-drawer-overlay');
        const drawer = document.getElementById('toc-drawer');
        if (overlay) overlay.classList.add('hidden');
        if (drawer) {
            drawer.classList.add('translate-y-full');
            drawer.classList.remove('translate-y-0');
        }
        document.body.style.overflow = '';
    };

    // ==========================================
    // Quiz
    // ==========================================
    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        quizForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(quizForm);
            const answers = {};
            formData.forEach(function(value, key) {
                if (key.startsWith('answers[')) {
                    const questionId = key.match(/\[(\d+)\]/)[1];
                    answers[questionId] = value;
                }
            });

            try {
                const response = await fetch('{{ route("api.courses.quiz-submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        lesson_id: {{ $lesson->id }},
                        answers: answers
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la soumission du quiz.');
            }
        });
    }

    // ==========================================
    // Marquer comme complété (desktop + mobile)
    // ==========================================
    function handleComplete() {
        fetch('{{ route("api.courses.complete-lesson") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                lesson_id: {{ $lesson->id }}
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            alert('Une erreur est survenue.');
        });
    }

    const completeBtn = document.getElementById('complete-lesson-btn');
    const completeBtnMobile = document.getElementById('complete-lesson-btn-mobile');
    if (completeBtn) completeBtn.addEventListener('click', handleComplete);
    if (completeBtnMobile) completeBtnMobile.addEventListener('click', handleComplete);
});
</script>
@endpush
@include('components.admin-edit-courses-button')
@endsection
