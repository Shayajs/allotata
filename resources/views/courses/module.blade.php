@extends('layouts.user')

@section('title', $module->titre . ' - Apprendre Allotata')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">

    {{-- Header : breadcrumb + hero combinés --}}
    <div class="pt-16 sm:pt-20 px-4 sm:px-6 lg:px-8 2xl:px-12">
        <div class="max-w-6xl xl:max-w-7xl 2xl:max-w-[1400px] mx-auto pt-3 sm:pt-4">
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
        </div>
    </div>

    {{-- Section Hero : titre, description, progression --}}
    <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 pb-4 sm:pb-6">
        <div class="max-w-6xl xl:max-w-7xl 2xl:max-w-[1400px] mx-auto">
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
                        @if($module->page_key)
                            @php
                                $pageUrl = \App\Services\CoursePageLinkService::resolve($module->page_key, auth()->user());
                            @endphp
                            @if($pageUrl)
                                <a href="{{ $pageUrl }}" class="inline-flex items-center gap-1.5 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Voir la page
                                </a>
                            @endif
                        @endif
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

    {{-- Vidéo de présentation avec tracking --}}
    @if($module->video_url)
        @php
            $rawVideoUrl = $module->video_url;
            // Résoudre le chemin : si ce n'est pas une URL complète, c'est un fichier interne
            $isInternalFile = !str_starts_with($rawVideoUrl, 'http');
            $videoUrl = $isInternalFile ? asset('storage/' . $rawVideoUrl) : $rawVideoUrl;

            $isYoutube = !$isInternalFile && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $ytMatch);
            $isDailymotion = !$isInternalFile && preg_match('/(?:dailymotion\.com\/video\/|dai\.ly\/)([a-zA-Z0-9]+)/', $videoUrl, $dmMatch);
            $isVimeo = !$isInternalFile && preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $vimeoMatch);
            $isExternal = $isYoutube || $isDailymotion || $isVimeo;
            $alreadyWatched = $videoWatched ?? false;
        @endphp

        <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 pb-4 sm:pb-6" id="video-section" data-module-id="{{ $module->id }}" data-already-watched="{{ $alreadyWatched ? '1' : '0' }}">
            <div class="max-w-6xl xl:max-w-7xl 2xl:max-w-[1400px] mx-auto">

                {{-- Header vidéo avec badge points --}}
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                        </svg>
                        Vidéo de présentation
                    </h2>
                    <div id="video-points-badge" class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all duration-300 {{ $alreadyWatched ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' }}">
                        @if($alreadyWatched)
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>5 pts gagnés</span>
                        @else
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                            <span>5 pts à gagner</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700 bg-black relative">
                    @if($isYoutube)
                        {{-- YouTube : iframe avec API pour tracking fin --}}
                        <div class="aspect-video">
                            <div id="yt-player" data-video-id="{{ $ytMatch[1] }}"></div>
                        </div>
                    @elseif($isDailymotion)
                        {{-- Dailymotion : iframe avec API pour tracking fin --}}
                        <div class="aspect-video">
                            <div id="dm-player" data-video-id="{{ $dmMatch[1] }}"></div>
                        </div>
                    @elseif($isVimeo)
                        {{-- Vimeo : iframe avec API pour tracking fin --}}
                        <div class="aspect-video">
                            <iframe 
                                id="vimeo-player"
                                src="https://player.vimeo.com/video/{{ $vimeoMatch[1] }}?title=0&byline=0&portrait=0"
                                class="w-full h-full"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    @else
                        {{-- Vidéo interne : lecteur custom Allotata --}}
                        <div class="aspect-video relative group" id="allotata-player-container">
                            <video 
                                id="allotata-video"
                                class="w-full h-full object-contain bg-black"
                                preload="metadata"
                                playsinline
                            >
                                <source src="{{ $videoUrl }}" type="video/mp4">
                                <source src="{{ $videoUrl }}" type="video/webm">
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>

                            {{-- Overlay play central --}}
                            <div id="play-overlay" class="absolute inset-0 flex items-center justify-center bg-black/20 cursor-pointer transition-opacity duration-300">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-green-500/90 hover:bg-green-500 flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-lg shadow-green-500/30">
                                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Contrôles custom --}}
                            <div id="custom-controls" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-3 sm:px-4 pb-3 pt-10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                {{-- Barre de progression --}}
                                <div class="relative w-full h-1.5 bg-white/20 rounded-full cursor-pointer mb-3 group/progress" id="progress-bar-container">
                                    <div id="progress-buffered" class="absolute top-0 left-0 h-full bg-white/30 rounded-full transition-all"></div>
                                    <div id="progress-bar" class="absolute top-0 left-0 h-full bg-green-500 rounded-full transition-all">
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 bg-green-400 rounded-full shadow-md opacity-0 group-hover/progress:opacity-100 transition-opacity"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        {{-- Play/Pause --}}
                                        <button id="btn-play-pause" class="text-white hover:text-green-400 transition-colors" title="Lecture/Pause">
                                            <svg id="icon-play" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            <svg id="icon-pause" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
                                        </button>

                                        {{-- Volume --}}
                                        <div class="flex items-center gap-1.5 group/vol">
                                            <button id="btn-mute" class="text-white hover:text-green-400 transition-colors" title="Muet">
                                                <svg id="icon-vol-on" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
                                                <svg id="icon-vol-off" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
                                            </button>
                                            <input type="range" id="volume-slider" min="0" max="1" step="0.05" value="1"
                                                class="w-0 group-hover/vol:w-16 transition-all duration-200 accent-green-500 h-1 cursor-pointer opacity-0 group-hover/vol:opacity-100">
                                        </div>

                                        {{-- Temps --}}
                                        <span class="text-white/80 text-xs font-mono">
                                            <span id="time-current">0:00</span> / <span id="time-total">0:00</span>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        {{-- Plein écran --}}
                                        <button id="btn-fullscreen" class="text-white hover:text-green-400 transition-colors" title="Plein écran">
                                            <svg id="icon-fs-enter" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                            <svg id="icon-fs-exit" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4H4m0 0l5 5m6-5h5v5m0 0l-5-5M9 15v5H4m0 0l5-5m6 5h5v-5m0 0l-5 5"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Toast de points vidéo --}}
        <div id="video-points-toast" class="fixed top-20 right-4 z-50 transform translate-x-full transition-transform duration-500 ease-out">
            <div class="bg-green-600 text-white px-5 py-3 rounded-xl shadow-2xl shadow-green-600/30 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-sm">+5 points !</p>
                    <p class="text-xs text-green-100">Vidéo de présentation terminée</p>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        (function() {
            const section = document.getElementById('video-section');
            if (!section) return;

            const moduleId = section.dataset.moduleId;
            const alreadyWatched = section.dataset.alreadyWatched === '1';
            let videoCompleted = alreadyWatched;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            // --- Fonction commune : envoyer completion vidéo ---
            function sendVideoCompletion() {
                if (videoCompleted || !csrfToken) return;
                videoCompleted = true;

                fetch('{{ route("api.courses.complete-video") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ module_id: moduleId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && !data.already_watched) {
                        showPointsToast();
                        updateBadge();
                    }
                })
                .catch(err => console.error('Erreur completion vidéo:', err));
            }

            function showPointsToast() {
                const toast = document.getElementById('video-points-toast');
                if (!toast) return;
                toast.classList.remove('translate-x-full');
                toast.classList.add('translate-x-0');
                setTimeout(() => {
                    toast.classList.remove('translate-x-0');
                    toast.classList.add('translate-x-full');
                }, 4000);
            }

            function updateBadge() {
                const badge = document.getElementById('video-points-badge');
                if (!badge) return;
                badge.className = badge.className
                    .replace(/bg-amber-100|dark:bg-amber-900\/30|text-amber-700|dark:text-amber-400/g, '')
                    + ' bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
                badge.innerHTML = `
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span>5 pts gagnés</span>`;
            }

            @if($isYoutube)
            // --- YOUTUBE ---
            (function() {
                const videoId = '{{ $ytMatch[1] }}';
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(tag);

                window.onYouTubeIframeAPIReady = function() {
                    new YT.Player('yt-player', {
                        videoId: videoId,
                        width: '100%',
                        height: '100%',
                        playerVars: { rel: 0, modestbranding: 1 },
                        events: {
                            onStateChange: function(event) {
                                if (event.data === YT.PlayerState.ENDED) {
                                    sendVideoCompletion();
                                }
                            }
                        }
                    });
                };
            })();
            @elseif($isDailymotion)
            // --- DAILYMOTION ---
            (function() {
                const videoId = '{{ $dmMatch[1] }}';
                const tag = document.createElement('script');
                tag.src = 'https://api.dmcdn.net/all.js';
                tag.onload = function() {
                    DM.player(document.getElementById('dm-player'), {
                        video: videoId,
                        width: '100%',
                        height: '100%',
                        params: { autoplay: false, mute: false, 'ui-logo': false }
                    }).addEventListener('video_end', function() {
                        sendVideoCompletion();
                    });
                };
                document.head.appendChild(tag);
            })();
            @elseif($isVimeo)
            // --- VIMEO ---
            (function() {
                const tag = document.createElement('script');
                tag.src = 'https://player.vimeo.com/api/player.js';
                tag.onload = function() {
                    const player = new Vimeo.Player(document.getElementById('vimeo-player'));
                    player.on('ended', function() {
                        sendVideoCompletion();
                    });
                };
                document.head.appendChild(tag);
            })();
            @else
            // --- LECTEUR CUSTOM ALLOTATA ---
            (function() {
                const video = document.getElementById('allotata-video');
                const container = document.getElementById('allotata-player-container');
                const playOverlay = document.getElementById('play-overlay');
                const btnPlayPause = document.getElementById('btn-play-pause');
                const iconPlay = document.getElementById('icon-play');
                const iconPause = document.getElementById('icon-pause');
                const btnMute = document.getElementById('btn-mute');
                const iconVolOn = document.getElementById('icon-vol-on');
                const iconVolOff = document.getElementById('icon-vol-off');
                const volumeSlider = document.getElementById('volume-slider');
                const progressContainer = document.getElementById('progress-bar-container');
                const progressBar = document.getElementById('progress-bar');
                const progressBuffered = document.getElementById('progress-buffered');
                const timeCurrent = document.getElementById('time-current');
                const timeTotal = document.getElementById('time-total');
                const btnFullscreen = document.getElementById('btn-fullscreen');
                const iconFsEnter = document.getElementById('icon-fs-enter');
                const iconFsExit = document.getElementById('icon-fs-exit');
                const controls = document.getElementById('custom-controls');

                if (!video) return;

                function formatTime(s) {
                    const m = Math.floor(s / 60);
                    const sec = Math.floor(s % 60);
                    return m + ':' + (sec < 10 ? '0' : '') + sec;
                }

                function updatePlayState() {
                    if (video.paused) {
                        iconPlay.classList.remove('hidden');
                        iconPause.classList.add('hidden');
                        playOverlay.classList.remove('opacity-0', 'pointer-events-none');
                    } else {
                        iconPlay.classList.add('hidden');
                        iconPause.classList.remove('hidden');
                        playOverlay.classList.add('opacity-0', 'pointer-events-none');
                    }
                }

                // Play / Pause
                playOverlay.addEventListener('click', () => { video.play(); });
                btnPlayPause.addEventListener('click', () => { video.paused ? video.play() : video.pause(); });
                video.addEventListener('play', updatePlayState);
                video.addEventListener('pause', updatePlayState);
                video.addEventListener('click', () => { video.paused ? video.play() : video.pause(); });

                // Temps et progression
                video.addEventListener('loadedmetadata', () => { timeTotal.textContent = formatTime(video.duration); });
                video.addEventListener('timeupdate', () => {
                    if (video.duration) {
                        const pct = (video.currentTime / video.duration) * 100;
                        progressBar.style.width = pct + '%';
                        timeCurrent.textContent = formatTime(video.currentTime);
                    }
                });
                video.addEventListener('progress', () => {
                    if (video.buffered.length > 0 && video.duration) {
                        const buffered = video.buffered.end(video.buffered.length - 1);
                        progressBuffered.style.width = (buffered / video.duration) * 100 + '%';
                    }
                });

                // Clic sur la barre de progression
                progressContainer.addEventListener('click', (e) => {
                    const rect = progressContainer.getBoundingClientRect();
                    const pct = (e.clientX - rect.left) / rect.width;
                    video.currentTime = pct * video.duration;
                });

                // Volume
                volumeSlider.addEventListener('input', () => {
                    video.volume = volumeSlider.value;
                    video.muted = false;
                    updateVolumeIcon();
                });
                btnMute.addEventListener('click', () => {
                    video.muted = !video.muted;
                    updateVolumeIcon();
                });
                function updateVolumeIcon() {
                    if (video.muted || video.volume === 0) {
                        iconVolOn.classList.add('hidden');
                        iconVolOff.classList.remove('hidden');
                    } else {
                        iconVolOn.classList.remove('hidden');
                        iconVolOff.classList.add('hidden');
                    }
                }

                // Plein écran
                btnFullscreen.addEventListener('click', () => {
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        container.requestFullscreen().catch(() => {});
                    }
                });
                document.addEventListener('fullscreenchange', () => {
                    if (document.fullscreenElement) {
                        iconFsEnter.classList.add('hidden');
                        iconFsExit.classList.remove('hidden');
                        controls.classList.add('opacity-100');
                    } else {
                        iconFsEnter.classList.remove('hidden');
                        iconFsExit.classList.add('hidden');
                    }
                });

                // Raccourcis clavier
                container.addEventListener('keydown', (e) => {
                    if (e.key === ' ' || e.key === 'k') { e.preventDefault(); video.paused ? video.play() : video.pause(); }
                    if (e.key === 'ArrowRight') { video.currentTime = Math.min(video.currentTime + 10, video.duration); }
                    if (e.key === 'ArrowLeft') { video.currentTime = Math.max(video.currentTime - 10, 0); }
                    if (e.key === 'm') { video.muted = !video.muted; updateVolumeIcon(); }
                    if (e.key === 'f') { btnFullscreen.click(); }
                });
                container.setAttribute('tabindex', '0');

                // Fin de vidéo : envoyer la complétion
                video.addEventListener('ended', () => {
                    sendVideoCompletion();
                    playOverlay.classList.remove('opacity-0', 'pointer-events-none');
                });
            })();
            @endif
        })();
        </script>
        @endpush
    @endif

    {{-- Liste des leçons : grid de cards --}}
    <section class="px-4 sm:px-6 lg:px-8 2xl:px-12 pb-8 sm:pb-12">
        <div class="max-w-6xl xl:max-w-7xl 2xl:max-w-[1400px] mx-auto">
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
