@extends('layouts.user')

@section('title', $lesson->titre . ' - ' . $module->titre)

@push('styles')
{{-- Styles pour les blocs de cours inclus dans app.css --}}
<style>
    /* Désactiver le scroll du body pour la page de cours */
    body {
        overflow: hidden;
    }
    /* Ajuster le main du layout pour la page de cours */
    main.max-w-7xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
        height: calc(100vh - 4rem) !important;
        overflow: hidden !important;
    }
    
    /* S'assurer que seuls les conteneurs principaux ont le scroll */
    /* Empêcher le scroll dans les blocs individuels qui pourraient créer des conflits */
    #lesson-content section,
    #lesson-content .prose,
    #lesson-content .prose > *,
    #lesson-content .course-block-content {
        overflow: visible !important;
    }
    
    /* S'assurer que les styles des blocs de cours sont appliqués */
    #lesson-content {
        line-height: 1.7;
    }
    
    #lesson-content section {
        margin-bottom: 0;
    }
    
    /* Les iframes et vidéos peuvent avoir leur propre scroll mais ne doivent pas bloquer le scroll de la page */
    #lesson-content iframe,
    #lesson-content video {
        pointer-events: auto;
    }
    
    /* Empêcher le scroll sur les conteneurs de blocs qui pourraient en créer */
    #lesson-content > * > * {
        overflow: visible !important;
        max-height: none !important;
    }
    
    /* S'assurer que le contenu principal peut bien scroller */
    #lesson-content {
        overflow: visible;
    }
</style>
@endpush

