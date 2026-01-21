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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="notes-list">
        @forelse($notes as $note)
            <a 
                href="{{ route('admin.notes.show', $note) }}"
                class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-md transition"
                data-note-id="{{ $note->id }}"
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
                    
                    <div class="flex items-center gap-2" data-note-{{ $note->id }}-collaborators>
                        @if(isset($note->activeCollaborators) && $note->activeCollaborators->count() > 0)
                            <span class="text-xs text-slate-500 dark:text-slate-400">En ligne:</span>
                            <div class="flex -space-x-2" data-note-{{ $note->id }}-avatars>
                                @foreach($note->activeCollaborators->take(3) as $activeUser)
                                    @php
                                        $firstName = explode(' ', $activeUser->name)[0] ?? $activeUser->name;
                                        $initial = strtoupper(substr($firstName, 0, 1));
                                        $avatarColor = '#' . substr(md5($activeUser->id), 0, 6);
                                    @endphp
                                    <div 
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800 transition-all duration-300"
                                        style="background-color: {{ $avatarColor }}20; color: {{ $avatarColor }};"
                                        title="{{ $activeUser->name }}"
                                        data-user-id="{{ $activeUser->id }}"
                                        data-user-name="{{ $activeUser->name }}"
                                    >
                                        {{ $initial }}
                                    </div>
                                @endforeach
                                @if($note->activeCollaborators->count() > 3)
                                    <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800" data-note-{{ $note->id }}-count>
                                        +{{ $note->activeCollaborators->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium" data-note-{{ $note->id }}-count-text>
                                {{ $note->activeCollaborators->count() }}
                            </span>
                        @elseif($note->collaborators->count() > 1)
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $note->collaborators->count() }} collaborateurs
                            </span>
                        @endif
                    </div>
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
<script type="module">
    import Pusher from 'pusher-js';

    // Initialiser Pusher pour la présence en temps réel dans le dashboard
    function initNotesPresence() {
        const key = window.PUSHER_APP_KEY;
        const cluster = window.PUSHER_APP_CLUSTER || 'mt1';

        if (!key || key === '' || key === 'null' || key === 'undefined') {
            console.warn('⚠️ Pusher non configuré pour la présence en temps réel');
            return;
        }

        const pusher = new Pusher(String(key).trim(), {
            cluster: String(cluster).trim(),
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

        // Récupérer toutes les notes visibles sur la page
        const noteElements = document.querySelectorAll('[data-note-id]');
        const noteIds = Array.from(noteElements).map(el => el.getAttribute('data-note-id'));

        console.log('📡 Connexion à Pusher pour', noteIds.length, 'notes');

        // Fonction pour mettre à jour les collaborateurs depuis les membres Pusher
        function updateCollaborators(noteId, members) {
            // Convertir les membres en tableau
            const activeUsers = Object.values(members).map(member => ({
                id: Number(member.id || member.user_id),
                name: member.info?.name || member.name || 'Utilisateur',
            }));

            // Mettre à jour l'affichage (cette fonction gère aussi le cas où il n'y a pas encore d'avatars)
            renderCollaborators(noteId, activeUsers);
        }

        // Fonction pour ajouter un collaborateur
        function addCollaborator(noteId, member) {
            const memberData = {
                id: Number(member.id || member.user_id),
                name: member.info?.name || member.name || 'Utilisateur',
            };

            // Récupérer les collaborateurs actuels
            const avatarsContainer = document.querySelector(`[data-note-${noteId}-avatars]`);
            if (!avatarsContainer) return;

            const existingIds = Array.from(avatarsContainer.querySelectorAll('[data-user-id]'))
                .map(el => Number(el.getAttribute('data-user-id')));

            // Si déjà présent, ne rien faire
            if (existingIds.includes(memberData.id)) return;

            // Récupérer tous les collaborateurs actifs (depuis les éléments DOM + le nouveau)
            const activeUsers = Array.from(avatarsContainer.querySelectorAll('[data-user-id]'))
                .map(el => ({
                    id: Number(el.getAttribute('data-user-id')),
                    name: el.getAttribute('data-user-name') || 'Utilisateur',
                }));

            activeUsers.push(memberData);

            // Mettre à jour l'affichage
            renderCollaborators(noteId, activeUsers);
        }

        // Fonction pour retirer un collaborateur
        function removeCollaborator(noteId, member) {
            const userId = Number(member.id || member.user_id);
            const avatarsContainer = document.querySelector(`[data-note-${noteId}-avatars]`);

            if (!avatarsContainer) return;

            // Retirer l'avatar avec animation
            const avatarEl = avatarsContainer.querySelector(`[data-user-id="${userId}"]`);
            if (avatarEl) {
                avatarEl.style.transition = 'opacity 0.3s, transform 0.3s';
                avatarEl.style.opacity = '0';
                avatarEl.style.transform = 'scale(0)';
                
                setTimeout(() => {
                    avatarEl.remove();
                    updateCollaboratorsCount(noteId);
                }, 300);
            } else {
                // Si pas trouvé directement, recalculer depuis tous les collaborateurs
                const activeUsers = Array.from(avatarsContainer.querySelectorAll('[data-user-id]'))
                    .map(el => ({
                        id: Number(el.getAttribute('data-user-id')),
                        name: el.getAttribute('data-user-name') || 'Utilisateur',
                    }))
                    .filter(user => user.id !== userId);

                renderCollaborators(noteId, activeUsers);
            }
        }

        // Fonction pour rendre les collaborateurs
        function renderCollaborators(noteId, activeUsers) {
            const avatarsContainer = document.querySelector(`[data-note-${noteId}-avatars]`);
            const countText = document.querySelector(`[data-note-${noteId}-count-text]`);
            const collaboratorsContainer = document.querySelector(`[data-note-${noteId}-collaborators]`);

            if (!collaboratorsContainer) return;

            // Si aucun collaborateur actif
            if (activeUsers.length === 0) {
                avatarsContainer?.remove();
                if (countText) countText.remove();
                return;
            }

            // Créer ou mettre à jour le conteneur d'avatars
            if (!avatarsContainer) {
                const newContainer = document.createElement('div');
                newContainer.className = 'flex -space-x-2';
                newContainer.setAttribute(`data-note-${noteId}-avatars`, '');
                
                const label = document.createElement('span');
                label.className = 'text-xs text-slate-500 dark:text-slate-400';
                label.textContent = 'En ligne:';
                
                collaboratorsContainer.prepend(label);
                collaboratorsContainer.appendChild(newContainer);
            }

            const container = avatarsContainer || document.querySelector(`[data-note-${noteId}-avatars]`);
            if (!container) return;

            // Vider le conteneur
            container.innerHTML = '';

            // Ajouter les avatars (max 3)
            activeUsers.slice(0, 3).forEach(user => {
                const firstName = user.name.split(' ')[0] || user.name;
                const initial = firstName.charAt(0).toUpperCase();
                const avatarColor = '#' + user.id.toString(16).padStart(6, '0').slice(-6);

                const avatarEl = document.createElement('div');
                avatarEl.className = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800 transition-all duration-300';
                avatarEl.style.backgroundColor = avatarColor + '20';
                avatarEl.style.color = avatarColor;
                avatarEl.setAttribute('data-user-id', user.id);
                avatarEl.setAttribute('data-user-name', user.name);
                avatarEl.setAttribute('title', user.name);
                avatarEl.textContent = initial;

                // Animation d'apparition
                avatarEl.style.opacity = '0';
                avatarEl.style.transform = 'scale(0)';
                container.appendChild(avatarEl);

                setTimeout(() => {
                    avatarEl.style.opacity = '1';
                    avatarEl.style.transform = 'scale(1)';
                }, 10);
            });

            // Ajouter le badge de compteur si plus de 3
            if (activeUsers.length > 3) {
                const countBadge = document.createElement('div');
                countBadge.className = 'w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs font-medium border-2 border-white dark:border-slate-800';
                countBadge.setAttribute(`data-note-${noteId}-count`, '');
                countBadge.textContent = `+${activeUsers.length - 3}`;
                container.appendChild(countBadge);
            }

            // Mettre à jour le compteur
            updateCollaboratorsCount(noteId, activeUsers.length);
        }

        // Fonction pour mettre à jour le compteur
        function updateCollaboratorsCount(noteId, count) {
            const countText = document.querySelector(`[data-note-${noteId}-count-text]`);
            const collaboratorsContainer = document.querySelector(`[data-note-${noteId}-collaborators]`);

            if (!count) {
                // Compter depuis les avatars actuels
                const avatarsContainer = document.querySelector(`[data-note-${noteId}-avatars]`);
                if (avatarsContainer) {
                    count = avatarsContainer.querySelectorAll('[data-user-id]').length;
                    const countBadge = document.querySelector(`[data-note-${noteId}-count]`);
                    if (countBadge) {
                        const overflow = parseInt(countBadge.textContent.replace('+', '')) || 0;
                        count += overflow;
                    }
                } else {
                    count = 0;
                }
            }

            if (count === 0) {
                if (countText) countText.remove();
                return;
            }

            if (!countText && count > 0) {
                const newCountText = document.createElement('span');
                newCountText.className = 'text-xs text-green-600 dark:text-green-400 font-medium';
                newCountText.setAttribute(`data-note-${noteId}-count-text`, '');
                newCountText.textContent = count;
                if (collaboratorsContainer) {
                    collaboratorsContainer.appendChild(newCountText);
                }
            } else if (countText) {
                countText.textContent = count;
            }
        }

        // Se connecter à chaque canal de note
        noteIds.forEach(noteId => {
            const channelName = `presence-note.${noteId}`;
            const channel = pusher.subscribe(channelName);

            // Canal souscrit avec succès
            channel.bind('pusher:subscription_succeeded', (members) => {
                updateCollaborators(noteId, members.members || {});
            });

            // Utilisateur rejoint
            channel.bind('pusher:member_added', (member) => {
                console.log('➕ Utilisateur rejoint la note', noteId, ':', member);
                addCollaborator(noteId, member);
            });

            // Utilisateur part
            channel.bind('pusher:member_removed', (member) => {
                console.log('➖ Utilisateur quitte la note', noteId, ':', member);
                removeCollaborator(noteId, member);
            });
        });
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotesPresence);
    } else {
        initNotesPresence();
    }
</script>
@endpush
@endsection
