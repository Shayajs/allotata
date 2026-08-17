<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Installer l'application</h2>
    </div>

    <!-- PWA / App Native -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 text-white relative overflow-hidden group">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-green-500 rounded-full opacity-10 blur-3xl group-hover:opacity-20 transition duration-500"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
            <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/10">
                <img src="{{ ($subdomainHost['mode'] ?? '') === 'tenant' ? url('/manage/icon/192.png') : route('manifest.icon', ['slug' => $entreprise->slug, 'size' => 192]) }}" alt="App Icon" class="w-24 h-24 rounded-xl shadow-2xl">
            </div>
            
            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                    <h3 class="text-2xl font-bold text-white">App {{ $entreprise->nom }}</h3>

                </div>
                <p class="text-slate-300 mb-6 max-w-xl">
                    Installez l'application officielle {{ $entreprise->nom }} sur votre appareil pour un accès direct à votre espace client, vos réservations et vos documents.
                </p>

                @php
                    $onTenantHost = ($subdomainHost['mode'] ?? '') === 'tenant';
                    $subdomainsOn = \App\Support\SubdomainHost::enabled();
                    $tenantManageUrl = \App\Support\SubdomainHost::tenantUrl($entreprise->slug, '/manage');
                @endphp

                @if($onTenantHost)
                <button
                    id="pwa-install-btn-entreprise"
                    onclick="window.installPwa()"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-orange-500 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg relative overflow-hidden group"
                >
                    <svg id="pwa-install-icon-entreprise" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span id="pwa-install-text-entreprise">Installer maintenant</span>
                </button>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                        if (isStandalone) {
                            const btn = document.getElementById('pwa-install-btn-entreprise');
                            const text = document.getElementById('pwa-install-text-entreprise');
                            if (btn && text) {
                                text.textContent = 'Déjà installé';
                                btn.classList.remove('from-green-500', 'to-orange-500', 'hover:scale-105');
                                btn.classList.add('bg-slate-700', 'cursor-default', 'opacity-90');
                                btn.onclick = null;
                            }
                        }
                    });
                </script>
                @else
                <button
                    id="pwa-install-btn-entreprise"
                    disabled
                    class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 text-slate-400 font-semibold rounded-xl cursor-not-allowed opacity-75 shadow-lg relative overflow-hidden"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Bientôt disponible</span>
                </button>
                @endif

                @if($subdomainsOn)
                <p class="mt-4 text-sm">
                    <a href="{{ $tenantManageUrl }}" class="text-green-400 hover:text-green-300 underline">
                        Tester l'app : {{ $entreprise->slug }}.{{ \App\Support\SubdomainHost::baseDomain() }}/manage
                    </a>
                </p>
                @endif

                <p class="mt-4 text-xs text-slate-400">
                    <span class="block sm:inline">📱 Compatible iOS & Android</span>
                    <span class="hidden sm:inline mx-2">•</span>
                    <span class="block sm:inline">💻 Compatible Windows, Mac & Linux</span>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Windows -->
        <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 opacity-60 grayscale relative overflow-hidden">
            <div class="absolute inset-0 bg-slate-100/50 dark:bg-slate-900/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
                <span class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-full border border-slate-300 dark:border-slate-600">
                    BIENTÔT DISPONIBLE
                </span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div class="w-12 h-12 mb-4 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4h-13.051M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Windows</h3>
                <p class="text-xs text-slate-500">Application native .exe</p>
            </div>
        </div>

        <!-- Mac/iPhone -->
        <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 opacity-60 grayscale relative overflow-hidden">
             <div class="absolute inset-0 bg-slate-100/50 dark:bg-slate-900/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
                <span class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-full border border-slate-300 dark:border-slate-600">
                    BIENTÔT DISPONIBLE
                </span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div class="w-12 h-12 mb-4 bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-700 dark:text-slate-300">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.79-1.31.02-2.3-1.23-3.17-2.43-1.75-2.5-2.98-7.09-.04-9.64 1.46-1.26 2.54-1.71 3.48-1.71.97 0 1.88.66 2.48.66.59 0 1.57-.82 2.76-.7 2.19.16 3.07 1.09 3.07 1.09s-1.72 1.1-1.68 3.55c.03 2.55 2.22 3.44 2.22 3.44s-.65 1.77-1.55 3.07zM15.5 5.5c.84-1.18 1.4-2.85 1.23-4.5-1.5 0-2.88.94-3.53 2.05-.72 1.21-1.23 2.91 1 2.98.05-.01.07-.02.04-.02h1.26"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">macOS</h3>
                <p class="text-xs text-slate-500">Application native .dmg</p>
            </div>
        </div>

        @include('partials.android-store-card')

        <!-- Linux -->
        <div class="p-6 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 opacity-60 grayscale relative overflow-hidden">
             <div class="absolute inset-0 bg-slate-100/50 dark:bg-slate-900/50 z-10 flex items-center justify-center backdrop-blur-[1px]">
                <span class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-full border border-slate-300 dark:border-slate-600">
                    BIENTÔT DISPONIBLE
                </span>
            </div>
            <div class="flex flex-col items-center text-center">
                <div class="w-12 h-12 mb-4 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center text-orange-600 dark:text-orange-400">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.003 0c-1.93 0-3.193.955-3.193 2.924 0 2.21 2.38 3.748 2.38 6.516 0 1.258-2.673 2.186-2.673 4.887 0 2.822 3.19 2.506 3.013 4.293-.178 1.787-2.607 1.49-2.329 4.36l.169 1.02h5.27l.169-1.02c.277-2.87-2.152-2.573-2.33-4.36-.176-1.787 3.014-1.47 3.014-4.293 0-2.7-2.672-3.63-2.672-4.887 0-2.768 2.38-4.306 2.38-6.516C15.196.955 13.934 0 12.003 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Linux</h3>
                <p class="text-xs text-slate-500">Paquet .deb / .snap</p>
            </div>
        </div>
    </div>
</div>
