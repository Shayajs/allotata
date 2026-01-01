@php
    $completion = $entreprise->getCompletionStatus();
    $circumference = 2 * pi() * 45; // Rayon = 45
    $offset = $circumference * (1 - $completion['percentage'] / 100);
@endphp

@if(!$completion['isComplete'])
    <!-- Box de suivi pour les nouvelles entreprises -->
    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-8 shadow-lg">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
            <!-- Cercle de progression -->
            <div class="flex-shrink-0 relative w-24 h-24 md:w-32 md:h-32">
                <svg class="w-24 h-24 md:w-32 md:h-32 transform -rotate-90" viewBox="0 0 100 100">
                    <!-- Cercle de fond -->
                    <circle
                        cx="50"
                        cy="50"
                        r="45"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="8"
                        class="text-slate-200 dark:text-slate-700"
                    />
                    <!-- Cercle de progression -->
                    <circle
                        cx="50"
                        cy="50"
                        r="45"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="8"
                        stroke-linecap="round"
                        class="text-blue-600 dark:text-blue-400 transition-all duration-500"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $offset }}"
                    />
                </svg>
                <!-- Pourcentage au centre -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $completion['percentage'] }}%
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                            Complété
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des conditions -->
            <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    🎯 Finalisez votre profil
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    Complétez ces étapes pour rendre votre entreprise plus visible et professionnelle.
                </p>
                <div class="space-y-3">
                    @foreach($completion['conditions'] as $key => $condition)
                        <div class="flex items-center gap-3 p-3 rounded-lg {{ $condition['completed'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-slate-800' }}">
                            @if($condition['completed'])
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="flex-1 text-sm font-medium text-green-700 dark:text-green-400 line-through">
                                    {{ $condition['label'] }}
                                </span>
                            @else
                                <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-blue-500 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                </div>
                                <a href="{{ $condition['route'] }}" class="flex-1 text-sm font-medium text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">
                                    {{ $condition['label'] }}
                                </a>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
