@extends('layouts.user')

@section('title', $module->titre . ' - Apprendre Allotata')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex">
    <!-- Sidebar 20% -->
    <aside class="w-1/5 min-w-[280px] bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 overflow-y-auto sticky top-0 h-screen">
        <div class="p-6">
            <!-- Barre de progression circulaire (gamification) -->
            @if($user && $moduleProgress)
                <div class="mb-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="relative w-32 h-32">
                            <svg class="w-32 h-32 transform -rotate-90">
                                <!-- Cercle de fond -->
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="currentColor"
                                    stroke-width="12"
                                    fill="none"
                                    class="text-slate-200 dark:text-slate-700"
                                ></circle>
                                <!-- Cercle de progression -->
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
                            {{ $moduleProgress->lessons_completed }} / {{ $moduleProgress->total_lessons }} leçons complétées
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
                <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                    Modules
                </h3>
                <ul class="space-y-1">
                    @foreach($allModules as $m)
                        <li>
                            <a 
                                href="{{ route('courses.module', $m) }}"
                                class="block px-3 py-2 rounded-lg text-sm transition-colors {{ $m->id === $module->id ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-medium' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                            >
                                {{ $m->titre }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Navigation des leçons du module actuel -->
            <div>
                <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                    Leçons
                </h3>
                <ul class="space-y-1">
                    @foreach($lessons as $lesson)
                        @php
                            $isAccessible = $lesson->isAccessibleBy($user);
                            $isCompleted = $user && $lesson->isCompletedBy($user);
                            $progress = $lessonProgress[$lesson->id] ?? null;
                            $isCurrent = $currentLesson && $currentLesson->id === $lesson->id;
                        @endphp
                        <li>
                            <a 
                                href="{{ route('courses.lesson', ['module' => $module, 'lesson' => $lesson]) }}"
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
                                        <span>{{ $lesson->titre }}</span>
                                    </span>
                                    @if($lesson->isQuiz())
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
    <main class="flex-1 overflow-y-auto">
        @if($currentLesson)
            <!-- Rediriger vers la leçon actuelle -->
            <script>
                window.location.href = '{{ route("courses.lesson", ["module" => $module, "lesson" => $currentLesson]) }}';
            </script>
        @else
            <!-- Afficher le module par défaut -->
            <div class="max-w-4xl mx-auto p-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ $module->titre }}</h1>
                @if($module->description)
                    <p class="text-lg text-slate-600 dark:text-slate-400 mb-6">{{ $module->description }}</p>
                @endif
                <p class="text-slate-500 dark:text-slate-400">Sélectionnez une leçon pour commencer.</p>
            </div>
        @endif
    </main>
</div>

@push('scripts')
<script>
    // Animation de la barre de progression
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
    });
</script>
@endpush

@include('components.admin-edit-courses-button')
@endsection
