{{-- Bannière d'activation des notifications push --}}
{{-- Affichée si : navigateur compatible + pas de subscription push + pas dismiss --}}
@auth
    @if(!auth()->user()->push_banner_dismissed_at)
        <div id="push-banner" class="hidden relative z-40">
            <div class="bg-gradient-to-r from-green-600 to-emerald-500 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-12 text-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="hidden sm:inline">Activez les notifications push pour ne rien manquer de vos réservations et messages.</span>
                            <span class="sm:hidden">Activez les notifications push !</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="push-banner-activate"
                                class="px-3 py-1 bg-white text-green-700 text-xs font-semibold rounded-md hover:bg-green-50 transition-colors">
                                Activer
                            </button>
                            <button type="button" id="push-banner-dismiss"
                                class="p-1 hover:bg-green-700/50 rounded transition-colors" aria-label="Fermer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const banner = document.getElementById('push-banner');
                if (!banner) return;

                const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
                if (!supported) return;

                // Ne pas afficher si déjà autorisé ou refusé
                if (Notification.permission !== 'default') return;

                // Vérifier si l'utilisateur a déjà une subscription
                navigator.serviceWorker.ready.then(reg => {
                    reg.pushManager.getSubscription().then(sub => {
                        if (!sub) {
                            banner.classList.remove('hidden');
                        }
                    });
                });

                // Bouton Activer
                document.getElementById('push-banner-activate')?.addEventListener('click', async function() {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        try {
                            const reg = await navigator.serviceWorker.ready;
                            const vapidKey = '{{ config("webpush.vapid.public_key") }}';
                            const padding = '='.repeat((4 - vapidKey.length % 4) % 4);
                            const base64 = (vapidKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                            const rawData = window.atob(base64);
                            const outputArray = new Uint8Array(rawData.length);
                            for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);

                            const subscription = await reg.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: outputArray,
                            });

                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                                || document.querySelector('input[name="_token"]')?.value;

                            await fetch('/push-subscription', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    endpoint: subscription.endpoint,
                                    keys: {
                                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                                    },
                                    content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                                }),
                            });
                        } catch (e) {
                            console.error('Erreur activation push banner:', e);
                        }
                    }
                    banner.classList.add('hidden');
                });

                // Bouton Dismiss (Plus tard)
                document.getElementById('push-banner-dismiss')?.addEventListener('click', async function() {
                    banner.classList.add('hidden');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;
                    try {
                        await fetch('/push-subscription/dismiss-banner', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (e) { /* silently fail */ }
                });
            });
        </script>
    @endif
@endauth
