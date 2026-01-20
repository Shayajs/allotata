@extends('admin.layout')

@section('title', $note->titre . ' - Notes')
@section('header', $note->titre)
@section('subheader', 'Éditeur collaboratif en temps réel')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<style>
    .CodeMirror {
        min-height: 500px;
        font-size: 14px;
    }
    
    /* Mode clair (par défaut) */
    .CodeMirror {
        background-color: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
    }
    .CodeMirror-cursor {
        border-left: 1px solid #1e293b;
    }
    .CodeMirror-selected {
        background-color: #dbeafe;
    }
    .editor-toolbar {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .editor-toolbar button {
        color: #475569;
    }
    .editor-toolbar button:hover {
        background-color: #e2e8f0;
        color: #1e293b;
    }
    .editor-toolbar button.active {
        background-color: #cbd5e1;
    }
    
    /* Mode sombre */
    html.dark .CodeMirror {
        background-color: #1e293b;
        color: #e2e8f0;
        border: 1px solid #334155;
    }
    html.dark .CodeMirror-cursor {
        border-left: 1px solid #e2e8f0;
    }
    html.dark .CodeMirror-selected {
        background-color: #334155;
    }
    html.dark .CodeMirror-gutters {
        background-color: #0f172a;
        border-right: 1px solid #334155;
    }
    html.dark .editor-toolbar {
        background-color: #0f172a;
        border-bottom: 1px solid #334155;
    }
    html.dark .editor-toolbar button {
        color: #94a3b8;
    }
    html.dark .editor-toolbar button:hover {
        background-color: #334155;
        color: #e2e8f0;
    }
    html.dark .editor-toolbar button.active {
        background-color: #475569;
    }
    html.dark .editor-preview {
        background-color: #1e293b;
        color: #e2e8f0;
    }
    html.dark .editor-preview-side {
        background-color: #1e293b;
        border-left: 1px solid #334155;
    }
    
    .collaborator-cursor {
        position: absolute;
        width: 2px;
        height: 20px;
        pointer-events: none;
        z-index: 10;
    }
    .collaborator-name {
        position: absolute;
        top: -20px;
        left: 0;
        padding: 2px 6px;
        font-size: 11px;
        border-radius: 3px;
        white-space: nowrap;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div x-data="notesEditor({{ $note->id }})" x-init="noteTitle = '{{ addslashes($note->titre) }}';" x-cloak x-show="true" class="space-y-6">
    <!-- En-tête avec collaborateurs -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <input 
                type="text"
                x-model="noteTitle"
                @blur="updateTitle()"
                class="text-2xl font-bold bg-transparent border-none focus:outline-none focus:ring-2 focus:ring-green-500 rounded px-2 text-slate-900 dark:text-white"
            >
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    Créée par {{ $note->creator->name }}
                </span>
                @if($note->updated_by && $note->updated_by !== $note->created_by)
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        • Modifiée par {{ $note->updater->name }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <!-- Liste des collaborateurs actifs -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-600 dark:text-slate-400">Collaborateurs:</span>
                <div class="flex -space-x-2">
                    @foreach($note->collaborators as $collaborator)
                        <div 
                            class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-xs font-medium text-green-700 dark:text-green-400 border-2 border-white dark:border-slate-800"
                            title="{{ $collaborator->user->name }}"
                        >
                            {{ substr($collaborator->user->name, 0, 1) }}
                        </div>
                    @endforeach
                </div>
            </div>
            <form method="POST" action="{{ route('admin.notes.destroy', $note) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette note ?')">
                @csrf
                @method('DELETE')
                <button 
                    type="submit"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition"
                >
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    <!-- Éditeur Markdown -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <textarea 
            id="note-editor"
            x-ref="editor"
            x-model="noteContent"
            @input="debouncedSave()"
        >{{ $note->contenu_markdown }}</textarea>
    </div>

    <!-- Prévisualisation (optionnelle) -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Prévisualisation</h3>
        <div 
            id="preview"
            class="prose dark:prose-invert max-w-none"
            x-html="renderedContent"
        ></div>
    </div>
</div>

@push('head-scripts')
@vite(['resources/js/admin-notes.js'])
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@endpush

@push('scripts')
<script>
    // Retirer x-cloak une fois que Alpine a initialisé
    document.addEventListener('alpine:initialized', () => {
        document.querySelectorAll('[x-data*="notesEditor"]').forEach(el => {
            el.removeAttribute('x-cloak');
        });
    });
</script>
@endpush
@endsection
