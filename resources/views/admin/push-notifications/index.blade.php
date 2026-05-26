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
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-green-500 focus:ring-green-500">{{ old('body') }}</textarea>
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

                <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Envoyer la notification
                </button>
            </form>
        </div>
    </div>

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
</script>
@endsection
