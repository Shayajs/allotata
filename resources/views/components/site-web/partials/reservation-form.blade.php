{{-- Formulaire de réservation embarqué (guest + connecté) --}}
@php
    $user = auth()->user();
    $storeUrl = route('site-web.reservation.store', ['slug' => $slug]);
    $popupUrl = route('auth.popup');
@endphp

<div class="rounded-2xl shadow-xl border p-6 sticky top-6" style="background: var(--site-background); border-color: color-mix(in srgb, var(--site-text) 15%, transparent);">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--site-primary);">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold" style="color: var(--site-text);">Réserver</h2>
            <p class="text-sm opacity-50" style="color: var(--site-text);">Sélectionnez un créneau</p>
        </div>
    </div>

    {{-- Bouton connexion si guest --}}
    @if(!$user)
        <div class="mb-5 p-3 rounded-xl border flex items-center justify-between"
             style="background: color-mix(in srgb, var(--site-primary) 5%, var(--site-background)); border-color: color-mix(in srgb, var(--site-primary) 20%, transparent);">
            <span class="text-sm opacity-70" style="color: var(--site-text);">Connectez-vous pour pré-remplir</span>
            <button type="button" onclick="window.__openAuthPopup()" class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition hover:opacity-90" style="background: var(--site-primary);">
                Se connecter
            </button>
        </div>
    @else
        <div class="mb-5 p-3 rounded-xl border flex items-center gap-3"
             style="background: color-mix(in srgb, var(--site-primary) 5%, var(--site-background)); border-color: color-mix(in srgb, var(--site-primary) 20%, transparent);">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background: var(--site-primary);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium truncate" style="color: var(--site-text);">{{ $user->name }}</p>
                <p class="text-xs opacity-50 truncate" style="color: var(--site-text);">{{ $user->email }}</p>
            </div>
        </div>
    @endif

    <form action="{{ $storeUrl }}" method="POST" id="sw-reservation-form">
        @csrf
        <div class="space-y-4">

            {{-- Service --}}
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Service</label>
                <select name="type_service_id" required
                        class="w-full px-4 py-3 text-sm border-2 rounded-xl focus:outline-none transition-colors"
                        style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);"
                        onchange="window.__swHandleServiceChange && window.__swHandleServiceChange(this)">
                    <option value="">Choisir un service</option>
                    @foreach($entreprise->typesServices as $service)
                        @php
                            $optionsData = $service->options ? $service->options->map(function($opt) {
                                return [
                                    'id' => $opt->id,
                                    'nom' => $opt->nom,
                                    'obligatoire' => $opt->obligatoire,
                                    'choices' => $opt->choices->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom, 'prix' => $c->prix_supplementaire, 'temps' => $c->temps_supplementaire])
                                ];
                            }) : collect();
                        @endphp
                        <option value="{{ $service->id }}"
                                data-duree="{{ $service->duree_minutes }}"
                                data-prix="{{ $service->prix }}"
                                data-type-structure="{{ $service->type_structure ?? 'ponctuel' }}"
                                data-capacite-max="{{ $service->capacite_max ?? '' }}"
                                data-options="{{ base64_encode(json_encode($optionsData)) }}"
                                {{ request('service') == $service->id ? 'selected' : '' }}>
                            {{ $service->nom }}
                            @if(($service->type_structure ?? '') === 'sur_devis')
                                &bull; Sur devis
                            @else
                                &bull; {{ number_format($service->prix, 0, ',', ' ') }}&euro;
                            @endif
                            @if(!in_array($service->type_structure ?? '', ['date_butoire', 'sur_devis'])) &bull; {{ $service->duree_minutes }}min @endif
                            @if(($service->type_structure ?? '') === 'evenement' && $service->capacite_max) &bull; {{ $service->capacite_max }} places @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Options du service (rempli par JS) --}}
            <div id="sw-service-options" class="space-y-4 hidden"></div>

            {{-- Date butoire --}}
            <div id="sw-date-butoire-wrapper" class="hidden">
                <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Date butoire</label>
                <input type="date" name="date_butoire" min="{{ date('Y-m-d') }}" disabled
                       class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                       style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
            </div>

            {{-- Champs RÉCURRENT --}}
            <div id="sw-recurrent-wrapper" class="hidden space-y-3">
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Fréquence</label>
                    <select name="frequence" disabled
                            class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                            style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);"
                            onchange="document.getElementById('sw-intervalle-jours')?.parentElement.classList.toggle('hidden', this.value !== 'personnalise')">
                        <option value="hebdomadaire">Chaque semaine</option>
                        <option value="bimensuel">Toutes les 2 semaines</option>
                        <option value="mensuel">Chaque mois</option>
                        <option value="personnalise">Personnalisé</option>
                    </select>
                </div>
                <div class="hidden">
                    <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Tous les X jours</label>
                    <input type="number" name="intervalle_jours" id="sw-intervalle-jours" min="1" value="7" disabled
                           class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                           style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Date de début</label>
                        <input type="date" name="date_debut" min="{{ date('Y-m-d') }}" disabled
                               class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                               style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Date de fin</label>
                        <input type="date" name="date_fin" min="{{ date('Y-m-d') }}" disabled
                               class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                               style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                    </div>
                </div>
            </div>

            {{-- Champs SUR DEVIS --}}
            <div id="sw-sur-devis-wrapper" class="hidden">
                <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Décrivez votre besoin *</label>
                <textarea name="description_besoin" rows="4" disabled
                          placeholder="Décrivez ce que vous souhaitez en détail..."
                          class="w-full px-4 py-3 text-sm border-2 rounded-xl resize-none"
                          style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);"></textarea>
            </div>

            {{-- Membre --}}
            @if($aGestionMultiPersonnes && $membres->count() > 0)
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Personne</label>
                    <select name="membre_id"
                            class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                            style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                        <option value="">Qu'importe (auto)</option>
                        @foreach($membres as $membre)
                            <option value="{{ $membre->id }}">{{ $membre->user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Date / Heure (masqué pour date_butoire) --}}
            <div id="sw-datetime-wrapper">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Date</label>
                        <input type="date" name="date_reservation" min="{{ date('Y-m-d') }}" required
                               class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                               style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Heure</label>
                        <input type="time" name="heure_reservation" required
                               class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                               style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                    </div>
                </div>
            </div>

            {{-- Champs guest (nom / email) --}}
            @if(!$user)
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Votre nom</label>
                    <input type="text" name="nom_client" required placeholder="Jean Dupont"
                           class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                           style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Email</label>
                    <input type="email" name="email_client" required placeholder="jean@example.com"
                           class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                           style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
                </div>
            @endif

            {{-- Téléphone --}}
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">
                    Téléphone
                    @if($user && !empty($userInfo['telephone']))
                        <span class="font-normal opacity-50 text-xs">(pré-rempli)</span>
                    @endif
                </label>
                <input type="tel" name="telephone_client" required placeholder="06 12 34 56 78"
                       value="{{ $userInfo['telephone'] ?? '' }}"
                       class="w-full px-4 py-3 text-sm border-2 rounded-xl"
                       style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);">
            </div>

            {{-- Masquer téléphone --}}
            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-colors"
                   style="background: color-mix(in srgb, var(--site-text) 4%, transparent);">
                <input type="checkbox" name="telephone_cache" value="1" class="w-4 h-4 rounded text-green-600 focus:ring-green-500 border-gray-300">
                <span class="text-sm opacity-70" style="color: var(--site-text);">Masquer mon numéro</span>
            </label>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color: var(--site-text);">Notes <span class="font-normal opacity-40">(optionnel)</span></label>
                <textarea name="notes" rows="2" placeholder="Informations complémentaires..."
                          class="w-full px-4 py-3 text-sm border-2 rounded-xl resize-none"
                          style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); background: var(--site-background); color: var(--site-text);"></textarea>
            </div>

            {{-- Bouton --}}
            <button type="submit"
                    class="w-full px-6 py-4 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    style="background: var(--site-primary); border-radius: var(--site-button-radius);">
                Réserver
            </button>
        </div>
    </form>
