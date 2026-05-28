@extends('admin.layout')

@section('title', 'Notifications Push')
@section('header', 'Notifications Push')
@section('subheader', 'Envoyez des notifications push personnalisées aux utilisateurs.')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Formulaire d'envoi --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Envoyer une notification</h3>

            <form method="POST" action="{{ route('admin.push-notifications.send') }}">
                @csrf

                {{-- Destinataire --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Destinataire</label>
                    <select name="target_type" id="target-type" onchange="toggleUserSearch()" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                        <option value="user">Utilisateur spécifique</option>
                        <option value="all">Tous les abonnés push</option>
                    </select>
                </div>

                {{-- Recherche utilisateur --}}
                <div id="user-search-container" class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rechercher un utilisateur</label>
                    <div class="relative">
                        <input type="text" id="user-search" placeholder="Nom ou email..." autocomplete="off"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                        <input type="hidden" name="user_id" id="user-id">
                        <div id="user-results" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                    </div>
                    <p id="selected-user" class="mt-2 text-sm text-green-600 dark:text-green-400 hidden"></p>
                </div>

                {{-- Catégorie --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Catégorie</label>
                    <select name="category" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                        <option value="general">Général</option>
                        <option value="reservation">Réservation</option>
                        <option value="paiement">Paiement</option>
                        <option value="message">Message</option>
                        <option value="rappel">Rappel</option>
                        <option value="promotion">Promotion</option>
                        <option value="mise_a_jour">Mise à jour</option>
                    </select>
                </div>

                {{-- Titre --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Titre</label>
                    <input type="text" name="title" required maxlength="100" placeholder="Titre de la notification"
                        value="{{ old('title') }}"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                </div>

                {{-- Message --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Message</label>
                    <textarea name="body" required maxlength="500" rows="3" placeholder="Corps de la notification"
                        class="ui-textarea w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">{{ old('body') }}</textarea>
                </div>

                {{-- URL --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">URL de redirection <span class="text-slate-400">(optionnel)</span></label>
                    <input type="text" name="url" placeholder="/dashboard" value="{{ old('url') }}"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">
                </div>

                {{-- Créer aussi une notification in-app --}}
                <div class="mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="create_notification" value="1" checked
                            class="rounded border-slate-300 dark:border-slate-600 text-green-500 focus:ring-green-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Créer aussi une notification in-app</span>
                    </label>
                </div>

                <button type="submit" class="ui-btn-simple w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Envoyer la notification
                </button>
            </form>
        </div>
    </div>

    {{-- Diagnostic --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Diagnostic Push Notifications</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Teste chaque étape du cheminement sur CE navigateur/appareil.</p>

            <div id="diag-results" class="space-y-3 mb-6"></div>

            <button onclick="runDiagnostic()" id="diag-btn" class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Lancer le diagnostic complet
            </button>

            <div id="diag-test-push" class="mt-4 hidden">
                <button onclick="sendTestPush()" class="w-full px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition">
                    Envoyer une notification push de test (sur cet appareil)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
    {{-- Stats --}}
    <div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statistiques</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Abonnés push</span>
                    <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ $stats['total_subscribers'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Souscriptions totales</span>
                    <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $stats['total_subscriptions'] }}</span>
                </div>
            </div>
        </div>

        {{-- Historique récent --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Envois récents</h3>
            @if($recentNotifications->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">Aucun envoi récent.</p>
            @else
                <div class="space-y-3">
                    @foreach($recentNotifications as $notif)
                        <div class="border-l-4 border-green-500 pl-3 py-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $notif->titre }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $notif->user->name ?? 'Broadcast' }} &middot; {{ $notif->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleUserSearch() {
    var container = document.getElementById('user-search-container');
    container.style.display = document.getElementById('target-type').value === 'user' ? 'block' : 'none';
}

(function() {
    var searchInput = document.getElementById('user-search');
    var resultsDiv = document.getElementById('user-results');
    var userIdInput = document.getElementById('user-id');
    var selectedUser = document.getElementById('selected-user');
    var debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var query = this.value.trim();
        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }
        debounceTimer = setTimeout(function() {
            fetch('/admin/push-notifications/search-users?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(users) {
                resultsDiv.innerHTML = '';
                if (users.length === 0) {
                    resultsDiv.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Aucun résultat</div>';
                } else {
                    users.forEach(function(u) {
                        var hasPush = u.has_push ? '<span class="text-green-500 text-xs ml-1">push actif</span>' : '<span class="text-slate-400 text-xs ml-1">pas de push</span>';
                        var div = document.createElement('div');
                        div.className = 'px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-600 cursor-pointer text-sm';
                        div.innerHTML = '<span class="font-medium text-slate-900 dark:text-white">' + u.name + '</span> <span class="text-slate-500 dark:text-slate-400">' + u.email + '</span>' + hasPush;
                        div.addEventListener('click', function() {
                            userIdInput.value = u.id;
                            searchInput.value = u.name + ' (' + u.email + ')';
                            selectedUser.textContent = 'Sélectionné : ' + u.name + (u.has_push ? '' : ' (attention : pas d\'abonnement push)');
                            selectedUser.classList.remove('hidden');
                            resultsDiv.classList.add('hidden');
                        });
                        resultsDiv.appendChild(div);
                    });
                }
                resultsDiv.classList.remove('hidden');
            });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!resultsDiv.contains(e.target) && e.target !== searchInput) {
            resultsDiv.classList.add('hidden');
        }
    });
})();

// ── Diagnostic ──
var diagResults = document.getElementById('diag-results');

function addStep(id, label) {
    var div = document.createElement('div');
    div.id = 'step-' + id;
    div.className = 'flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50';
    div.innerHTML = '<div class="w-6 h-6 rounded-full bg-slate-300 dark:bg-slate-600 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-slate-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"></path></svg></div><div><p class="text-sm font-medium text-slate-700 dark:text-slate-300">' + label + '</p><p class="text-xs text-slate-500 dark:text-slate-400" id="detail-' + id + '">En cours...</p></div>';
    diagResults.appendChild(div);
}

function setStepResult(id, success, detail) {
    var step = document.getElementById('step-' + id);
    if (!step) return;
    var icon = success
        ? '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>'
        : '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>';
    var color = success ? 'bg-green-500' : 'bg-red-500';
    step.querySelector('.w-6').className = 'w-6 h-6 rounded-full ' + color + ' flex items-center justify-center flex-shrink-0';
    step.querySelector('.w-6').innerHTML = icon;
    document.getElementById('detail-' + id).textContent = detail;
}

async function runDiagnostic() {
    diagResults.innerHTML = '';
    document.getElementById('diag-test-push').classList.add('hidden');
    document.getElementById('diag-btn').disabled = true;
    document.getElementById('diag-btn').textContent = 'Diagnostic en cours...';

    // 1. Support navigateur
    addStep('support', 'Support du navigateur');
    await sleep(300);
    var hasSW = 'serviceWorker' in navigator;
    var hasPush = 'PushManager' in window;
    var hasNotif = 'Notification' in window;
    if (hasSW && hasPush && hasNotif) {
        setStepResult('support', true, 'Service Worker, PushManager et Notification API disponibles');
    } else {
        var missing = [];
        if (!hasSW) missing.push('Service Worker');
        if (!hasPush) missing.push('PushManager');
        if (!hasNotif) missing.push('Notification API');
        setStepResult('support', false, 'Manquant : ' + missing.join(', ') + '. Ce navigateur ne supporte pas les push notifications.');
        done(); return;
    }

    // 2. Service Worker enregistré
    addStep('sw', 'Service Worker enregistré');
    await sleep(300);
    try {
        var reg = await navigator.serviceWorker.getRegistration();
        if (reg) {
            setStepResult('sw', true, 'Scope: ' + reg.scope + ' | Actif: ' + (reg.active ? 'oui' : 'non'));
        } else {
            setStepResult('sw', false, 'Aucun Service Worker enregistré. Rechargez la page.');
            done(); return;
        }
    } catch(e) {
        setStepResult('sw', false, 'Erreur: ' + e.message);
        done(); return;
    }

    // 3. Permission notifications
    addStep('perm', 'Permission notifications');
    await sleep(300);
    var perm = Notification.permission;
    if (perm === 'granted') {
        setStepResult('perm', true, 'Permission accordée');
    } else if (perm === 'denied') {
        setStepResult('perm', false, 'Permission REFUSÉE. Allez dans les paramètres du navigateur pour la réactiver.');
        done(); return;
    } else {
        setStepResult('perm', false, 'Permission non demandée (default). Cliquez sur "Activer les notifications" dans la bannière.');
        done(); return;
    }

    // 4. Clé VAPID
    addStep('vapid', 'Clé VAPID configurée');
    await sleep(300);
    if (window.VAPID_PUBLIC_KEY && window.VAPID_PUBLIC_KEY.length > 10) {
        setStepResult('vapid', true, 'Clé publique: ' + window.VAPID_PUBLIC_KEY.substring(0, 20) + '...');
    } else {
        setStepResult('vapid', false, 'Clé VAPID absente ou invalide. Vérifiez VAPID_PUBLIC_KEY dans le .env');
        done(); return;
    }

    // 5. Souscription PushManager
    addStep('sub', 'Souscription push active');
    await sleep(300);
    try {
        var registration = await navigator.serviceWorker.ready;
        var subscription = await registration.pushManager.getSubscription();
        if (subscription) {
            setStepResult('sub', true, 'Endpoint: ' + subscription.endpoint.substring(0, 60) + '...');
        } else {
            setStepResult('sub', false, 'Aucune souscription. Tentative de re-souscription...');
            // Tenter de souscrire
            try {
                var padding = '='.repeat((4 - window.VAPID_PUBLIC_KEY.length % 4) % 4);
                var base64 = (window.VAPID_PUBLIC_KEY + padding).replace(/-/g, '+').replace(/_/g, '/');
                var rawData = atob(base64);
                var outputArray = new Uint8Array(rawData.length);
                for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);

                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: outputArray
                });
                // Envoyer au serveur
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                var resp = await fetch('/push-subscription', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint,
                        keys: {
                            p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                            auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
                        },
                        content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0]
                    })
                });
                if (resp.ok) {
                    setStepResult('sub', true, 'Souscription créée et envoyée au serveur !');
                } else {
                    setStepResult('sub', false, 'Souscription créée mais le serveur a refusé (HTTP ' + resp.status + ')');
                    done(); return;
                }
            } catch(subErr) {
                setStepResult('sub', false, 'Impossible de souscrire : ' + subErr.message);
                done(); return;
            }
        }
    } catch(e) {
        setStepResult('sub', false, 'Erreur: ' + e.message);
        done(); return;
    }

    // 6. Souscription côté serveur
    addStep('server', 'Souscription enregistrée côté serveur');
    await sleep(300);
    try {
        var checkResp = await fetch('/admin/push-notifications/check-subscription', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        var checkData = await checkResp.json();
        if (checkData.has_subscription) {
            setStepResult('server', true, checkData.count + ' souscription(s) en BDD pour votre compte');
        } else {
            setStepResult('server', false, 'Aucune souscription trouvée en BDD pour votre compte');
            done(); return;
        }
    } catch(e) {
        setStepResult('server', false, 'Erreur vérification serveur: ' + e.message);
        done(); return;
    }

    // 7. Notification locale (sans serveur)
    addStep('local', 'Notification locale (sans serveur)');
    await sleep(300);
    try {
        await registration.showNotification('Test Allo Tata', {
            body: 'Si vous voyez ceci, les notifications locales fonctionnent !',
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-192x192.png',
            tag: 'diagnostic-local',
            vibrate: [200, 100, 200]
        });
        setStepResult('local', true, 'Notification locale affichée. Vous devriez la voir maintenant.');
    } catch(e) {
        setStepResult('local', false, 'Impossible d\'afficher une notification: ' + e.message);
        done(); return;
    }

    // Tout OK — proposer le test push serveur
    document.getElementById('diag-test-push').classList.remove('hidden');
    done();
}

async function sendTestPush() {
    var btn = document.querySelector('#diag-test-push button');
    btn.disabled = true;
    btn.textContent = 'Envoi en cours...';

    try {
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var resp = await fetch('/admin/push-notifications/test-self', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
        var data = await resp.json();

        addStep('push', 'Push serveur vers cet appareil');
        if (data.success) {
            setStepResult('push', true, data.message);
        } else {
            setStepResult('push', false, data.message);
        }
    } catch(e) {
        addStep('push', 'Push serveur vers cet appareil');
        setStepResult('push', false, 'Erreur: ' + e.message);
    }

    btn.disabled = false;
    btn.textContent = 'Envoyer une notification push de test (sur cet appareil)';
}

function done() {
    document.getElementById('diag-btn').disabled = false;
    document.getElementById('diag-btn').textContent = 'Relancer le diagnostic';
}

function sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }
</script>
@endsection
