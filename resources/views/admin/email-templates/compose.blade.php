@extends('admin.layout')

@section('title', 'Composer un email')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center gap-4">
        <a href="{{ route('admin.email-templates.index') }}" 
           class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Composer un email
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Envoyez un email personnalisé à un ou plusieurs destinataires</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-800 dark:text-green-200">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-red-800 dark:text-red-200">{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.email-templates.send') }}" method="POST" class="space-y-6" id="composeForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Editor -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recipients -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Destinataires</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                                Type de destinataires
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-xl cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition recipient-option">
                                    <input type="radio" name="recipient_type" value="custom" checked
                                           class="w-4 h-4 text-green-600 focus:ring-green-500"
                                           onchange="toggleRecipientType()">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email personnalisé</span>
                                        <span class="text-xs text-slate-500">Entrer les adresses</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-xl cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition recipient-option">
                                    <input type="radio" name="recipient_type" value="users"
                                           class="w-4 h-4 text-green-600 focus:ring-green-500"
                                           onchange="toggleRecipientType()">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">Utilisateurs</span>
                                        <span class="text-xs text-slate-500">{{ $usersCount ?? 0 }} utilisateurs</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-600 rounded-xl cursor-pointer hover:border-green-500 dark:hover:border-green-400 transition recipient-option">
                                    <input type="radio" name="recipient_type" value="entreprises"
                                           class="w-4 h-4 text-green-600 focus:ring-green-500"
                                           onchange="toggleRecipientType()">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">Entreprises</span>
                                        <span class="text-xs text-slate-500">{{ $entreprisesCount ?? 0 }} entreprises</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Custom Emails -->
                        <div id="custom-recipients">
                            <label for="recipients" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Adresses email (une par ligne ou séparées par des virgules)
                            </label>
                            <textarea name="recipients" id="recipients" rows="3"
                                      class="ui-textarea w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                      placeholder="exemple@email.com, autre@email.com">{{ old('recipients') }}</textarea>
                        </div>

                        <!-- User Filter -->
                        <div id="users-filter" class="hidden space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Filtrer par statut
                                    </label>
                                    <select name="user_filter" class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                                        <option value="all">Tous les utilisateurs</option>
                                        <option value="verified">Email vérifié uniquement</option>
                                        <option value="active">Actifs (connexion récente)</option>
                                        <option value="with_subscription">Avec abonnement actif</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Limite
                                    </label>
                                    <input type="number" name="user_limit" min="1" max="1000" value="100"
                                           class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <svg class="w-4 h-4 inline-block text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Attention : L'envoi en masse peut prendre du temps. Les emails seront mis en file d'attente.
                            </p>
                        </div>

                        <!-- Entreprise Filter -->
                        <div id="entreprises-filter" class="hidden space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Filtrer par abonnement
                                    </label>
                                    <select name="entreprise_filter" class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                                        <option value="all">Toutes les entreprises</option>
                                        <option value="with_subscription">Avec abonnement actif</option>
                                        <option value="trial">En période d'essai</option>
                                        <option value="expired">Abonnement expiré</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Limite
                                    </label>
                                    <input type="number" name="entreprise_limit" min="1" max="500" value="50"
                                           class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Content -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Contenu de l'email</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Sujet <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                   placeholder="Sujet de votre email">
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Body Editor -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Corps du message <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="toggleEditor('visual')" id="btn-visual"
                                            class="px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg">
                                        Visuel
                                    </button>
                                    <button type="button" onclick="toggleEditor('code')" id="btn-code"
                                            class="px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">
                                        HTML
                                    </button>
                                </div>
                            </div>
                            
                            <div id="editor-visual" class="min-h-[300px]">
                                <div id="quill-editor" class="rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden"></div>
                            </div>
                            
                            <div id="editor-code" class="hidden">
                                <textarea name="body_code" id="body-code" rows="15"
                                          class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono text-sm">{{ old('body', '<p>Bonjour,</p>

<p>Votre message ici...</p>

<p>Cordialement,<br>L\'équipe Allo Tata</p>') }}</textarea>
                            </div>
                            
                            <input type="hidden" name="body" id="body-hidden" value="{{ old('body') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Use Template -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Utiliser un template</h2>
                    
                    <select id="template-select" onchange="loadTemplate()"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                        <option value="">-- Sélectionner un template --</option>
                        @foreach($templates ?? [] as $template)
                            <option value="{{ $template->id }}" 
                                    data-subject="{{ $template->subject }}" 
                                    data-body="{{ e($template->body) }}">
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Le contenu du template remplacera le contenu actuel.
                    </p>
                </div>

                <!-- Quick Elements -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Éléments rapides</h2>
                    <div class="space-y-2">
                        <button type="button" onclick="insertElement('button')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                            </svg>
                            Bouton d'action
                        </button>
                        <button type="button" onclick="insertElement('info-box')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Boîte d'information
                        </button>
                        <button type="button" onclick="insertElement('warning-box')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Boîte d'avertissement
                        </button>
                    </div>
                </div>

                <!-- Preview & Send -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-3">
                    <button type="button" onclick="previewEmail()"
                            class="ui-btn-simple w-full px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium rounded-xl transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Prévisualiser
                    </button>
                    
                    <button type="submit" 
                            class="ui-btn-simple w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg shadow-orange-500/25 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Envoyer l'email
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Prévisualisation de l'email</h3>
            <button type="button" onclick="closePreviewModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-6 bg-slate-100 dark:bg-slate-900">
            <div class="bg-white rounded-lg shadow-sm max-w-[600px] mx-auto">
                <iframe id="preview-iframe" class="w-full" style="min-height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
