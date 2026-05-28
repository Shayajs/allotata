@extends('admin.layout')

@section('title', 'Créer un template d\'email')

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
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Créer un nouveau template</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Créez un template d'email personnalisé</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl">
            <ul class="list-disc list-inside text-red-800 dark:text-red-200 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.email-templates.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Editor -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h2>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Type (identifiant unique) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="type" id="type" value="{{ old('type') }}" required
                                       pattern="[a-z_]+"
                                       class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono"
                                       placeholder="mon_template">
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lettres minuscules et underscores uniquement</p>
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Nom du template <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                       placeholder="Mon Template">
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Sujet de l'email <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                   placeholder="Bonjour {nom}, voici votre email">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Description (usage interne)
                            </label>
                            <input type="text" name="description" id="description" value="{{ old('description') }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
                                   placeholder="Décrivez l'utilisation de ce template...">
                        </div>

                        <div>
                            <label for="variables" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Variables (séparées par des virgules)
                            </label>
                            <input type="text" name="variables" id="variables" value="{{ old('variables') }}"
                                   class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono"
                                   placeholder="nom, email, date, montant">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ces variables seront utilisables avec {nom_variable}</p>
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
                        <textarea name="body_code" id="body-code" rows="20"
                                  class="ui-textarea w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-slate-700 dark:text-white font-mono text-sm">{{ old('body', '<h1 style="color: #22c55e;">Titre de l\'email</h1>

<p>Bonjour {nom},</p>

<p>Votre contenu ici...</p>

<div class="button-container">
    <a href="{url}" class="button">Cliquez ici</a>
</div>

<p>Cordialement,<br>L\'équipe Allo Tata</p>') }}</textarea>
                    </div>
                    
                    <input type="hidden" name="body" id="body-hidden" value="{{ old('body') }}">
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statut</h2>
                    
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-5 h-5 rounded border-slate-300 text-green-600 focus:ring-green-500">
                        <span class="text-slate-700 dark:text-slate-300">Template actif</span>
                    </label>
                </div>

                <!-- Quick Elements -->
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
                    </div>
                </div>

                <!-- Example Variables -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Variables communes</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        Cliquez pour insérer
                    </p>
                    <div class="space-y-2">
                        @foreach(['nom', 'email', 'entreprise', 'date', 'montant', 'url'] as $var)
                            <button type="button" onclick="insertVariable('{{ $var }}')"
                                    class="w-full px-3 py-2 text-left text-sm font-mono bg-slate-50 dark:bg-slate-700 hover:bg-green-50 dark:hover:bg-green-900/30 text-slate-700 dark:text-slate-300 hover:text-green-700 dark:hover:text-green-400 rounded-lg transition">
                                {{'{'}}{{ $var }}{{'}'}}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Save Actions -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <button type="submit" 
                            class="ui-btn-simple w-full px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg shadow-green-500/25 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Créer le template
                    </button>
                </div>
            </div>
        </div>
    </form>
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

    const initialContent = document.getElementById('body-code').value;
    quill.root.innerHTML = initialContent;
    document.querySelector('.ql-editor').style.minHeight = '350px';
});

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
            html = '<div class="details-card"><h3>Détails</h3><p><strong>Label :</strong> Valeur</p></div>';
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

document.querySelector('form').addEventListener('submit', function() {
    const bodyHidden = document.getElementById('body-hidden');
    if (currentMode === 'visual') {
        bodyHidden.value = quill.root.innerHTML;
    } else {
        bodyHidden.value = document.getElementById('body-code').value;
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
