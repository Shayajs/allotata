@php
    $completion = $entreprise->getCompletionStatus();
    $circumference = 2 * pi() * 45; // Rayon = 45
    $offset = $circumference * (1 - $completion['percentage'] / 100);
@endphp

@if(!$completion['isFullyComplete'])
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

            <!-- Contenu -->
            <div class="flex-1 w-full">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">
                    Finalisez votre profil
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    {{ $completion['completedEssential'] }}/{{ $completion['totalEssential'] }} étapes essentielles
                    @if($completion['completedAdvanced'] > 0)
                        &middot; {{ $completion['completedAdvanced'] }}/{{ $completion['totalAdvanced'] }} avancées
                    @endif
                </p>

                {{-- Section Essentiel --}}
                @if($completion['completedEssential'] < $completion['totalEssential'])
                    <div class="mb-4">
                        <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Essentiel</h4>
                        <div class="space-y-2">
                            @foreach($completion['essential'] as $key => $condition)
                                @include('components.entreprise-completion-item', ['condition' => $condition, 'key' => $key])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Section Pour aller plus loin --}}
                <div x-data="{ open: {{ $completion['isComplete'] ? 'true' : 'false' }} }">
                    <button 
                        onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron-icon').classList.toggle('rotate-90')" 
                        class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 hover:text-slate-700 dark:hover:text-slate-300 transition"
                    >
                        <svg class="w-3 h-3 chevron-icon transition-transform {{ $completion['isComplete'] ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        Pour aller plus loin ({{ $completion['completedAdvanced'] }}/{{ $completion['totalAdvanced'] }})
                    </button>
                    <div class="{{ $completion['isComplete'] ? '' : 'hidden' }} space-y-2">
                        @foreach($completion['advanced'] as $key => $condition)
                            @include('components.entreprise-completion-item', ['condition' => $condition, 'key' => $key])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
