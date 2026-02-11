{{-- 
    Modale Remplissage IA
    Variables attendues :
    - $bulkFillMode : 'global', 'module', ou 'lesson'
    - $bulkFillTargetId : ID du module ou de la leçon cible (null en mode global)
    - $bulkFillContext : tableau contextuel (modules existants, titre module/leçon, etc.)
--}}

@php
    $mode = $bulkFillMode ?? 'global';
    $targetId = $bulkFillTargetId ?? null;
    $context = $bulkFillContext ?? [];
@endphp

<!-- Bouton d'ouverture -->
{{-- Le bouton est géré dans chaque vue parente --}}

<!-- Modal -->
<div id="bulk-fill-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeBulkFillModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-5xl my-8 overflow-hidden" onclick="event.stopPropagation()">
        
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Remplissage IA</h3>
                    <p class="text-sm text-white/70">
                        @if($mode === 'global')
                            Mode : Modules complets
                        @elseif($mode === 'module')
                            Mode : Leçons pour "{{ $context['module_titre'] ?? 'Module' }}"
                        @else
                            Mode : Blocs pour "{{ $context['lesson_titre'] ?? 'Leçon' }}"
                        @endif
                    </p>
                </div>
            </div>
            <button onclick="closeBulkFillModal()" class="text-white/70 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

            {{-- Étape 1 : Pré-prompt --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                        Copier le pré-prompt pour l'IA
                    </h4>
                    <button 
                        onclick="copyPreprompt()" 
                        id="copy-preprompt-btn"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span id="copy-preprompt-text">Copier le prompt</span>
                    </button>
                </div>
                
                <div class="relative">
                    <div 
                        id="preprompt-content" 
                        class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-sm text-slate-700 dark:text-slate-300 font-mono leading-relaxed max-h-60 overflow-y-auto whitespace-pre-wrap cursor-pointer"
                        onclick="copyPreprompt()"
                        title="Cliquer pour copier"
                    >@include('admin.courses._bulk-fill-preprompt', ['mode' => $mode, 'context' => $context])</div>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Copiez ce prompt, collez-le dans Gemini, ChatGPT ou Claude, puis collez le JSON obtenu ci-dessous.
                </p>
            </div>

            {{-- Étape 2 : Coller le JSON --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                        Coller le JSON généré par l'IA
                    </h4>
                    <button 
                        onclick="formatBulkJson()" 
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-sm rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                    >
                        Formater
                    </button>
                </div>
                <textarea 
                    id="bulk-fill-json" 
                    rows="12"
                    placeholder='Collez ici le JSON généré par l&apos;IA...'
                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono text-sm leading-relaxed resize-y"
                    spellcheck="false"
                ></textarea>
            </div>

            {{-- Étape 3 : Vérifier --}}
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                        Vérifier et importer
                    </h4>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button 
                        onclick="validateBulkFill()" 
                        id="btn-validate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition shadow-sm"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Vérifier l'intégrité</span>
                    </button>

                    <button 
                        onclick="executeBulkFill()" 
                        id="btn-execute"
                        disabled
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition shadow-sm"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Remplir maintenant</span>
                    </button>
                </div>

                {{-- Zone de résultats --}}
                <div id="bulk-fill-results" class="hidden mt-4">
                    {{-- Rempli dynamiquement par JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const BULK_MODE = '{{ $mode }}';
    const BULK_TARGET_ID = {{ $targetId ?? 'null' }};
    const VALIDATE_URL = '{{ route("admin.courses.bulk-fill.validate") }}';
    const EXECUTE_URL = '{{ route("admin.courses.bulk-fill") }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

    let isValidated = false;

    window.openBulkFillModal = function() {
        document.getElementById('bulk-fill-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeBulkFillModal = function() {
        document.getElementById('bulk-fill-modal').classList.add('hidden');
        document.body.style.overflow = '';
    };

    window.copyPreprompt = function() {
        const text = document.getElementById('preprompt-content').textContent;
        navigator.clipboard.writeText(text.trim()).then(() => {
            const btn = document.getElementById('copy-preprompt-text');
            btn.textContent = 'Copié !';
            document.getElementById('copy-preprompt-btn').classList.add('!bg-green-100', 'dark:!bg-green-900/30', '!text-green-700', 'dark:!text-green-300');
            setTimeout(() => {
                btn.textContent = 'Copier le prompt';
                document.getElementById('copy-preprompt-btn').classList.remove('!bg-green-100', 'dark:!bg-green-900/30', '!text-green-700', 'dark:!text-green-300');
            }, 2000);
        });
    };

    window.formatBulkJson = function() {
        const textarea = document.getElementById('bulk-fill-json');
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
        const textarea = document.getElementById('bulk-fill-json');
        if (textarea) {
            textarea.addEventListener('input', function() {
                isValidated = false;
                document.getElementById('btn-execute').disabled = true;
                document.getElementById('bulk-fill-results').classList.add('hidden');
            });
        }
    });

    window.validateBulkFill = async function() {
        const jsonData = document.getElementById('bulk-fill-json').value.trim();
        if (!jsonData) {
            showResults(false, 'Veuillez coller un JSON avant de vérifier.');
            return;
        }

        // Vérifier le JSON localement
        try {
            JSON.parse(jsonData);
        } catch (e) {
            showResults(false, 'JSON invalide : ' + e.message);
            return;
        }

        const btn = document.getElementById('btn-validate');
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
                    mode: BULK_MODE,
                    target_id: BULK_TARGET_ID,
                }),
            });

            const data = await response.json();

            if (data.success) {
                isValidated = true;
                document.getElementById('btn-execute').disabled = false;
                showResults(true, null, data.summary, data.errors);
            } else {
                isValidated = false;
                document.getElementById('btn-execute').disabled = true;
                showResults(false, data.error || 'Erreurs détectées', data.summary, data.errors);
            }
        } catch (e) {
            showResults(false, 'Erreur réseau : ' + e.message);
        } finally {
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Vérifier l\'intégrité';
        }
    };

    window.executeBulkFill = async function() {
        if (!isValidated) return;
        if (!confirm('Lancer le remplissage ? Cette action va créer tous les éléments listés.')) return;

        const jsonData = document.getElementById('bulk-fill-json').value.trim();
        const btn = document.getElementById('btn-execute');
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Insertion en cours...';

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
                    mode: BULK_MODE,
                    target_id: BULK_TARGET_ID,
                }),
            });

            const data = await response.json();

            if (data.success) {
                showExecutionResult(data.created);
                // Recharger la page après 2 secondes
                setTimeout(() => location.reload(), 2500);
            } else {
                showResults(false, data.error || 'Erreur lors de l\'insertion', null, data.errors);
                btn.disabled = false;
                btn.querySelector('span').textContent = 'Remplir maintenant';
            }
        } catch (e) {
            showResults(false, 'Erreur réseau : ' + e.message);
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Remplir maintenant';
        }
    };

    function showResults(success, message, summary, errors) {
        const el = document.getElementById('bulk-fill-results');
        el.classList.remove('hidden');

        let html = '';

        if (success && summary) {
            html += '<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">';
            html += '<div class="flex items-center gap-2 mb-3"><svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="font-semibold text-green-800 dark:text-green-300">Prêt à importer</span></div>';
            html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">';
            if (summary.modules > 0) html += summaryCard('Modules', summary.modules, 'blue');
            if (summary.lessons > 0) html += summaryCard('Leçons', summary.lessons, 'green');
            if (summary.blocks > 0) html += summaryCard('Blocs', summary.blocks, 'purple');
            if (summary.questions > 0) html += summaryCard('Questions', summary.questions, 'orange');
            html += '</div></div>';
        } else if (!success) {
            html += '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">';
            html += '<div class="flex items-center gap-2 mb-2"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="font-semibold text-red-800 dark:text-red-300">Erreurs détectées</span></div>';
            if (message) html += '<p class="text-sm text-red-700 dark:text-red-400 mb-2">' + escapeHtml(message) + '</p>';
            if (errors && errors.length > 0) {
                html += '<ul class="text-sm text-red-700 dark:text-red-400 space-y-1 list-disc list-inside max-h-40 overflow-y-auto">';
                errors.forEach(err => {
                    html += '<li>' + escapeHtml(err) + '</li>';
                });
                html += '</ul>';
            }
            if (summary) {
                html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3 opacity-60">';
                if (summary.modules > 0) html += summaryCard('Modules', summary.modules, 'slate');
                if (summary.lessons > 0) html += summaryCard('Leçons', summary.lessons, 'slate');
                if (summary.blocks > 0) html += summaryCard('Blocs', summary.blocks, 'slate');
                if (summary.questions > 0) html += summaryCard('Questions', summary.questions, 'slate');
                html += '</div>';
            }
            html += '</div>';
        }

        el.innerHTML = html;
    }

    function showExecutionResult(created) {
        const el = document.getElementById('bulk-fill-results');
        el.classList.remove('hidden');

        let html = '<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">';
        html += '<div class="flex items-center gap-2 mb-3"><svg class="w-6 h-6 text-green-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="font-bold text-green-800 dark:text-green-300 text-lg">Remplissage terminé !</span></div>';
        html += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">';
        if (created.modules > 0) html += summaryCard('Modules créés', created.modules, 'blue');
        if (created.lessons > 0) html += summaryCard('Leçons créées', created.lessons, 'green');
        if (created.blocks > 0) html += summaryCard('Blocs ajoutés', created.blocks, 'purple');
        if (created.questions > 0) html += summaryCard('Questions', created.questions, 'orange');
        html += '</div>';
        html += '<p class="text-sm text-green-700 dark:text-green-400 mt-3">Rechargement de la page...</p>';
        html += '</div>';

        el.innerHTML = html;
    }

    function summaryCard(label, count, color) {
        return '<div class="bg-' + color + '-100 dark:bg-' + color + '-900/30 rounded-lg p-3 text-center">' +
            '<p class="text-2xl font-bold text-' + color + '-700 dark:text-' + color + '-300">' + count + '</p>' +
            '<p class="text-xs text-' + color + '-600 dark:text-' + color + '-400">' + label + '</p></div>';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>
