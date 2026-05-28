@props([
    'entreprise' => null,
    'oldPrefix' => '',
])

@php
    $typeLocalisation = old('type_localisation', $entreprise?->type_localisation ?? \App\Models\Entreprise::LOCALISATION_PHYSIQUE);
    $isVirtuel = $typeLocalisation === \App\Models\Entreprise::LOCALISATION_VIRTUEL;
@endphp

<div class="space-y-4" id="localisation-form-root" data-initial-type="{{ $typeLocalisation }}">
    <div>
        <p class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
            Mode d'activité <span class="text-red-500">*</span>
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all localisation-type-card {{ !$isVirtuel ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300' }}">
                <input type="radio" name="type_localisation" value="physique" class="sr-only localisation-type-radio" {{ !$isVirtuel ? 'checked' : '' }}>
                <div>
                    <span class="font-semibold text-slate-900 dark:text-white">Physique</span>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Accueil sur place ou déplacement chez le client</p>
                </div>
            </label>
            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all localisation-type-card {{ $isVirtuel ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-slate-200 dark:border-slate-600 hover:border-slate-300' }}">
                <input type="radio" name="type_localisation" value="virtuel" class="sr-only localisation-type-radio" {{ $isVirtuel ? 'checked' : '' }}>
                <div>
                    <span class="font-semibold text-slate-900 dark:text-white">Virtuel</span>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">100 % en ligne — pas d'adresse physique à afficher</p>
                </div>
            </label>
        </div>
        @error('type_localisation')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div id="localisation-virtuelle-panel" class="{{ $isVirtuel ? '' : 'hidden' }} p-4 rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20">
        <p class="text-sm text-violet-900 dark:text-violet-200">
            <strong>Activité 100 % en ligne.</strong> Aucune adresse ni carte ne sera affichée sur votre page publique (<code class="text-xs">/p/</code>, <code class="text-xs">/w/</code>).
            Vous restez visible dans la recherche par mots-clés et apparaissez aussi lors des recherches par ville (prestataires en ligne).
        </p>
    </div>

    <div id="localisation-physique-panel" class="{{ $isVirtuel ? 'hidden' : '' }} space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Rechercher une adresse
            </label>
            <div class="relative">
                <input
                    type="text"
                    id="address-search"
                    placeholder="Commencez à taper votre adresse..."
                    autocomplete="off"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                >
                <div id="address-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto"></div>
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Recherchez votre adresse pour remplir automatiquement les champs ci-dessous
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Adresse (rue et numéro)</label>
                <input type="text" name="adresse_rue" id="adresse_rue" value="{{ old('adresse_rue', $entreprise?->adresse_rue) }}"
                    placeholder="123 rue de la Paix"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Code postal</label>
                <input type="text" name="code_postal" id="code_postal" value="{{ old('code_postal', $entreprise?->code_postal) }}"
                    placeholder="75001" maxlength="5"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Ville <span class="text-red-500 localisation-ville-required">*</span>
                </label>
                <input type="text" name="ville" id="ville" value="{{ old('ville', $entreprise?->ville) }}"
                    placeholder="Paris"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white localisation-ville-input"
                    @if(!$isVirtuel) required @endif
                >
                @error('ville')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Rayon de déplacement (km)</label>
                <input type="number" name="rayon_deplacement" id="rayon_deplacement" value="{{ old('rayon_deplacement', $entreprise?->rayon_deplacement ?? 0) }}"
                    min="0" placeholder="0 = fixe, &gt;0 = mobile"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">0 si vous êtes sur un lieu fixe, ou le nombre de km si vous vous déplacez</p>
                @error('rayon_deplacement')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-lg cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition">
            <input type="checkbox" name="afficher_adresse_complete" value="1"
                {{ old('afficher_adresse_complete', $entreprise?->afficher_adresse_complete) ? 'checked' : '' }}
                class="w-5 h-5 text-green-600 border-slate-300 rounded focus:ring-green-500">
            <div>
                <span class="text-sm font-medium text-slate-900 dark:text-white">Afficher l'adresse complète publiquement</span>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sinon, seule la ville sera visible sur votre page publique.</p>
            </div>
        </label>

        @if($entreprise?->latitude && $entreprise?->longitude && !$isVirtuel)
            <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-400">Coordonnées GPS enregistrées — visible dans les recherches par proximité.</p>
            </div>
        @elseif($entreprise && !$isVirtuel)
            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-400">Recherchez votre adresse pour activer les recherches par proximité.</p>
            </div>
        @endif

        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $entreprise?->latitude) }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $entreprise?->longitude) }}">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
            const root = document.getElementById('localisation-form-root');
            if (!root) return;

            const physiquePanel = document.getElementById('localisation-physique-panel');
            const virtuellePanel = document.getElementById('localisation-virtuelle-panel');
            const villeInput = document.getElementById('ville');
            const cards = root.querySelectorAll('.localisation-type-card');
            const radios = root.querySelectorAll('.localisation-type-radio');

            function setMode(type) {
                const virtuel = type === 'virtuel';
                physiquePanel.classList.toggle('hidden', virtuel);
                virtuellePanel.classList.toggle('hidden', !virtuel);
                if (villeInput) {
                    villeInput.required = !virtuel;
                    villeInput.toggleAttribute('disabled', virtuel);
                }
                cards.forEach(card => {
                    const radio = card.querySelector('input[type=radio]');
                    const active = radio.value === type;
                    card.classList.toggle('border-green-500', active);
                    card.classList.toggle('bg-green-50', active);
                    card.classList.toggle('dark:bg-green-900/20', active);
                    card.classList.toggle('border-slate-200', !active);
                    card.classList.toggle('dark:border-slate-600', !active);
                });
            }

            radios.forEach(radio => {
                radio.addEventListener('change', () => setMode(radio.value));
            });

            if (typeof AddressAutocomplete !== 'undefined') {
                const addressAutocomplete = new AddressAutocomplete({
                    onSelect: function(data) {
                        document.getElementById('adresse_rue').value = ((data.housenumber || '') + ' ' + (data.street || data.name || '')).trim();
                        document.getElementById('code_postal').value = data.postcode || '';
                        document.getElementById('ville').value = data.city || '';
                        document.getElementById('latitude').value = data.latitude || '';
                        document.getElementById('longitude').value = data.longitude || '';
                        document.getElementById('address-search').value = data.label || '';
                    }
                });
                addressAutocomplete.init('address-search', 'address-results', 'address');
            }

            setMode(root.dataset.initialType || 'physique');
    });
</script>
