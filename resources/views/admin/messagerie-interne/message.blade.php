@php
    $isMine = $message->user_id == $currentUserId;
@endphp
<div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} message-item" data-message-id="{{ $message->id }}">
    <div class="flex items-start gap-2 max-w-[70%] {{ $isMine ? 'flex-row-reverse' : '' }}">
        <!-- Avatar -->
        @if(!$isMine)
            <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-r from-green-500 to-orange-500 flex-shrink-0">
                @if($message->user && $message->user->photo_profil)
                    <img 
                        src="/media/{{ $message->user->photo_profil }}" 
                        alt="{{ $message->user->name }}" 
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <span class="text-white font-bold text-sm hidden">
                        {{ strtoupper(substr($message->user->name ?? '?', 0, 1)) }}
                    </span>
                @else
                    <span class="text-white font-bold text-sm">
                        {{ strtoupper(substr($message->user->name ?? '?', 0, 1)) }}
                    </span>
                @endif
            </div>
        @endif

        <!-- Message -->
        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
            <!-- Nom et timestamp -->
            @if(!$isMine)
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1 px-2">{{ $message->user->name }}</p>
            @endif

            <!-- Bulle de message -->
            <div class="rounded-lg px-4 py-2 {{ $isMine ? 'bg-green-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' }} message-bubble" data-message-id="{{ $message->id }}">
                @if($message->contenu)
                    <p class="whitespace-pre-wrap break-words message-content">{{ $message->contenu }}</p>
                @endif
                
                <!-- Zone d'édition (cachée par défaut) -->
                @if($isMine)
                    <div class="message-edit-form hidden mt-2">
                        <textarea 
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"
                            rows="3"
                        >{{ $message->contenu }}</textarea>
                        <div class="flex gap-2 mt-2">
                            <button 
                                onclick="saveMessageEdit({{ $message->id }})"
                                class="px-3 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition"
                            >
                                Enregistrer
                            </button>
                            <button 
                                onclick="cancelMessageEdit({{ $message->id }})"
                                class="px-3 py-1 text-xs bg-slate-500 hover:bg-slate-600 text-white rounded transition"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                @endif

                @if($message->aFichier())
                    @if($message->estImage())
                        <div class="mt-2">
                            <img src="{{ Storage::disk('public')->url($message->fichier) }}" 
                                 alt="Image" 
                                 class="max-w-xs max-h-64 rounded-lg cursor-pointer object-cover hover:opacity-90 transition"
                                 onclick="openImageModal('{{ Storage::disk('public')->url($message->fichier) }}')">
                        </div>
                    @elseif($message->estVideo())
                        <video src="{{ Storage::disk('public')->url($message->fichier) }}" 
                               controls 
                               class="mt-2 max-w-xs max-h-64 rounded-lg object-contain">
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    @endif
                @endif

                <!-- Réactions -->
                @if($message->reactions->count() > 0)
                    @php
                        $reactionsGrouped = $message->reactions->groupBy('emoji');
                    @endphp
                    <div class="flex flex-wrap gap-1 mt-2 pt-2 border-t {{ $isMine ? 'border-green-400/50' : 'border-slate-300 dark:border-slate-600' }}">
                        @foreach($reactionsGrouped as $emoji => $reactions)
                            <button 
                                class="text-xs px-2 py-1 rounded-full bg-white/20 dark:bg-black/20 hover:bg-white/30 dark:hover:bg-black/30 transition"
                                onclick="toggleReaction({{ $message->id }}, '{{ $emoji }}')"
                                title="{{ $reactions->pluck('user.name')->join(', ') }}"
                            >
                                {{ $emoji }} {{ $reactions->count() }}
                            </button>
                        @endforeach
                        @if($isMine)
                            <button 
                                class="text-xs px-2 py-1 rounded-full bg-white/20 dark:bg-black/20 hover:bg-white/30 dark:hover:bg-black/30 transition"
                                onclick="showReactionPicker(event, {{ $message->id }})"
                                title="Ajouter une réaction"
                            >
                                +
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Timestamp et actions -->
            <div class="flex items-center gap-2 mt-1 px-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $message->created_at->format('H:i') }}
                    @if($message->updated_at != $message->created_at)
                        <span class="italic">(modifié)</span>
                    @endif
                </span>
                @if($isMine)
                    <button 
                        class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition"
                        onclick="editMessage({{ $message->id }})"
                        title="Modifier le message"
                    >
                        ✏️
                    </button>
                @endif
                <button 
                    class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition relative"
                    onclick="showReactionPicker(event, {{ $message->id }})"
                    title="Réagir"
                >
                    😊
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function openImageModal(src) {
    document.getElementById('modal-image').src = src;
    document.getElementById('image-modal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
}

function showReactionPicker(messageId) {
    // Implémenté dans admin-internal-messaging.js
}

function addReaction(messageId, emoji) {
    // Implémenté dans admin-internal-messaging.js
}

function toggleReaction(messageId, emoji) {
    // Implémenté dans admin-internal-messaging.js
}
</script>
