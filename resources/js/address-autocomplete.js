/**
 * Address Autocomplete - Utilise l'API Adresse du gouvernement français
 * https://adresse.data.gouv.fr/api-doc/adresse
 */

class AddressAutocomplete {
    constructor(options = {}) {
        this.minLength = options.minLength || 3;
        this.debounceMs = options.debounceMs || 300;
        this.onSelect = options.onSelect || (() => { });
        this.apiUrl = '/api/address/search';
        this.citiesApiUrl = '/api/address/cities';
        this.debounceTimer = null;
    }

    /**
     * Initialise l'autocomplete sur un champ de texte
     * @param {string} inputId - ID du champ input
     * @param {string} resultsId - ID du conteneur de résultats
     * @param {string} type - Type de recherche: 'address' ou 'city'
     */
    init(inputId, resultsId, type = 'address') {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);

        if (!input || !results) {
            console.error('AddressAutocomplete: Input or results container not found');
            return;
        }

        input.addEventListener('input', (e) => {
            this.handleInput(e.target.value, results, type);
        });

        input.addEventListener('focus', () => {
            if (input.value.length >= this.minLength) {
                results.classList.remove('hidden');
            }
        });

        // Fermer quand on clique ailleurs
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.classList.add('hidden');
            }
        });

        // Navigation clavier
        input.addEventListener('keydown', (e) => {
            this.handleKeydown(e, results, input);
        });
    }

    handleInput(value, resultsContainer, type) {
        clearTimeout(this.debounceTimer);

        if (value.length < this.minLength) {
            resultsContainer.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }

        this.debounceTimer = setTimeout(async () => {
            const url = type === 'city' ? this.citiesApiUrl : this.apiUrl;
            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(value)}&limit=5`);
                const data = await response.json();

                if (data.success && data.results.length > 0) {
                    this.renderResults(data.results, resultsContainer);
                    resultsContainer.classList.remove('hidden');
                } else {
                    resultsContainer.innerHTML = '<div class="p-3 text-slate-500 text-sm">Aucun résultat trouvé</div>';
                    resultsContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Address autocomplete error:', error);
                resultsContainer.classList.add('hidden');
            }
        }, this.debounceMs);
    }

    renderResults(results, container) {
        container.innerHTML = results.map((result, index) => `
            <div class="address-result p-3 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition-colors ${index === 0 ? 'bg-slate-50 dark:bg-slate-700/50' : ''}"
                 data-address='${JSON.stringify(result)}'
                 tabindex="0">
                <div class="font-medium text-slate-900 dark:text-white">${this.escapeHtml(result.label)}</div>
                ${result.context ? `<div class="text-sm text-slate-500 dark:text-slate-400">${this.escapeHtml(result.context)}</div>` : ''}
            </div>
        `).join('');

        // Event listeners pour les résultats
        container.querySelectorAll('.address-result').forEach(el => {
            el.addEventListener('click', () => {
                const addressData = JSON.parse(el.dataset.address);
                this.selectAddress(addressData, container);
            });

            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const addressData = JSON.parse(el.dataset.address);
                    this.selectAddress(addressData, container);
                }
            });
        });
    }

    selectAddress(addressData, container) {
        container.classList.add('hidden');
        this.onSelect(addressData);
    }

    handleKeydown(e, resultsContainer, input) {
        const items = resultsContainer.querySelectorAll('.address-result');
        const activeIndex = Array.from(items).findIndex(item => item.classList.contains('bg-slate-50', 'dark:bg-slate-700/50'));

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (items.length > 0) {
                    const nextIndex = Math.min(activeIndex + 1, items.length - 1);
                    this.setActiveItem(items, nextIndex);
                }
                break;

            case 'ArrowUp':
                e.preventDefault();
                if (items.length > 0) {
                    const prevIndex = Math.max(activeIndex - 1, 0);
                    this.setActiveItem(items, prevIndex);
                }
                break;

            case 'Enter':
                e.preventDefault();
                if (items.length > 0 && activeIndex >= 0) {
                    items[activeIndex].click();
                }
                break;

            case 'Escape':
                resultsContainer.classList.add('hidden');
                break;
        }
    }

    setActiveItem(items, index) {
        items.forEach((item, i) => {
            if (i === index) {
                item.classList.add('bg-slate-50', 'dark:bg-slate-700/50');
            } else {
                item.classList.remove('bg-slate-50', 'dark:bg-slate-700/50');
            }
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Exporter globalement
window.AddressAutocomplete = AddressAutocomplete;
