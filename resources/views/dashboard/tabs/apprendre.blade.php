{{-- Onglet Apprendre - Parcours de formation --}}
<div class="flex flex-col">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Apprendre</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Suivez vos cours et progressez à votre rythme</p>
        </div>
        <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Voir tous les cours
        </a>
    </div>

    @if(isset($courseModules) && $courseModules->count() > 0)
        {{-- Résumé progression globale --}}
        @php
            $totalModules = $courseModules->count();
            $totalLessons = $courseModules->sum(fn($m) => $m->activeLessons->count());
            $completedLessons = 0;
            $completedModules = 0;
            $totalPoints = 0;
            $lastAccessed = null;

            if (isset($courseProgress) && $courseProgress) {
                foreach ($courseProgress as $progress) {
                    $completedLessons += $progress->lessons_completed ?? 0;
                    $totalPoints += $progress->points_total ?? 0;
                    if ($progress->isCompleted()) {
                        $completedModules++;
                    }
                    if ($progress->last_accessed_at && (!$lastAccessed || $progress->last_accessed_at->gt($lastAccessed))) {
                        $lastAccessed = $progress->last_accessed_at;
                    }
                }
            }

            $globalPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="p-3 sm:p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Modules</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">{{ $completedModules }}<span class="text-sm font-normal text-slate-500">/{{ $totalModules }}</span></p>
            </div>
            <div class="p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Leçons</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">{{ $completedLessons }}<span class="text-sm font-normal text-slate-500">/{{ $totalLessons }}</span></p>
            </div>
            <div class="p-3 sm:p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <span class="text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wide">Points</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">{{ $totalPoints }}</p>
            </div>
            <div class="p-3 sm:p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Progression</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">{{ $globalPercent }}%</p>
            </div>
        </div>

        {{-- Barre de progression globale --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Progression globale</span>
                @if($lastAccessed)
                    <span class="text-xs text-slate-400 dark:text-slate-500">Dernier accès : {{ $lastAccessed->diffForHumans() }}</span>
                @endif
            </div>
            <div class="w-full h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-500 to-emerald-400 rounded-full transition-all duration-500" style="width: {{ $globalPercent }}%"></div>
            </div>
        </div>

        {{-- Liste des modules avec leçons --}}
        <div class="space-y-4">
            @foreach($courseModules as $module)
                @php
                    $moduleProgress = isset($courseProgress) && $courseProgress ? ($courseProgress[$module->id] ?? null) : null;
                    $modulePercent = $moduleProgress ? round($moduleProgress->progress_percentage) : 0;
                    $moduleLessonsCompleted = $moduleProgress ? $moduleProgress->lessons_completed : 0;
                    $moduleTotalLessons = $module->activeLessons->count();
                    $isModuleCompleted = $moduleProgress && $moduleProgress->isCompleted();
                @endphp
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all hover:border-slate-300 dark:hover:border-slate-600">
                    {{-- En-tête du module (cliquable pour déplier) --}}
                    <button 
                        type="button"
                        onclick="toggleModuleLessons('module-lessons-{{ $module->id }}')"
                        class="w-full flex items-center gap-3 sm:gap-4 p-3 sm:p-4 bg-slate-50 dark:bg-slate-800/50 text-left hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        {{-- Image ou icône du module --}}
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg flex-shrink-0 overflow-hidden bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center">
                            @if($module->image_path)
                                <img src="{{ asset('storage/' . $module->image_path) }}" alt="{{ $module->titre }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            @endif
                        </div>

                        {{-- Infos du module --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white truncate">{{ $module->titre }}</h3>
                                @if($isModuleCompleted)
                                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                        Terminé
                                    </span>
                                @endif
                                @if($module->video_url)
                                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path></svg>
                                        Vidéo
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $moduleLessonsCompleted }}/{{ $moduleTotalLessons }} leçons</span>
                                <div class="flex-1 max-w-[120px] sm:max-w-[200px] h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $isModuleCompleted ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $modulePercent }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $isModuleCompleted ? 'text-green-600 dark:text-green-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $modulePercent }}%</span>
                            </div>
                        </div>

                        {{-- Chevron --}}
                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform duration-200 module-chevron" id="chevron-{{ $module->id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    {{-- Liste des leçons (cachée par défaut) --}}
                    <div id="module-lessons-{{ $module->id }}" class="hidden border-t border-slate-200 dark:border-slate-700">
                        @if($moduleTotalLessons > 0)
                            <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                @foreach($module->activeLessons as $lessonIndex => $lesson)
                                    @php
                                        $isCompleted = $lesson->isCompletedBy($user);
                                        $isAccessible = $lesson->isAccessibleBy($user);
                                        $isQuiz = $lesson->isQuiz();
                                    @endphp
                                    @if($isAccessible)
                                        <a 
                                            href="{{ route('courses.lesson', [$module, $lesson]) }}"
                                            class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group"
                                        >
                                    @else
                                        <div class="flex items-center gap-3 px-4 sm:px-5 py-3 opacity-50 cursor-not-allowed">
                                    @endif
                                        {{-- Numéro / Statut --}}
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                            {{ $isCompleted ? 'bg-green-100 dark:bg-green-900/30' : ($isAccessible ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-slate-100 dark:bg-slate-700') }}">
                                            @if($isCompleted)
                                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @elseif(!$isAccessible)
                                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                            @else
                                                <span class="text-xs font-semibold {{ $isAccessible ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400' }}">{{ $lessonIndex + 1 }}</span>
                                            @endif
                                        </div>

                                        {{-- Titre + type --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate {{ $isCompleted ? 'text-green-700 dark:text-green-400' : ($isAccessible ? 'text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400' : 'text-slate-500 dark:text-slate-500') }}">
                                                {{ $lesson->titre }}
                                            </p>
                                        </div>

                                        {{-- Badges --}}
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            @if($isQuiz)
                                                <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">Quiz</span>
                                            @else
                                                <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-full">Cours</span>
                                            @endif
                                            @if($isAccessible && !$isCompleted)
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-green-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    @if($isAccessible)
                                        </a>
                                    @else
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                Aucune leçon disponible pour ce module.
                            </div>
                        @endif

                        {{-- Lien vers le module complet --}}
                        <div class="px-4 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-700">
                            <a href="{{ route('courses.module', $module) }}" class="inline-flex items-center gap-2 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                                Voir le module complet
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- État vide --}}
        <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucun cours disponible</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6">Les cours seront bientôt disponibles. Restez connecté !</p>
            <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                Explorer les cours
            </a>
        </div>
    @endif
</div>

<script>
    function toggleModuleLessons(containerId) {
        const container = document.getElementById(containerId);
        const moduleId = containerId.replace('module-lessons-', '');
        const chevron = document.getElementById('chevron-' + moduleId);
        
        if (container) {
            const isHidden = container.classList.contains('hidden');
            container.classList.toggle('hidden');
            
            if (chevron) {
                if (isHidden) {
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        }
    }
</script>
