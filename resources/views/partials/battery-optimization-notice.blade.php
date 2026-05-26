<div id="battery-optimization-notice" class="hidden">
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-4 relative">
        <button onclick="dismissBatteryNotice()" class="absolute top-2 right-2 text-amber-400 hover:text-amber-600 dark:hover:text-amber-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="flex items-start gap-3 pr-6">
            <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <div>
                <p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">Notifications fiables sur votre appareil</p>
                <p class="text-amber-700 dark:text-amber-400 text-sm mt-1" id="battery-instructions"></p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (document.cookie.includes('battery_notice_dismissed=1')) return;
    if (Notification.permission !== 'granted') return;

    var isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (!isPwa) return;

    var ua = navigator.userAgent.toLowerCase();
    var instructions = null;

    if (ua.includes('samsung')) {
        instructions = 'Pour recevoir vos notifications sans interruption : Paramètres > Applications > Allo Tata > Batterie > Non restreinte.';
    } else if (ua.includes('xiaomi') || ua.includes('redmi') || ua.includes('poco') || ua.includes('miui')) {
        instructions = 'Pour recevoir vos notifications sans interruption : Paramètres > Applications > Gérer les apps > Allo Tata > Autostart activé + Pas de restrictions batterie.';
    } else if (ua.includes('huawei') || ua.includes('honor')) {
        instructions = 'Pour recevoir vos notifications sans interruption : Paramètres > Batterie > Lancement d\'applications > Allo Tata > Gérer manuellement (tout activer).';
    } else if (ua.includes('oppo') || ua.includes('realme') || ua.includes('oneplus')) {
        instructions = 'Pour recevoir vos notifications sans interruption : Paramètres > Batterie > Optimisation de la batterie > Allo Tata > Ne pas optimiser.';
    } else if (/android/.test(ua)) {
        instructions = 'Pour recevoir vos notifications sans interruption, désactivez l\'optimisation de la batterie pour cette application dans vos paramètres.';
    }

    if (!instructions) return;

    document.getElementById('battery-instructions').textContent = instructions;
    document.getElementById('battery-optimization-notice').classList.remove('hidden');
})();

function dismissBatteryNotice() {
    document.getElementById('battery-optimization-notice').classList.add('hidden');
    var d = new Date();
    d.setDate(d.getDate() + 30);
    document.cookie = 'battery_notice_dismissed=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
}
</script>
