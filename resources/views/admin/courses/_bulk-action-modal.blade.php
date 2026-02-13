{{-- 
    Modale Commandes IA Bulk (Modifier / Supprimer / Basculer / Réordonner)
    Variables attendues :
    - $bulkActionContext : tableau contextuel (modules, leçons, etc.)
--}}

@php
    $ctx = $bulkActionContext ?? [];
@endphp

<!-- Modal -->
<div id="bulk-action-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeBulkActionModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-5xl my-8 overflow-hidden" onclick="event.stopPropagation()">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Commandes IA Bulk</h3>
                    <p class="text-sm text-white/70">Modifier, supprimer, basculer ou réordonner en masse</p>
                </div>
            </div>
            <button onclick="closeBulkActionModal()" class="text-white/70 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

            {{-- Étape 0 : Choisir l'opération --}}
            <div>
                <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 mb-3">
                    <span class="w-6 h-6 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center text-xs font-bold">0</span>
                    Choisir l'opération
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button onclick="selectBulkAction('update')" id="ba-btn-update" class="ba-action-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-blue-500 dark:hover:border-blue-400 transition-all">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Modifier</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 text-center">Titres, descriptions, champs...</span>
                    </button>

                    <button onclick="selectBulkAction('delete')" id="ba-btn-delete" class="ba-action-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-red-500 dark:hover:border-red-400 transition-all">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Supprimer</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 text-center">Modules, lecons, questions</span>
                    </button>

                    <button onclick="selectBulkAction('toggle')" id="ba-btn-toggle" class="ba-action-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-green-500 dark:hover:border-green-400 transition-all">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Basculer</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 text-center">Actif/inactif, publier/depublier</span>
                    </button>

                    <button onclick="selectBulkAction('reorder')" id="ba-btn-reorder" class="ba-action-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-purple-500 dark:hover:border-purple-400 transition-all">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Reordonner</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 text-center">Ordre des modules/lecons</span>
                    </button>
                </div>
            </div>

            {{-- Étape 1 : Pré-prompt (affiché après sélection) --}}
            <div id="ba-step-prompt" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                        Copier le pré-prompt pour l'IA
                    </h4>
                    <button 
                        onclick="copyBulkActionPreprompt()" 
                        id="ba-copy-btn"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span id="ba-copy-text">Copier le prompt</span>
                    </button>
                </div>
                
                <div class="relative">
                    <div 
                        id="ba-preprompt-content" 
                        class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-sm text-slate-700 dark:text-slate-300 font-mono leading-relaxed max-h-60 overflow-y-auto whitespace-pre-wrap cursor-pointer"
                        onclick="copyBulkActionPreprompt()"
                        title="Cliquer pour copier"
                    ></div>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Copiez ce prompt, collez-le dans Gemini, ChatGPT ou Claude, puis collez le JSON obtenu ci-dessous.
                </p>
            </div>

            {{-- Étape 2 : Coller le JSON --}}
            <div id="ba-step-json" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                        Coller le JSON generé par l'IA
                    </h4>
                    <button 
                        onclick="formatBulkActionJson()" 
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-sm rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                    >
                        Formater
                    </button>
                </div>
                <textarea 
                    id="ba-json-textarea" 
                    rows="12"
                    placeholder='Collez ici le JSON généré par l&apos;IA...'
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono text-sm leading-relaxed resize-y"
                    spellcheck="false"
                ></textarea>
            </div>

            {{-- Étape 3 : Vérifier et exécuter --}}
            <div id="ba-step-execute" class="hidden">
                <div class="flex items-center gap-3 mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                        Vérifier et exécuter
                    </h4>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button 
                        onclick="validateBulkAction()" 
                        id="ba-btn-validate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition shadow-sm"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Vérifier l'intégrité</span>
                    </button>

                    <button 
                        onclick="executeBulkAction()" 
                        id="ba-btn-execute"
                        disabled
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition shadow-sm"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Exécuter</span>
                    </button>
                </div>

                {{-- Zone de résultats --}}
                <div id="ba-results" class="hidden mt-4"></div>
            </div>
        </div>
    </div>
</div>

{{-- Données contextuelles injectées en JSON pour les preprompts dynamiques --}}
<script>
    window.__bulkActionContext = @json($ctx);
</script>

{{-- Preprompts templates --}}
@include('admin.courses._bulk-action-preprompt', ['context' => $ctx])

<script>
(function() {
    const VALIDATE_URL = '{{ route("admin.courses.bulk-action.validate") }}';
    const EXECUTE_URL = '{{ route("admin.courses.bulk-action") }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

    let selectedAction = null;
    let isValidated = false;

    const actionColors = {
        update: 'blue',
        delete: 'red',
        toggle: 'green',
        reorder: 'purple',
    };

    const actionLabels = {
        update: 'Modifier en masse',
        delete: 'Supprimer en masse',
        toggle: 'Basculer les états',
        reorder: 'Réordonner',
    };

    const actionConfirmMessages = {
        update: 'Lancer la modification en masse ? Les éléments listés seront mis à jour.',
        delete: 'ATTENTION : Lancer la suppression en masse ? Cette action est IRRÉVERSIBLE.',
        toggle: 'Lancer le basculement des états ?',
        reorder: 'Lancer le réordonnancement ?',
    };

    window.openBulkActionModal = function() {
        document.getElementById('bulk-action-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeBulkActionModal = function() {
        document.getElementById('bulk-action-modal').classList.add('hidden');
        document.body.style.overflow = '';
        // Reset state
        selectedAction = null;
        isValidated = false;
        document.querySelectorAll('.ba-action-btn').forEach(btn => {
            btn.classList.remove('!border-blue-500', '!border-red-500', '!border-green-500', '!border-purple-500', 'ring-2', 'ring-offset-2');
        });
        document.getElementById('ba-step-prompt').classList.add('hidden');
        document.getElementById('ba-step-json').classList.add('hidden');
        document.getElementById('ba-step-execute').classList.add('hidden');
        document.getElementById('ba-results').classList.add('hidden');
        document.getElementById('ba-json-textarea').value = '';
        document.getElementById('ba-btn-execute').disabled = true;
    };

    window.selectBulkAction = function(action) {
        selectedAction = action;
        isValidated = false;

        // Reset button states
        document.querySelectorAll('.ba-action-btn').forEach(btn => {
            btn.classList.remove('!border-blue-500', '!border-red-500', '!border-green-500', '!border-purple-500', 'ring-2', 'ring-offset-2', 'ring-blue-300', 'ring-red-300', 'ring-green-300', 'ring-purple-300');
        });

        // Highlight selected
        const color = actionColors[action];
        const btn = document.getElementById('ba-btn-' + action);
        btn.classList.add('!border-' + color + '-500', 'ring-2', 'ring-offset-2', 'ring-' + color + '-300');

        // Show preprompt
        const prepromptContent = document.getElementById('ba-preprompt-template-' + action);
        if (prepromptContent) {
            document.getElementById('ba-preprompt-content').textContent = prepromptContent.textContent.trim();
        }

        // Show steps
        document.getElementById('ba-step-prompt').classList.remove('hidden');
        document.getElementById('ba-step-json').classList.remove('hidden');
        document.getElementById('ba-step-execute').classList.remove('hidden');
        document.getElementById('ba-results').classList.add('hidden');
        document.getElementById('ba-btn-execute').disabled = true;
        document.getElementById('ba-json-textarea').value = '';
    };

    window.copyBulkActionPreprompt = function() {
        const text = document.getElementById('ba-preprompt-content').textContent;
        navigator.clipboard.writeText(text.trim()).then(() => {
            const btn = document.getElementById('ba-copy-text');
            btn.textContent = 'Copié !';
            document.getElementById('ba-copy-btn').classList.add('!bg-green-100', 'dark:!bg-green-900/30', '!text-green-700', 'dark:!text-green-300');
            setTimeout(() => {
                btn.textContent = 'Copier le prompt';
                document.getElementById('ba-copy-btn').classList.remove('!bg-green-100', 'dark:!bg-green-900/30', '!text-green-700', 'dark:!text-green-300');
            }, 2000);
        });
    };

    window.formatBulkActionJson = function() {
        const textarea = document.getElementById('ba-json-textarea');
        try {
            const parsed = JSON.parse(textarea.value);
            textarea.value = JSON.stringify(parsed, null, 2);
            textarea.style.borderColor = '';
        } catch (e) {
            textarea.style.borderColor = '#ef4444';
        }
    };

    // Re-désactiver le bouton si le JSON change
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('ba-json-textarea');
        if (textarea) {
            textarea.addEventListener('input', function() {
                isValidated = false;
                document.getElementById('ba-btn-execute').disabled = true;
                document.getElementById('ba-results').classList.add('hidden');
            });
        }
    });

    window.validateBulkAction = async function() {
        if (!selectedAction) return;

        const jsonData = document.getElementById('ba-json-textarea').value.trim();
        if (!jsonData) {
            showBaResults(false, 'Veuillez coller un JSON avant de vérifier.');
            return;
        }

        try {
            JSON.parse(jsonData);
        } catch (e) {
            showBaResults(false, 'JSON invalide : ' + e.message);
            return;
        }

        const btn = document.getElementById('ba-btn-validate');
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Vérification...';

        try {
            const response = await fetch(VALIDATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    json_data: jsonData,
                    action: selectedAction,
                }),
            });

            const data = await response.json();

            if (data.success) {
                isValidated = true;
                document.getElementById('ba-btn-execute').disabled = false;
                showBaResults(true, null, data.summary, data.errors);
            } else {
                isValidated = false;
                document.getElementById('ba-btn-execute').disabled = true;
                showBaResults(false, data.error || 'Erreurs détectées', data.summary, data.errors);
            }
        } catch (e) {
            showBaResults(false, 'Erreur réseau : ' + e.message);
        } finally {
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Vérifier l\'intégrité';
        }
    };

    window.executeBulkAction = async function() {
        if (!isValidated || !selectedAction) return;
        if (!confirm(actionConfirmMessages[selectedAction])) return;

        const jsonData = document.getElementById('ba-json-textarea').value.trim();
        const btn = document.getElementById('ba-btn-execute');
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Exécution...';

        try {
            const response = await fetch(EXECUTE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    json_data: jsonData,
                    action: selectedAction,
                }),
            });

            const data = await response.json();

            if (data.success) {
                showBaExecutionResult(data.affected);
                setTimeout(() => location.reload(), 2500);
            } else {
                showBaResults(false, data.error || 'Erreur lors de l\'exécution', null, data.errors);
                btn.disabled = false;
                btn.querySelector('span').textContent = 'Exécuter';
            }
        } catch (e) {
            showBaResults(false, 'Erreur réseau : ' + e.message);
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Exécuter';
        }
    };

    function showBaResults(success, message, summary, errors) {
        const el = document.getElementById('ba-results');
        el.classList.remove('hidden');

        let html = '';

        if (success && summary) {
            html += '<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">';
            html += '<div class="flex items-center gap-2 mb-3"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="font-semibold text-green-800 dark:text-green-300">Validation OK — Prêt à exécuter</span></div>';
            html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">';
            for (const [key, val] of Object.entries(summary)) {
                if (val > 0) html += baCard(key, val, 'green');
            }
            html += '</div></div>';
        } else if (!success) {
            html += '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">';
            html += '<div class="flex items-center gap-2 mb-2"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="font-semibold text-red-800 dark:text-red-300">Erreurs détectées</span></div>';
            if (message) html += '<p class="text-sm text-red-700 dark:text-red-400 mb-2">' + escHtml(message) + '</p>';
            if (errors && errors.length > 0) {
                html += '<ul class="text-sm text-red-700 dark:text-red-400 space-y-1 list-disc list-inside max-h-40 overflow-y-auto">';
                errors.forEach(err => { html += '<li>' + escHtml(err) + '</li>'; });
                html += '</ul>';
            }
            html += '</div>';
        }

        el.innerHTML = html;
    }

    function showBaExecutionResult(affected) {
        const el = document.getElementById('ba-results');
        el.classList.remove('hidden');

        const colorMap = {
            modules: 'blue', lessons: 'green', questions: 'orange',
            activations: 'green', desactivations: 'slate', publications: 'indigo', depublications: 'amber',
        };

        const labelMap = {
            modules: 'Modules', lessons: 'Lecons', questions: 'Questions',
            activations: 'Activés', desactivations: 'Désactivés', publications: 'Publiés', depublications: 'Dépubliés',
        };

        let html = '<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">';
        html += '<div class="flex items-center gap-2 mb-3"><svg class="w-6 h-6 text-green-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="font-bold text-green-800 dark:text-green-300 text-lg">Commande exécutée !</span></div>';
        html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">';
        for (const [key, val] of Object.entries(affected)) {
            if (val > 0) {
                const color = colorMap[key] || 'slate';
                const label = labelMap[key] || key;
                html += baCard(label, val, color);
            }
        }
        html += '</div>';
        html += '<p class="text-sm text-green-700 dark:text-green-400 mt-3">Rechargement de la page...</p>';
        html += '</div>';

        el.innerHTML = html;
    }

    function baCard(label, count, color) {
        return '<div class="bg-' + color + '-100 dark:bg-' + color + '-900/30 rounded-lg p-3 text-center">' +
            '<p class="text-2xl font-bold text-' + color + '-700 dark:text-' + color + '-300">' + count + '</p>' +
            '<p class="text-xs text-' + color + '-600 dark:text-' + color + '-400">' + label + '</p></div>';
    }

    function escHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>
