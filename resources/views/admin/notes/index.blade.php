@extends('admin.layout')

@section('title', 'Notes Collaboratives')
@section('header', 'Notes Collaboratives')
@section('subheader', 'Créez et partagez des notes en temps réel')

@section('content')
<div class="space-y-6">
    <!-- Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Mes Notes</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                {{ $notes->total() }} note{{ $notes->total() > 1 ? 's' : '' }}
            </p>
        </div>
        <a 
            href="{{ route('admin.notes.show', 'new') }}"
            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition"
        >
            + Nouvelle Note
        </a>
    </div>

    <!-- Liste des notes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($notes as $note)
            <a 
                href="{{ route('admin.notes.show', $note) }}"
                class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-md transition"
            >
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-slate-900 dark:text-white text-lg">{{ $note->titre }}</h3>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $note->updated_at->diffForHumans() }}
                    </span>
                </div>
                
                @if($note->contenu_markdown)
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-3 mb-4">
                        {{ Str::limit(strip_tags($note->contenu_markdown), 150) }}
                    </p>
                @else
                    <p class="text-sm text-slate-400 dark:text-slate-500 italic mb-4">Note vide</p>
                @endif

                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-xs font-medium text-green-700 dark:text-green-400">
                            {{ substr($note->creator->name, 0, 1) }}
                        </div>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $note->creator->name }}</span>
                    </div>
                    
                    @if(isset($note->activeCollaborators) && $note->activeCollaborators->count() > 0)
                        <div class="flex items-center gap-2" data-note-id="{{ $note->id }}" data-note-presence>
                            <span class="text-xs text-slate-500 dark:text-slate-400">En ligne:</span>
                            <div class="flex -space-x-2" data-note-avatars="{{ $note->id }}">
                                @foreach($note->activeCollaborators->take(3) as $activeUser)
                                    @php
                                        $firstName = explode(' ', $activeUser->name)[0] ?? $activeUser->name;
                                        $initial = strtoupper(substr($firstName, 0, 1));
                                        $avatarColor = '#' . substr(md5($activeUser->id), 0, 6);
                                    @endphp
                                    <div 
                                        class="note-avatar w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800 transition-opacity duration-300"
                                        style="background-color: {{ $avatarColor }}20; color: {{ $avatarColor }};"
                                        title="{{ $activeUser->name }}"
                                        data-user-id="{{ $activeUser->id }}"
                                    >
                                        {{ $initial }}
                                    </div>
                                @endforeach
                                @if($note->activeCollaborators->count() > 3)
                                    <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800" data-note-count="{{ $note->id }}">
                                        +{{ $note->activeCollaborators->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium" data-note-count-text="{{ $note->id }}">
                                {{ $note->activeCollaborators->count() }}
                            </span>
                        </div>
                    @elseif($note->collaborators->count() > 1)
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $note->collaborators->count() }} collaborateurs
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-slate-600 dark:text-slate-400 mb-4">Aucune note pour le moment</p>
                <a 
                    href="{{ route('admin.notes.show', 'new') }}"
                    class="inline-block px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition"
                >
                    Créer votre première note
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notes->hasPages())
        <div class="mt-6">
            {{ $notes->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Pusher si disponible
    if (typeof window.PUSHER_APP_KEY === 'undefined' || !window.PUSHER_APP_KEY) {
        console.warn('⚠️ Pusher non configuré, présence en temps réel désactivée');
        return;
    }

    // Importer Pusher dynamiquement
    import('pusher-js').then(({ default: Pusher }) => {
        const pusher = new Pusher(window.PUSHER_APP_KEY, {
            cluster: window.PUSHER_APP_CLUSTER || 'eu',
            forceTLS: true,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            },
        });

        // Écouter les événements de déconnexion pour toutes les notes affichées
        const notes = document.querySelectorAll('[data-note-id]');
        
        notes.forEach(noteElement => {
            const noteId = noteElement.getAttribute('data-note-id');
            
            // S'abonner au canal de présence de la note
            const channel = pusher.subscribe(`presence-note.${noteId}`);
            
            // Écouter quand un utilisateur quitte
            channel.bind('App\\Events\\UserLeftNote', (data) => {
                console.log('➖ Utilisateur a quitté la note', data);
                
                if (data.note && data.note.id == noteId && data.user) {
                    const userId = data.user.id;
                    const avatarsContainer = document.querySelector(`[data-note-avatars="${noteId}"]`);
                    const countText = document.querySelector(`[data-note-count-text="${noteId}"]`);
                    
                    if (avatarsContainer) {
                        // Retirer l'avatar de l'utilisateur qui a quitté
                        const avatar = avatarsContainer.querySelector(`[data-user-id="${userId}"]`);
                        if (avatar) {
                            // Animation de disparition
                            avatar.style.opacity = '0';
                            avatar.style.transform = 'scale(0)';
                            
                            setTimeout(() => {
                                avatar.remove();
                                updatePresenceCount(noteId);
                            }, 300);
                        }
                    }
                }
            });
            
            // Écouter les événements de présence Pusher (member_removed)
            channel.bind('pusher:member_removed', (member) => {
                const userId = member.id || member.user_id;
                console.log('➖ Membre retiré du canal Presence', userId);
                
                const avatarsContainer = document.querySelector(`[data-note-avatars="${noteId}"]`);
                if (avatarsContainer) {
                    const avatar = avatarsContainer.querySelector(`[data-user-id="${userId}"]`);
                    if (avatar) {
                        // Animation de disparition
                        avatar.style.opacity = '0';
                        avatar.style.transform = 'scale(0)';
                        
                        setTimeout(() => {
                            avatar.remove();
                            updatePresenceCount(noteId);
                        }, 300);
                    }
                }
            });
        });
        
        // Fonction pour mettre à jour le compteur de présence
        function updatePresenceCount(noteId) {
            const avatarsContainer = document.querySelector(`[data-note-avatars="${noteId}"]`);
            const countText = document.querySelector(`[data-note-count-text="${noteId}"]`);
            
            if (avatarsContainer && countText) {
                const avatarCount = avatarsContainer.querySelectorAll('.note-avatar').length;
                const countBadge = avatarsContainer.querySelector(`[data-note-count="${noteId}"]`);
                
                if (avatarCount > 0) {
                    countText.textContent = avatarCount;
                    
                    // Mettre à jour ou retirer le badge "+X"
                    if (avatarCount > 3) {
                        if (!countBadge) {
                            const badge = document.createElement('div');
                            badge.className = 'w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800';
                            badge.setAttribute('data-note-count', noteId);
                            badge.textContent = `+${avatarCount - 3}`;
                            avatarsContainer.appendChild(badge);
                        } else {
                            countBadge.textContent = `+${avatarCount - 3}`;
                        }
                    } else if (countBadge) {
                        countBadge.remove();
                    }
                } else {
                    // Plus personne en ligne, masquer toute la section
                    const presenceContainer = document.querySelector(`[data-note-presence][data-note-id="${noteId}"]`);
                    if (presenceContainer) {
                        presenceContainer.style.opacity = '0';
                        setTimeout(() => {
                            presenceContainer.style.display = 'none';
                        }, 300);
                    }
                }
            }
        }
    }).catch(e => {
        console.error('❌ Erreur lors du chargement de Pusher:', e);
    });
});
</script>
@endpush
@endsection