</div>

<script>
// Popup auth
window.__openAuthPopup = function() {
    const w = 500, h = 650;
    const left = (screen.width - w) / 2;
    const top = (screen.height - h) / 2;
    window.open('{{ $popupUrl }}', 'allotata_auth', `width=${w},height=${h},left=${left},top=${top},toolbar=no,menubar=no,scrollbars=yes`);
};

// Gestion changement de service
window.__swHandleServiceChange = function(select) {
    const opt = select.options[select.selectedIndex];
    const typeStructure = opt ? (opt.dataset.typeStructure || 'ponctuel') : 'ponctuel';
    const isDateButoire = typeStructure === 'date_butoire';
    const isRecurrent = typeStructure === 'recurrent';
    const isSurDevis = typeStructure === 'sur_devis';
    const isEvenement = typeStructure === 'evenement';
    
    const dbWrapper = document.getElementById('sw-date-butoire-wrapper');
    const dtWrapper = document.getElementById('sw-datetime-wrapper');
    const recWrapper = document.getElementById('sw-recurrent-wrapper');
    const devisWrapper = document.getElementById('sw-sur-devis-wrapper');
    const dbInput = dbWrapper?.querySelector('input[name="date_butoire"]');
    const dateInput = dtWrapper?.querySelector('input[name="date_reservation"]');
    const heureInput = dtWrapper?.querySelector('input[name="heure_reservation"]');

    // Masquer tout d'abord
    dbWrapper?.classList.add('hidden');
    dtWrapper?.classList.add('hidden');
    recWrapper?.classList.add('hidden');
    devisWrapper?.classList.add('hidden');

    // Désactiver tous les inputs conditionnels
    if (dbInput) { dbInput.required = false; dbInput.disabled = true; dbInput.value = ''; }
    if (dateInput) { dateInput.required = false; dateInput.disabled = true; }
    if (heureInput) { heureInput.required = false; heureInput.disabled = true; }
    recWrapper?.querySelectorAll('input, select').forEach(el => { el.disabled = true; el.required = false; });
    devisWrapper?.querySelectorAll('textarea').forEach(el => { el.disabled = true; el.required = false; });

    if (isDateButoire) {
        dbWrapper?.classList.remove('hidden');
        if (dbInput) { dbInput.required = true; dbInput.disabled = false; }
    } else if (isRecurrent) {
        // Récurrent : montrer heure + champs récurrence
        dtWrapper?.classList.remove('hidden');
        recWrapper?.classList.remove('hidden');
        if (heureInput) { heureInput.required = true; heureInput.disabled = false; }
        // Pas besoin de date_reservation, mais on laisse le wrapper visible pour l'heure
        if (dateInput) { dateInput.required = false; dateInput.disabled = true; dateInput.parentElement.classList.add('hidden'); }
        recWrapper?.querySelectorAll('input, select').forEach(el => { el.disabled = false; });
        recWrapper?.querySelector('input[name="date_debut"]').required = true;
        recWrapper?.querySelector('input[name="date_fin"]').required = true;
        recWrapper?.querySelector('select[name="frequence"]').required = true;
    } else if (isSurDevis) {
        devisWrapper?.classList.remove('hidden');
        devisWrapper?.querySelector('textarea[name="description_besoin"]').disabled = false;
        devisWrapper?.querySelector('textarea[name="description_besoin"]').required = true;
    } else {
        // Ponctuel, multi_jours, multi_rendez_vous, evenement
        dtWrapper?.classList.remove('hidden');
        if (dateInput) { dateInput.required = true; dateInput.disabled = false; dateInput.parentElement.classList.remove('hidden'); }
        if (heureInput) { heureInput.required = true; heureInput.disabled = false; }
    }

    // Options du service
    const container = document.getElementById('sw-service-options');
    if (!container) return;
    container.innerHTML = '';
    container.classList.add('hidden');
    if (!opt || !opt.value || !opt.dataset.options) return;
    try {
        const options = JSON.parse(atob(opt.dataset.options));
        if (!options.length) return;
        container.classList.remove('hidden');
        options.forEach(option => {
            const group = document.createElement('div');
            group.className = 'p-4 rounded-xl border';
            group.style.cssText = 'border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background: color-mix(in srgb, var(--site-text) 3%, transparent);';
            group.innerHTML = `<h4 class="font-medium text-sm mb-3" style="color: var(--site-text);">${option.nom} ${option.obligatoire ? '<span class="text-xs text-red-500">(obligatoire)</span>' : ''}</h4>`;
            const choices = document.createElement('div');
            choices.className = 'space-y-2';
            option.choices.forEach(c => {
                const label = document.createElement('label');
                label.className = 'flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-colors';
                label.style.cssText = 'border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background: var(--site-background);';
                label.innerHTML = `
                    <div class="flex items-center gap-2">
                        <input type="radio" name="service_options[${option.id}]" value="${c.id}" class="w-4 h-4 text-green-600" ${option.obligatoire ? 'required' : ''}>
                        <span class="text-sm" style="color: var(--site-text);">${c.nom}</span>
                    </div>
                    <div class="text-xs opacity-50">
                        ${c.prix > 0 ? `<span style="color: var(--site-primary);">+${parseFloat(c.prix).toLocaleString('fr-FR')}&euro;</span>` : ''}
                        ${c.temps > 0 ? `<span>+${c.temps} min</span>` : ''}
                    </div>`;
                choices.appendChild(label);
            });
            group.appendChild(choices);
            container.appendChild(group);
        });
    } catch(e) { console.error(e); }
};

// Auto-trigger si service pré-sélectionné
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.querySelector('#sw-reservation-form select[name="type_service_id"]');
    if (sel && sel.value) window.__swHandleServiceChange(sel);
});
</script>
