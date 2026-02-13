<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 transform translate-y-full transition-transform duration-300 ease-in-out hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 text-center sm:text-left">
                <p class="text-slate-600 dark:text-slate-300 text-sm">
                    Nous utilisons des cookies pour optimiser votre expérience, analyser notre trafic et sécuriser nos services.
                    Nous utilisons également des <strong>trackers de visite</strong> pour aider les professionnels (Tata) à améliorer et simplifier leurs activités en leur fournissant des statistiques anonymisées sur les visites de leur page publique.
                    Pour en savoir plus, consultez notre 
                    <a href="{{ route('legal.cookies') }}" class="text-green-600 dark:text-green-400 font-medium hover:underline">Politique relative aux cookies</a>.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto flex-shrink-0">
                <button 
                    id="cookie-refuse"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition"
                >
                    Refuser
                </button>
                <button 
                    id="cookie-customize"
                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition"
                >
                    Personnaliser
                </button>
                <button 
                    id="cookie-accept"
                    class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5"
                >
                    Tout accepter
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

<!-- Panneau de personnalisation des cookies -->
<div id="cookie-panel" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" id="cookie-panel-overlay"></div>
    <div class="fixed inset-y-0 right-0 w-full max-w-md bg-white dark:bg-slate-900 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto" id="cookie-panel-content">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Paramètres des cookies</h3>
                <button id="cookie-panel-close" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                Choisissez les types de cookies que vous acceptez. Les cookies essentiels sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.
            </p>

            <div class="space-y-4">
                <!-- Cookies essentiels -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Cookies essentiels</h4>
                        <span class="px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 rounded-full">Toujours actifs</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Nécessaires au fonctionnement du site : authentification, sécurité, préférences de thème. Sans ces cookies, le site ne peut pas fonctionner correctement.
                    </p>
                </div>

                <!-- Cookies analytiques -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Cookies analytiques</h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="cookie-analytics" class="sr-only peer" checked>
                            <div class="w-9 h-5 bg-slate-300 dark:bg-slate-600 peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Nous aident à comprendre comment les visiteurs interagissent avec le site. Ces données anonymisées permettent aussi aux professionnels de suivre les visites sur leur page.
                    </p>
                </div>

                <!-- Cookies marketing -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Cookies marketing</h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="cookie-marketing" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-300 dark:bg-slate-600 peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Utilisés pour vous proposer des contenus et publicités pertinents. Ces cookies permettent de mesurer l'efficacité de nos campagnes.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button id="cookie-save-preferences" class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 rounded-lg transition">
                    Sauvegarder mes préférences
                </button>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('legal.cookies') }}" class="text-xs text-green-600 dark:text-green-400 hover:underline">
                    Consulter notre politique de cookies
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookie-banner');
        const panel = document.getElementById('cookie-panel');
        const panelContent = document.getElementById('cookie-panel-content');
        if (!banner) return;

        // Lire les préférences existantes
        function getCookiePreferences() {
            try {
                const raw = localStorage.getItem('allo_tata_cookie_preferences');
                return raw ? JSON.parse(raw) : null;
            } catch (e) { return null; }
        }

        function saveCookiePreferences(prefs) {
            localStorage.setItem('allo_tata_cookie_preferences', JSON.stringify(prefs));
            localStorage.setItem('allo_tata_cookie_consent', prefs.consent);
        }

        // Vérifier si le consentement a déjà été donné
        const consent = localStorage.getItem('allo_tata_cookie_consent');

        if (!consent) {
            banner.classList.remove('hidden');
            setTimeout(() => { banner.classList.remove('translate-y-full'); }, 100);
        }

        // Charger les préférences existantes dans le panneau
        const existing = getCookiePreferences();
        if (existing) {
            document.getElementById('cookie-analytics').checked = existing.analytics !== false;
            document.getElementById('cookie-marketing').checked = existing.marketing === true;
        }

        // Tout accepter
        document.getElementById('cookie-accept').addEventListener('click', function() {
            saveCookiePreferences({ consent: 'accepted', essential: true, analytics: true, marketing: true });
            syncConsent(true);
            hideBanner();
            hidePanel();
        });

        // Refuser (seuls les essentiels)
        document.getElementById('cookie-refuse').addEventListener('click', function() {
            saveCookiePreferences({ consent: 'refused', essential: true, analytics: false, marketing: false });
            syncConsent(false);
            hideBanner();
            hidePanel();
        });

        // Personnaliser
        document.getElementById('cookie-customize').addEventListener('click', function() {
            showPanel();
        });

        // Sauvegarder les préférences
        document.getElementById('cookie-save-preferences').addEventListener('click', function() {
            const analytics = document.getElementById('cookie-analytics').checked;
            const marketing = document.getElementById('cookie-marketing').checked;
            saveCookiePreferences({ consent: 'custom', essential: true, analytics: analytics, marketing: marketing });
            syncConsent(analytics);
            hideBanner();
            hidePanel();
        });

        // Fermer le panneau
        document.getElementById('cookie-panel-close').addEventListener('click', hidePanel);
        document.getElementById('cookie-panel-overlay').addEventListener('click', hidePanel);

        // Fermer la bannière (mobile)
        document.getElementById('cookie-close').addEventListener('click', function() {
            hideBanner();
        });

        function showPanel() {
            panel.classList.remove('hidden');
            setTimeout(() => { panelContent.classList.remove('translate-x-full'); }, 10);
        }

        function hidePanel() {
            if (!panel) return;
            panelContent.classList.add('translate-x-full');
            setTimeout(() => { panel.classList.add('hidden'); }, 300);
        }

        function hideBanner() {
            banner.classList.add('translate-y-full');
            setTimeout(() => { banner.classList.add('hidden'); }, 300);
        }

        function syncConsent(trackingConsent) {
            @auth
                fetch('{{ route("settings.confidentialite.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ tracking_consent: trackingConsent })
                }).catch(error => {
                    console.debug('Erreur lors de la mise à jour du consentement:', error);
                });
            @endauth
        }
    });
</script>