@section('content')
<div class="bg-slate-50 dark:bg-slate-900 flex overflow-hidden" style="height: calc(100vh - 4rem);">
    <!-- Sidebar 20% -->
    <aside class="w-1/5 min-w-[280px] bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 overflow-y-auto h-full">
        <div class="p-6">
            <!-- Bouton retour à la liste des cours -->
            <div class="mb-6">
                <a 
                    href="{{ route('courses.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors w-full"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Tous les modules</span>
                </a>
            </div>

            <!-- Barre de progression circulaire -->
            @if($user && $moduleProgress)
                <div class="mb-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="relative w-32 h-32">
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="currentColor"
                                    stroke-width="12"
                                    fill="none"
                                    class="text-slate-200 dark:text-slate-700"
                                ></circle>
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="currentColor"
                                    stroke-width="12"
                                    fill="none"
                                    stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $moduleProgress->progress_percentage / 100) }}"
                                    stroke-linecap="round"
                                    class="text-green-500 transition-all duration-500"
                                    id="progress-circle"
                                ></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-2xl font-bold text-slate-900 dark:text-white" id="progress-text">
                                    {{ round($moduleProgress->progress_percentage) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                            {{ $moduleProgress->lessons_completed }} / {{ $moduleProgress->total_lessons }} leçons
                        </p>
                        @if($moduleProgress->points_total > 0)
                            <p class="text-xs text-green-600 dark:text-green-400 font-medium">
                                {{ $moduleProgress->points_total }} points
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Navigation des modules -->
            <div class="mb-6">
                <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center justify-between">
                    <span>Modules</span>
                </h3>
                <ul class="space-y-1">
                    @foreach($allModules as $m)
                        <li>
                            <a 
                                href="{{ route('courses.module', $m) }}"
                                class="block px-3 py-2 rounded-lg text-sm transition-colors {{ $m->id === $module->id ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-medium' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                            >
                                <div class="flex items-center justify-between">
                                    <span>{{ $m->titre }}</span>
                                    @if($m->id === $module->id)
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Navigation des leçons -->
            <div>
                <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                    Leçons
                </h3>
                <ul class="space-y-1">
                    @foreach($lessons as $l)
                        @php
                            $isAccessible = $l->isAccessibleBy($user);
                            $isCompleted = $user && $l->isCompletedBy($user);
                            $progress = $lessonProgressMap[$l->id] ?? null;
                            $isCurrent = $l->id === $lesson->id;
                        @endphp
                        <li>
                            <a 
                                href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $l]) }}"
                                class="block px-3 py-2 rounded-lg text-sm transition-colors relative {{ $isCurrent ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-medium' : ($isAccessible ? 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-400 dark:text-slate-600 opacity-50 cursor-not-allowed') }}"
                                @if(!$isAccessible) onclick="event.preventDefault(); return false;" @endif
                            >
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        @if($isCompleted)
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif(!$isAccessible)
                                            <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                        <span>{{ $l->titre }}</span>
                                    </span>
                                    @if($l->isQuiz())
                                        <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-0.5 rounded">
                                            Quiz
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </aside>

    <!-- Contenu principal 80% -->
    <main class="flex-1 overflow-y-auto h-full" id="lesson-main-content">
        <div class="max-w-4xl mx-auto p-8">
            <!-- Image de couverture -->
            @if($lesson->image_path)
                <div class="mb-8 rounded-xl overflow-hidden">
                    <img 
                        src="{{ asset('storage/' . $lesson->image_path) }}" 
                        alt="{{ $lesson->titre }}"
                        class="w-full h-64 object-cover"
                    >
                </div>
            @endif

            <!-- En-tête -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    @if($lesson->isQuiz())
                        <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full text-sm font-medium">
                            Quiz
                        </span>
                    @else
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-sm font-medium">
                            Cours
                        </span>
                    @endif
                    @if($lessonProgress && $lessonProgress->completed_at)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Complété
                        </span>
                    @endif
                </div>
                
                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">
                    {{ $lesson->titre }}
                </h1>
                
                @if($lesson->description)
                    <p class="text-lg text-slate-600 dark:text-slate-400">
                        {{ $lesson->description }}
                    </p>
                @endif
            </div>

            <!-- Contenu riche du cours -->
            @if($lesson->contenu_rich_html)
                <div id="lesson-content" class="mb-8">
                    {!! $lesson->contenu_rich_html !!}
                </div>
            @endif

            <!-- Quiz -->
            @if($lesson->isQuiz() && $quizQuestions->count() > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-8">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Questions du quiz</h2>
                    <form id="quiz-form" class="space-y-6">
                        @csrf
                        @foreach($quizQuestions as $index => $question)
                            <div class="border-b border-slate-200 dark:border-slate-700 pb-6 last:border-0 last:pb-0">
                                <div class="flex items-start justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                        Question {{ $index + 1 }}: {{ $question->question }}
                                    </h3>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ $question->points }} point{{ $question->points > 1 ? 's' : '' }}
                                    </span>
                                </div>

                                @if($question->type === 'multiple_choice')
                                    <div class="space-y-2">
                                        @foreach($question->getOptions() as $option)
                                            <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                                <input 
                                                    type="radio" 
                                                    name="answers[{{ $question->id }}]" 
                                                    value="{{ $option }}"
                                                    class="w-4 h-4 text-green-600 focus:ring-green-500"
                                                    {{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) && $lessonProgress->quiz_answers_json[$question->id] === $option ? 'checked' : '' }}
                                                    @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                                >
                                                <span class="text-slate-700 dark:text-slate-300">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question->type === 'true_false')
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="answers[{{ $question->id }}]" 
                                                value="1"
                                                class="w-4 h-4 text-green-600 focus:ring-green-500"
                                                {{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) && $lessonProgress->quiz_answers_json[$question->id] == 1 ? 'checked' : '' }}
                                                @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                            >
                                            <span class="text-slate-700 dark:text-slate-300">Vrai</span>
                                        </label>
                                        <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                            <input 
                                                type="radio" 
                                                name="answers[{{ $question->id }}]" 
                                                value="0"
                                                class="w-4 h-4 text-green-600 focus:ring-green-500"
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
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                        placeholder="Votre réponse..."
                                        @if($lessonProgress && $lessonProgress->completed_at) disabled @endif
                                    >{{ $lessonProgress && isset($lessonProgress->quiz_answers_json[$question->id]) ? $lessonProgress->quiz_answers_json[$question->id] : '' }}</textarea>
                                @endif
                            </div>
                        @endforeach

                        <div class="flex items-center justify-between pt-4">
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
                                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                                >
                                    Soumettre le quiz
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @endif

            <!-- Boutons de navigation -->
            <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
                <div>
                    @if($previousLesson && $previousLesson->isAccessibleBy($user))
                        <a 
                            href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $previousLesson]) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Leçon précédente
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
                            Leçon suivante
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </main>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const progressCircle = document.getElementById('progress-circle');
        const progressText = document.getElementById('progress-text');
        
        if (progressCircle && progressText) {
            const progress = {{ $moduleProgress ? $moduleProgress->progress_percentage : 0 }};
            const radius = 56;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference * (1 - progress / 100);
            
            progressCircle.style.strokeDashoffset = offset;
        }

        // Soumettre le quiz
        const quizForm = document.getElementById('quiz-form');
        if (quizForm) {
            quizForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(quizForm);
                const answers = {};
                formData.forEach((value, key) => {
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
                        // Recharger la page pour afficher les résultats
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

        // Marquer la leçon comme complétée (pour les cours)
        const completeBtn = document.getElementById('complete-lesson-btn');
        if (completeBtn) {
            completeBtn.addEventListener('click', async function() {
                try {
                    const response = await fetch('{{ route("api.courses.complete-lesson") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            lesson_id: {{ $lesson->id }}
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        // Mettre à jour la progression dans la sidebar
                        if (data.progress && progressCircle && progressText) {
                            const newProgress = data.progress.progress_percentage;
                            const radius = 56;
                            const circumference = 2 * Math.PI * radius;
                            const offset = circumference * (1 - newProgress / 100);
                            
                            progressCircle.style.strokeDashoffset = offset;
                            progressText.textContent = Math.round(newProgress) + '%';
                        }
                        
                        // Recharger la page
                        window.location.reload();
                    } else {
                        alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Une erreur est survenue.');
                }
            });
        }
    });
</script>
@endpush
@include('components.admin-edit-courses-button')

@push('scripts')
{{-- Scripts pour les blocs de cours (chargé depuis app.js automatiquement) --}}
@endpush
@endsection
