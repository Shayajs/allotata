@extends('admin.layout')

@section('title', 'Modifier le template - ' . $template->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.email-templates.index') }}" 
               class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $template->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 font-mono">{{ $template->type }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.email-templates.preview', $template) }}" 
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Prévisualiser
            </a>
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

    <form action="{{ route('admin.email-templates.update', $template) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Editor -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Nom du template
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Sujet de l'email
                            </label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject', $template->subject) }}" required
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                   placeholder="Ex: Bonjour {nom_client}, votre réservation est confirmée">
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Description (usage interne)
                            </label>
                            <input type="text" name="description" id="description" value="{{ old('description', $template->description) }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Body Editor -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Corps de l'email</h2>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="toggleEditor('visual')" id="btn-visual"
                                    class="px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg">
                                Visuel
                            </button>
                            <button type="button" onclick="toggleEditor('code')" id="btn-code"
                                    class="px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">
                                Code HTML
                            </button>
                        </div>
                    </div>
                    
                    <div id="editor-visual" class="min-h-[400px]">
                        <div id="quill-editor" class="rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden"></div>
                    </div>
                    
                    <div id="editor-code" class="hidden">
                        <textarea name="body" id="body-code" rows="20"
                                  class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono text-sm">{{ old('body', $template->body) }}</textarea>
                    </div>
                    
                    <input type="hidden" name="body" id="body-hidden" value="{{ old('body', $template->body) }}">
                    
                    @error('body')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statut</h2>
                    
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" 
                               {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <span class="text-slate-700 dark:text-slate-300">Template actif</span>
                    </label>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Un template inactif ne sera pas utilisé pour l'envoi automatique d'emails.
                    </p>
                </div>

                <!-- Variables Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Variables disponibles</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        Cliquez pour insérer dans l'éditeur
                    </p>
                    
                    @if($template->variables && count($template->variables) > 0)
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($template->variables as $variable)
                                <button type="button" onclick="insertVariable('{{ $variable }}')"
                                        class="w-full px-3 py-2 text-left text-sm font-mono bg-slate-50 dark:bg-slate-700 hover:bg-green-50 dark:hover:bg-green-900/30 text-slate-700 dark:text-slate-300 hover:text-green-700 dark:hover:text-green-400 rounded-lg transition group">
                                    <span class="text-slate-400 group-hover:text-green-500">{'{'}</span>{{ $variable }}<span class="text-slate-400 group-hover:text-green-500">{'}'}</span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">Aucune variable définie pour ce template.</p>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Éléments rapides</h2>
                    <div class="space-y-2">
                        <button type="button" onclick="insertElement('button')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
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
                        <button type="button" onclick="insertElement('details-card')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Carte de détails
                        </button>
                        <button type="button" onclick="insertElement('divider')"
                                class="w-full px-3 py-2 text-left text-sm bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            Séparateur
                        </button>
                    </div>
                </div>

                <!-- Save Actions -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg shadow-green-500/25 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Enregistrer
                    </button>
                    <button type="button" onclick="sendTestFromEdit()"
                            class="w-full mt-3 px-6 py-3 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium rounded-xl transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Envoyer un test
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Envoyer un email de test</h3>
        </div>
        <form method="POST" action="{{ route('admin.email-templates.test', $template) }}">
            @csrf
            <div class="p-6">
                <label for="test_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Adresse email
                </label>
                <input type="email" name="email" id="test_email" value="{{ auth()->user()->email }}" required
                       class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
            </div>
            <div class="p-6 pt-0 flex gap-3">
                <button type="button" onclick="closeTestModal()" 
                        class="flex-1 px-4 py-2.5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    Envoyer
                </button>
            </div>
        </form>
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
    // Initialize Quill
    quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Rédigez le contenu de votre email...',
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

    // Load initial content
    const initialContent = document.getElementById('body-hidden').value;
    quill.root.innerHTML = initialContent;

    // Set editor height
    document.querySelector('.ql-editor').style.minHeight = '350px';
});

function toggleEditor(mode) {
    currentMode = mode;
    const visualEditor = document.getElementById('editor-visual');
    const codeEditor = document.getElementById('editor-code');
    const btnVisual = document.getElementById('btn-visual');
    const btnCode = document.getElementById('btn-code');
    const bodyCode = document.getElementById('body-code');
    const bodyHidden = document.getElementById('body-hidden');

    if (mode === 'visual') {
        // Sync code to visual
        quill.root.innerHTML = bodyCode.value;
        visualEditor.classList.remove('hidden');
        codeEditor.classList.add('hidden');
        btnVisual.className = 'px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg';
        btnCode.className = 'px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg';
    } else {
        // Sync visual to code
        bodyCode.value = quill.root.innerHTML;
        visualEditor.classList.add('hidden');
        codeEditor.classList.remove('hidden');
        btnCode.className = 'px-3 py-1.5 text-sm font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded-lg';
        btnVisual.className = 'px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg';
    }
}

function insertVariable(variable) {
    const text = '{' + variable + '}';
    if (currentMode === 'visual') {
        const range = quill.getSelection(true);
        quill.insertText(range.index, text);
    } else {
        const textarea = document.getElementById('body-code');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + text.length, start + text.length);
    }
}

function insertElement(type) {
    let html = '';
    switch(type) {
        case 'button':
            html = '<div class="button-container"><a href="{url}" class="button">Cliquez ici</a></div>';
            break;
        case 'info-box':
            html = '<div class="info-box"><h3>Information</h3><p>Votre message ici...</p></div>';
            break;
        case 'warning-box':
            html = '<div class="warning-box"><h3>Attention</h3><p>Votre message ici...</p></div>';
            break;
        case 'details-card':
            html = '<div class="details-card"><h3>Détails</h3><p><strong>Label :</strong> Valeur</p><p><strong>Label :</strong> Valeur</p></div>';
            break;
        case 'divider':
            html = '<div class="divider"></div>';
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

function sendTestFromEdit() {
    document.getElementById('testEmailModal').classList.remove('hidden');
}

function closeTestModal() {
    document.getElementById('testEmailModal').classList.add('hidden');
}

// Sync before submit
document.querySelector('form').addEventListener('submit', function() {
    const bodyHidden = document.getElementById('body-hidden');
    if (currentMode === 'visual') {
        bodyHidden.value = quill.root.innerHTML;
    } else {
        bodyHidden.value = document.getElementById('body-code').value;
    }
});

// Close modal on outside click
document.getElementById('testEmailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTestModal();
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
</style>
@endsection
