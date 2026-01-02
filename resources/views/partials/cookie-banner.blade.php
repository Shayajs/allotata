<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 transform translate-y-full transition-transform duration-300 ease-in-out hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 text-center sm:text-left">
                <p class="text-slate-600 dark:text-slate-300 text-sm">
                    Nous utilisons des cookies pour optimiser votre expérience, analyser notre trafic et sécuriser nos services.
                    Pour en savoir plus, consultez notre 
                    <a href="{{ route('legal.cookies') }}" class="text-green-600 dark:text-green-400 font-medium hover:underline">Politique relative aux cookies</a>.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                {{-- <button 
                    id="cookie-refuse"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition"
                >
                    Continuer sans accepter
                </button> --}}
                <button 
                    id="cookie-accept"
                    class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5"
                >
                    J'accepte
                </button>
                <button 
                    id="cookie-close"
                    class="absolute top-2 right-2 sm:hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookie-banner');
        if (!banner) return;

        // Vérifier si le consentement a déjà été donné
        const consent = localStorage.getItem('allo_tata_cookie_consent');

        if (!consent) {
            // Afficher la bannière avec un petit délai pour l'effet d'animation
            banner.classList.remove('hidden');
            setTimeout(() => {
                banner.classList.remove('translate-y-full');
            }, 100);
        }

        // Gestion du clic sur "J'accepte"
        document.getElementById('cookie-accept').addEventListener('click', function() {
            localStorage.setItem('allo_tata_cookie_consent', 'accepted');
            hideBanner();
        });

        // Gestion du clic sur "Refuser" (optionnel, pour l'instant masqué)
        /*
        document.getElementById('cookie-refuse').addEventListener('click', function() {
            localStorage.setItem('allo_tata_cookie_consent', 'refused');
            hideBanner();
        });
        */

        // Gestion de la fermeture (mobile)
        document.getElementById('cookie-close').addEventListener('click', function() {
            // On ne stocke pas de choix permanent, la bannière reviendra à la prochaine session
            hideBanner();
        });

        function hideBanner() {
            banner.classList.add('translate-y-full');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 300);
        }
    });
</script>
