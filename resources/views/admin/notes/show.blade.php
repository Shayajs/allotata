@extends('admin.layout')

@section('title', $note->titre . ' - Notes')
@section('header', $note->titre)
@section('subheader', 'Éditeur collaboratif en temps réel')

@push('styles')
<style>
    .editor-container {
        height: 600px;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    html.dark .editor-container {
        border-color: #334155;
    }

    .cm-editor {
        height: 100%;
        font-size: 14px;
    }

    .cm-scroller {
        font-family: 'Fira Code', 'Monaco', 'Menlo', monospace;
    }

    .save-status {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .save-status.saving {
        background-color: #fef3c7;
        color: #92400e;
    }

    .save-status.saved {
        background-color: #d1fae5;
        color: #065f46;
    }

    html.dark .save-status.saving {
        background-color: #78350f;
        color: #fcd34d;
    }

    html.dark .save-status.saved {
        background-color: #064e3b;
        color: #34d399;
    }

    .collaborator-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-center;
        font-weight: 600;
        font-size: 13px;
        border: 2px solid white;
    }

    html.dark .collaborator-avatar {
        border-color: #1e293b;
    }

    .collaborator-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    /* Styles pour les curseurs collaborateurs */
    .collaborator-cursor {
        position: absolute;
        width: 2px;
        z-index: 10;
        cursor: default;
        transition: opacity 0.2s;
    }

    .collaborator-cursor:hover {
        opacity: 0.8;
    }

    .collaborator-name-tag {
        position: absolute;
        top: -28px;
        left: 50%;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        white-space: nowrap;
        z-index: 12;
        color: white;
        transform: translateX(-50%);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease-in-out, top 0.2s ease-in-out;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .collaborator-cursor:hover .collaborator-name-tag {
        opacity: 1;
        top: -32px;
    }

    /* Goutte au survol */
    .collaborator-cursor::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 50%;
        width: 8px;
        height: 8px;
        border-radius: 50% 50% 50% 0;
        transform: translateX(-50%) rotate(-45deg);
        background-color: var(--cursor-color, #3b82f6);
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
        z-index: 11;
    }

    .collaborator-cursor:hover::before {
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div x-data="notesEditor({{ $note->id }})" x-cloak class="space-y-6">
    <!-- En-tête -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <input 
                    type="text"
                    x-model="noteTitle"
                    @blur="updateTitle()"
                    value="{{ $note->titre }}"
                    placeholder="Titre de la note..."
                    class="text-2xl font-bold bg-transparent border-none focus:outline-none focus:ring-2 focus:ring-green-500 rounded px-2 py-1 text-slate-900 dark:text-white w-full"
                >
                <div class="flex items-center gap-3 mt-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>Créée par <strong>{{ $note->creator->name }}</strong></span>
                    @if($note->updated_by && $note->updated_by !== $note->created_by)
                        <span>•</span>
                        <span>Modifiée par <strong>{{ $note->updater->name }}</strong></span>
                    @endif
                    <span>•</span>
                    <span x-text="saveStatusText" :class="saveStatusClass" class="save-status"></span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">En ligne:</span>
                    <div class="flex -space-x-2">
                        @foreach($note->collaborators->take(5) as $collaborator)
                            @php
                                $user = $collaborator->user;
                                $firstName = explode(' ', $user->name)[0] ?? $user->name;
                                $initial = strtoupper(substr($firstName, 0, 1));
                                $avatarColor = '#' . substr(md5($user->id), 0, 6);
                            @endphp
                            <div 
                                class="collaborator-avatar overflow-hidden relative"
                                style="background-color: {{ $avatarColor }}20; color: {{ $avatarColor }}; border-color: {{ $avatarColor }};"
                                title="{{ $user->name }}"
                            >
                                @if($user->photo_profil)
                                    <img 
                                        src="{{ asset('media/' . $user->photo_profil) }}" 
                                        alt="{{ $user->name }}"
                                        class="w-full h-full object-cover rounded-full"
                                        onerror="this.parentElement.querySelector('.avatar-fallback').style.display='flex'; this.style.display='none';"
                                    >
                                    <span class="avatar-fallback hidden items-center justify-center w-full h-full absolute inset-0">{{ $initial }}</span>
                                @else
                                    <span class="flex items-center justify-center w-full h-full">{{ $initial }}</span>
                                @endif
                            </div>
                        @endforeach
                        @if($note->collaborators->count() > 5)
                            <div class="collaborator-avatar bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                +{{ $note->collaborators->count() - 5 }}
                            </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.notes.destroy', $note) }}" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette note ?')">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Éditeur -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700 px-4 py-2 bg-slate-50 dark:bg-slate-900/50">
            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Éditeur Markdown</span>
        </div>
        <div class="p-4">
            <div id="editor-container" class="editor-container"></div>
        </div>
    </div>
</div>

@push('head-scripts')
@vite(['resources/js/admin-notes.js'])
@endpush

@push('scripts')
<script>
    // Données initiales pour l'éditeur
    window.noteContent = @json($note->contenu_markdown ?? '');
    
    // Retirer x-cloak une fois Alpine initialisé
    function removeCloak() {
        document.querySelectorAll('[x-data*="notesEditor"]').forEach(el => {
            el.removeAttribute('x-cloak');
        });
    }
    
    if (window.Alpine && window.Alpine.store) {
        removeCloak();
    } else {
        document.addEventListener('alpine:init', removeCloak);
        document.addEventListener('alpine:initialized', removeCloak);
        setTimeout(removeCloak, 500);
    }
</script>
@endpush
@endsection
