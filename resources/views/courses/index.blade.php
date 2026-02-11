@extends('layouts.user')

@section('title', 'Apprendre Allotata - Allo Tata')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Hero Section -->
    <section class="pt-20 sm:pt-24 md:pt-32 pb-8 sm:pb-12 md:pb-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-900">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-bold mb-4 sm:mb-6">
                <span class="block text-slate-900 dark:text-white">Apprendre</span>
                <span class="block bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    Allotata
                </span>
            </h1>
            <p class="text-base sm:text-lg md:text-xl lg:text-2xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-6 sm:mb-8 md:mb-10 px-2">
                Découvrez comment utiliser Allotata de A à Z grâce à nos cours interactifs.
                Maîtrisez toutes les fonctionnalités de la plateforme étape par étape.
            </p>
        </div>
    </section>

    <!-- Liste des modules -->
    <section class="py-8 sm:py-10 md:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            @if($modules->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 xl:gap-8">
                    @foreach($modules as $module)
                        @php
                            $progress = $userProgress[$module->id] ?? null;
                            $lessonsCount = $module->activeLessons->count();
                        @endphp
                        <a href="{{ route('courses.module', $module) }}" class="block group">
                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg hover:border-green-300 dark:hover:border-green-700 transition-all duration-300 h-full flex flex-col">
                                {{-- Image / Placeholder --}}
                                <div class="relative">
                                    @if($module->image_path)
                                        <div class="aspect-video w-full overflow-hidden bg-slate-200 dark:bg-slate-700">
                                            <img 
                                                src="{{ asset('storage/' . $module->image_path) }}" 
                                                alt="{{ $module->titre }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                loading="lazy"
                                            >
                                        </div>
                                    @else
                                        <div class="aspect-video w-full bg-gradient-to-br from-green-500 to-orange-500 flex items-center justify-center">
                                            <svg class="w-12 h-12 sm:w-16 sm:h-16 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                        </div>
                                    @endif

                                    {{-- Badge vidéo --}}
                                    @if($module->video_url)
                                        <div class="absolute top-3 right-3">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-black/60 backdrop-blur-sm text-white text-xs font-medium rounded-full">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                                                </svg>
                                                Vidéo
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Contenu --}}
                                <div class="p-4 sm:p-5 md:p-6 flex-1 flex flex-col">
                                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-2 group-hover:text-green-500 transition">
                                        {{ $module->titre }}
                                    </h3>
                                    
                                    @if($module->description)
                                        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mb-3 sm:mb-4 line-clamp-2 flex-1">
                                            {{ $module->description }}
                                        </p>
                                    @endif

                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 text-xs sm:text-sm mt-auto">
                                        <span class="text-slate-500 dark:text-slate-400">
                                            {{ $lessonsCount }} {{ $lessonsCount > 1 ? 'leçons' : 'leçon' }}
                                        </span>
                                        
                                        @if($user && $progress)
                                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                                <div class="flex-1 sm:flex-none sm:w-24 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                    <div 
                                                        class="h-full bg-gradient-to-r from-green-500 to-green-400 transition-all duration-300"
                                                        style="width: {{ $progress->progress_percentage }}%"
                                                    ></div>
                                                </div>
                                                <span class="text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">
                                                    {{ round($progress->progress_percentage) }}%
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-green-600 dark:text-green-400 font-medium">
                                                Commencer
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 sm:py-12">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <p class="text-slate-600 dark:text-slate-400 text-base sm:text-lg px-4">
                        Aucun module disponible pour le moment.
                    </p>
                </div>
            @endif
        </div>
    </section>
</div>

@include('components.admin-edit-courses-button')
@endsection
