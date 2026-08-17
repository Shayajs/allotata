<div id="pwa-install-banner" class="hide-on-capacitor pwa-hide-mobile fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 transform translate-y-full transition-transform duration-300 ease-in-out hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 flex items-center gap-4 text-center sm:text-left">
                <div class="hidden sm:block">
                    @php
                        $bannerIcon = \App\Helpers\SiteHelper::getLogo('pwa') ?: \App\Helpers\SiteHelper::getAllotataLogoUrl();
                        $bannerIconFile = public_path('icons/icon-192x192.png');
                        $bannerIconFallback = '/icons/icon-192x192.png'.(is_file($bannerIconFile) ? '?v='.filemtime($bannerIconFile) : '');
                    @endphp
                    <img src="{{ $bannerIcon ?: $bannerIconFallback }}" alt="Allo Tata" class="w-12 h-12 rounded-xl shadow-sm object-cover bg-slate-900">
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg">Installez l'application Allo Tata</h3>
                    <p class="text-slate-600 dark:text-slate-300 text-sm">
                        Pour une expérience plus fluide et un accès rapide à votre tableau de bord, installez notre application sur votre mobile.
                    </p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <button 
                    id="pwa-dismiss"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition"
                >
                    Passer
                </button>
                <button 
                    id="pwa-install"
                    class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Installer maintenant
                </button>
            </div>
        </div>
    </div>
</div>

</script>
<script>
    // Variable globale pour stocker l'événement d'installation
    window.deferredPrompt = null;

    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('pwa-install-banner');
        
        // Clé pour le stockage du refus
        const PWA_DISMISSED_KEY = 'allo_tata_pwa_dismissed';

        // Détection Mobile/iOS
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        
        // Fonction globale d'installation (appelable depuis n'importe où)
        window.installPwa = async function() {
            if (window.deferredPrompt) {
                // Montrer l'invite native
                window.deferredPrompt.prompt();
                const { outcome } = await window.deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                window.deferredPrompt = null;
            } else if (isIOS) {
                alert("Pour installer l'application sur iOS :\n1. Appuyez sur le bouton 'Partager' (carré avec flèche) du navigateur\n2. Sélectionnez 'Sur l'écran d'accueil'");
            } else {
                alert("Pour installer l'application :\nOuvrez le menu de votre navigateur (3 points) et sélectionnez 'Installer l'application' ou 'Ajouter à l'écran d'accueil'.");
            }
        };

        // --- Logique de la bannière ---

        // Détection si déjà en mode PWA standalone
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        // Si déjà installé, on ne fait rien pour la bannière
        if (isStandalone) {
            return;
        }

        // Écouter l'événement beforeinstallprompt (Android/Chrome)
        window.addEventListener('beforeinstallprompt', (e) => {
            // Empêcher Chrome d'afficher sa bannière native tout de suite
            e.preventDefault();
            // Sauvegarder l'événement globalement
            window.deferredPrompt = e;

            // Afficher notre bannière custom SI mobile et PAS refusé
            if (isMobile && !getCookie(PWA_DISMISSED_KEY)) {
                showBanner();
            }
        });

        // Pour iOS (pas d'event), on affiche si mobile et pas refusé
        if (isIOS && !getCookie(PWA_DISMISSED_KEY)) {
            showBanner();
        }

        function showBanner() {
            if (!banner) return;
            const cookieBanner = document.getElementById('cookie-banner');
            const delay = cookieBanner && !cookieBanner.classList.contains('hidden') ? 1000 : 2000;
            
            setTimeout(() => {
                banner.classList.remove('hidden');
                setTimeout(() => {
                    banner.classList.remove('translate-y-full');
                }, 100);
            }, delay);
        }

        function hideBanner() {
            if (!banner) return;
            banner.classList.add('translate-y-full');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 300);
        }

        // Clic sur "Installer" dans la bannière
        const installBtn = document.getElementById('pwa-install');
        if (installBtn) {
            installBtn.addEventListener('click', () => {
                window.installPwa();
                hideBanner();
            });
        }

        // Clic sur "Passer"
        const dismissBtn = document.getElementById('pwa-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                setCookie(PWA_DISMISSED_KEY, 'true', 7); // Mémoriser pendant 7 jours
                hideBanner();
            });
        }

        // Helpers Cookies
        function setCookie(name, value, days) {
            let expires = "";
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days*24*60*60*1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/; SameSite=Lax";
        }

        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for(let i=0;i < ca.length;i++) {
                let c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }
    });
</script>
