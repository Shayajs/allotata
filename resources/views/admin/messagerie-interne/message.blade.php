@php
    $isMine = $message->user_id == $currentUserId;
@endphp
<div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} message-item" data-message-id="{{ $message->id }}">
    <div class="flex items-start gap-2 max-w-[70%] {{ $isMine ? 'flex-row-reverse' : '' }}">
        <!-- Avatar -->
        @if(!$isMine)
            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($message->user->name ?? '?', 0, 1)) }}
            </div>
        @endif

        <!-- Message -->
        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
            <!-- Nom et timestamp -->
            @if(!$isMine)
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1 px-2">{{ $message->user->name }}</p>
            @endif

            <!-- Bulle de message -->
            <div class="rounded-lg px-4 py-2 {{ $isMine ? 'bg-green-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' }}">
                @if($message->contenu)
                    <p class="whitespace-pre-wrap break-words">{{ $message->contenu }}</p>
                @endif

                @if($message->aFichier())
                    @if($message->estImage())
                        <img src="{{ Storage::disk('public')->url($message->fichier) }}" 
                             alt="Image" 
                             class="mt-2 max-w-full h-auto rounded-lg cursor-pointer"
                             onclick="openImageModal('{{ Storage::disk('public')->url($message->fichier) }}')">
                    @elseif($message->estVideo())
                        <video src="{{ Storage::disk('public')->url($message->fichier) }}" 
                               controls 
                               class="mt-2 max-w-full h-auto rounded-lg">
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
                    </div>
                @endif
            </div>

            <!-- Timestamp et réactions -->
            <div class="flex items-center gap-2 mt-1 px-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $message->created_at->format('H:i') }}
                </span>
                <button 
                    class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition"
                    onclick="showReactionPicker({{ $message->id }})"
                >
                    😊
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour agrandir l'image -->
<div id="image-modal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center" onclick="closeImageModal()">
    <img id="modal-image" src="" alt="" class="max-w-[90%] max-h-[90%] object-contain">
</div>

<!-- Picker de réactions -->
<div id="reaction-picker" class="hidden absolute bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-2 z-50">
    <div class="flex gap-2">
        <button onclick="addReaction({{ $message->id }}, '👍')" class="text-2xl hover:scale-110 transition">👍</button>
        <button onclick="addReaction({{ $message->id }}, '❤️')" class="text-2xl hover:scale-110 transition">❤️</button>
        <button onclick="addReaction({{ $message->id }}, '😂')" class="text-2xl hover:scale-110 transition">😂</button>
        <button onclick="addReaction({{ $message->id }}, '😮')" class="text-2xl hover:scale-110 transition">😮</button>
        <button onclick="addReaction({{ $message->id }}, '😢')" class="text-2xl hover:scale-110 transition">😢</button>
        <button onclick="addReaction({{ $message->id }}, '🔥')" class="text-2xl hover:scale-110 transition">🔥</button>
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