let quill;
let currentMode = 'visual';

document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Rédigez votre message...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link'],
                ['clean']
            ]
        }
    });

    const initialContent = document.getElementById('body-code').value;
    quill.root.innerHTML = initialContent;
    document.querySelector('.ql-editor').style.minHeight = '250px';
});

function toggleRecipientType() {
    const type = document.querySelector('input[name="recipient_type"]:checked').value;
    document.getElementById('custom-recipients').classList.toggle('hidden', type !== 'custom');
    document.getElementById('users-filter').classList.toggle('hidden', type !== 'users');
    document.getElementById('entreprises-filter').classList.toggle('hidden', type !== 'entreprises');
}

function toggleEditor(mode) {
    currentMode = mode;
    const visualEditor = document.getElementById('editor-visual');
    const codeEditor = document.getElementById('editor-code');
    const btnVisual = document.getElementById('btn-visual');
    const btnCode = document.getElementById('btn-code');
    const bodyCode = document.getElementById('body-code');

    if (mode === 'visual') {
        quill.root.innerHTML = bodyCode.value;
        visualEditor.classList.remove('hidden');
        codeEditor.classList.add('hidden');
        btnVisual.className = 'px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg';
        btnCode.className = 'px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg';
    } else {
        bodyCode.value = quill.root.innerHTML;
        visualEditor.classList.add('hidden');
        codeEditor.classList.remove('hidden');
        btnCode.className = 'px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg';
        btnVisual.className = 'px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg';
    }
}

function loadTemplate() {
    const select = document.getElementById('template-select');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const subject = option.dataset.subject;
        const body = option.dataset.body;
        
        document.getElementById('subject').value = subject;
        
        // Decode HTML entities
        const textarea = document.createElement('textarea');
        textarea.innerHTML = body;
        const decodedBody = textarea.value;
        
        document.getElementById('body-code').value = decodedBody;
        quill.root.innerHTML = decodedBody;
    }
}

function insertElement(type) {
    let html = '';
    switch(type) {
        case 'button':
            html = '<div class="button-container"><a href="#" class="button">Cliquez ici</a></div>';
            break;
        case 'info-box':
            html = '<div class="info-box"><h3>Information</h3><p>Votre message ici...</p></div>';
            break;
        case 'warning-box':
            html = '<div class="warning-box"><h3>Attention</h3><p>Votre message ici...</p></div>';
            break;
    }
    
    if (currentMode === 'visual') {
        const range = quill.getSelection(true);
        quill.clipboard.dangerouslyPasteHTML(range.index, html);
    } else {
        const textarea = document.getElementById('body-code');
        const start = textarea.selectionStart;
        textarea.value = textarea.value.substring(0, start) + html + textarea.value.substring(start);
        textarea.focus();
    }
}

function previewEmail() {
    const body = currentMode === 'visual' ? quill.root.innerHTML : document.getElementById('body-code').value;
    const subject = document.getElementById('subject').value || 'Aperçu';
    
    // Create preview URL
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.email-templates.preview-compose") }}';
    form.target = 'preview-iframe';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    const bodyInput = document.createElement('input');
    bodyInput.type = 'hidden';
    bodyInput.name = 'body';
    bodyInput.value = body;
    form.appendChild(bodyInput);
    
    const subjectInput = document.createElement('input');
    subjectInput.type = 'hidden';
    subjectInput.name = 'subject';
    subjectInput.value = subject;
    form.appendChild(subjectInput);
    
    document.body.appendChild(form);
    
    const iframe = document.getElementById('preview-iframe');
    iframe.name = 'preview-iframe';
    
    document.getElementById('previewModal').classList.remove('hidden');
    form.submit();
    form.remove();
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

document.getElementById('composeForm').addEventListener('submit', function() {
    const bodyHidden = document.getElementById('body-hidden');
    if (currentMode === 'visual') {
        bodyHidden.value = quill.root.innerHTML;
    } else {
        bodyHidden.value = document.getElementById('body-code').value;
    }
});

document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreviewModal();
    }
});
</script>

<style>
.ql-toolbar {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    background: #f9fafb;
}
.dark .ql-toolbar {
    background: #1e293b;
    border-color: #475569;
}
.ql-container {
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}
.dark .ql-container {
    border-color: #475569;
}
.dark .ql-editor {
    color: #e2e8f0;
}
.dark .ql-editor.ql-blank::before {
    color: #64748b;
}
.dark .ql-toolbar .ql-stroke {
    stroke: #94a3b8;
}
.dark .ql-toolbar .ql-fill {
    fill: #94a3b8;
}
.dark .ql-toolbar .ql-picker-label {
    color: #94a3b8;
}

.recipient-option:has(input:checked) {
    border-color: #22c55e;
    background-color: #f0fdf4;
}
.dark .recipient-option:has(input:checked) {
    background-color: rgba(34, 197, 94, 0.1);
}
</style>
@endsection
