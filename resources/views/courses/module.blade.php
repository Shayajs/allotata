@extends('layouts.user')

@section('title', $module->titre . ' - Apprendre Allotata')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">

    {{-- Header : breadcrumb + bouton retour --}}
    <div class="pt-20 sm:pt-24 pb-4 px-4 sm:px-6 lg:px-8 2xl:px-12 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-5xl xl:max-w-6xl 2xl:max-w-7xl mx-auto">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-4">
                <a href="{{ route('courses.index') }}" class="hover:text-green-600 dark:hover:text-green-400 transition">
                    Apprendre
                </a>
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-slate-900 dark:text-white font-medium truncate">{{ $module->titre }}</span>
            </nav>

            {{-- Bouton retour mobile --}}
            <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition mb-4 sm:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour aux modules
            </a>
        </div>
    </div>

    {{-- Section Hero : titre, description, progression --}}
    <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 py-6 sm:py-8 md:py-10">
        <div class="max-w-5xl xl:max-w-6xl 2xl:max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-start gap-6">
                {{-- Texte --}}
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-3">
                        {{ $module->titre }}
                    </h1>
                    @if($module->description)
                        <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mb-4">
                            {{ $module->description }}
                        </p>
                    @endif
                    <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            {{ $lessons->count() }} {{ $lessons->count() > 1 ? 'leçons' : 'leçon' }}
                        </span>
                        @if($module->video_url)
                            <span class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                                </svg>
                                Vidéo de présentation
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Progression circulaire --}}
                @if($user && $moduleProgress)
                    <div class="flex-shrink-0 flex flex-row md:flex-col items-center gap-4 md:gap-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 md:p-5">
                        <div class="relative w-20 h-20 sm:w-24 sm:h-24">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 128 128">
                                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="10" fill="none" class="text-slate-200 dark:text-slate-700"></circle>
                                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="10" fill="none"
                                    stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $moduleProgress->progress_percentage / 100) }}"
                                    stroke-linecap="round"
                                    class="text-green-500 transition-all duration-500"
                                ></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">
                                    {{ round($moduleProgress->progress_percentage) }}%
                                </span>
                            </div>
                        </div>
                        <div class="text-left md:text-center">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $moduleProgress->lessons_completed }}/{{ $moduleProgress->total_lessons }} leçons
                            </p>
                            @if($moduleProgress->points_total > 0)
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium mt-0.5">
                                    {{ $moduleProgress->points_total }} points
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Vidéo de présentation --}}
    @if($module->video_url)
        <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 pb-6 sm:pb-8">
            <div class="max-w-5xl xl:max-w-6xl 2xl:max-w-7xl mx-auto">
                <div class="rounded-xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700 bg-black">
                    @php
                        $videoUrl = $module->video_url;
                        $isYoutube = preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $ytMatch);
                        $isVimeo = preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $vimeoMatch);
                    @endphp

                    @if($isYoutube)
                        <div class="aspect-video">
                            <iframe 
                                src="https://www.youtube.com/embed/{{ $ytMatch[1] }}?rel=0" 
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    @elseif($isVimeo)
                        <div class="aspect-video">
                            <iframe 
                                src="https://player.vimeo.com/video/{{ $vimeoMatch[1] }}"
                                class="w-full h-full"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    @else
                        <div class="aspect-video">
                            <video 
                                src="{{ $videoUrl }}" 
                                controls 
                                class="w-full h-full"
                                preload="metadata"
                            ></video>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Liste des leçons : grid de cards --}}
    <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 pb-12 sm:pb-16">
        <div class="max-w-5xl xl:max-w-6xl 2xl:max-w-7xl mx-auto">
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-4 sm:mb-6">
                Leçons du module
            </h2>

            @if($lessons->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 xl:gap-5">
                    @foreach($lessons as $lesson)
                        @php
                            $isAccessible = $lesson->isAccessibleBy($user);
                            $isCompleted = $user && $lesson->isCompletedBy($user);
                            $progress = $lessonProgress[$lesson->id] ?? null;
                        @endphp
                        <a 
                            href="{{ $isAccessible ? route('courses.lesson', ['module' => $module, 'lesson' => $lesson]) : '#' }}"
                            class="block group {{ !$isAccessible ? 'opacity-60 cursor-not-allowed' : '' }}"
                            @if(!$isAccessible) onclick="event.preventDefault();" @endif
                        >
                            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 sm:p-5 h-full flex flex-col transition-all duration-200 {{ $isAccessible ? 'hover:shadow-md hover:border-green-300 dark:hover:border-green-700' : '' }} {{ $isCompleted ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' : '' }}">
                                
                                {{-- Header : icône statut + type --}}
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        @if($isCompleted)
                                            <div class="w-7 h-7 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        @elseif(!$isAccessible)
                                            <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                                <div class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-500"></div>
                                            </div>
                                        @endif
                                    </div>

                                    @if($lesson->isQuiz())
                                        <span class="text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-0.5 rounded-full">
                                            Quiz
                                        </span>
                                    @else
                                        <span class="text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full">
                                            Cours
                                        </span>
                                    @endif
                                </div>

                                {{-- Titre --}}
                                <h3 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white mb-1 {{ $isAccessible ? 'group-hover:text-green-600 dark:group-hover:text-green-400' : '' }} transition line-clamp-2 flex-1">
                                    {{ $lesson->titre }}
                                </h3>

                                {{-- Description courte --}}
                                @if($lesson->description)
                                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-2">
                                        {{ $lesson->description }}
                                    </p>
                                @endif

                                {{-- Score quiz si complété --}}
                                @if($isCompleted && $lesson->isQuiz() && $progress && $progress->score !== null)
                                    <div class="mt-auto pt-2">
                                        <span class="text-xs font-medium {{ $progress->score >= 70 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }}">
                                            Score : {{ $progress->score }}%
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 sm:py-12 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <svg class="w-12 h-12 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <p class="text-slate-600 dark:text-slate-400">
                        Aucune leçon disponible pour ce module.
                    </p>
                </div>
            @endif
        </div>
    </section>
</div>

@include('components.admin-edit-courses-button')
@endsection
