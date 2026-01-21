@extends('admin.layout')

@section('title', 'Messagerie interne - ' . ($conversation->nom ?? 'Conversation'))
@section('header', 'Messagerie interne')
@section('subheader', 'Communiquez avec les autres administrateurs')

@section('content')
<div class="flex h-[calc(100vh-12rem)] gap-4">
    <!-- Liste des conversations -->
    <div class="w-80 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col">
        <!-- Header avec bouton nouveau chat -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Conversations</h2>
                <button id="btn-nouveau-chat" class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Sélection admin pour nouveau chat -->
            <div id="select-admin" class="hidden">
                <select id="admin-select" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Sélectionner un administrateur...</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Liste des conversations -->
        <div class="flex-1 overflow-y-auto">
            @php
                $allConversations = \App\Models\AdminConversation::whereHas('members', function($query) {
                    $query->where('users.id', auth()->id());
                })
                ->with(['dernierMessage.user', 'members'])
                ->orderBy('dernier_message_at', 'desc')
                ->get();
            @endphp
            @forelse($allConversations as $conv)
                @php
                    $otherMember = $conv->members->where('id', '!=', auth()->id())->first();
                    $lastMessage = $conv->dernierMessage;
                @endphp
                <a href="{{ route('admin.messagerie-interne.show', $conv->id) }}" 
                   class="block p-4 border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition {{ $conversation->id == $conv->id ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                            {{ strtoupper(substr($otherMember->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    {{ $otherMember->name ?? 'Conversation' }}
                                </p>
                                @if($lastMessage)
                                    <span class="text-xs text-slate-500 dark:text-slate-400 flex-shrink-0 ml-2">
                                        {{ $lastMessage->created_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                            @if($lastMessage)
                                <p class="text-sm text-slate-600 dark:text-slate-400 truncate">
                                    {{ $lastMessage->contenu ?? ($lastMessage->estImage() ? '📷 Image' : ($lastMessage->estVideo() ? '🎥 Vidéo' : '')) }}
                                </p>
                            @else
                                <p class="text-sm text-slate-400 dark:text-slate-500 italic">Aucun message</p>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                    <p>Aucune conversation</p>
                    <p class="text-sm mt-2">Cliquez sur + pour démarrer une nouvelle conversation</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Zone de chat -->
    <div class="flex-1 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col">
        <!-- Header de la conversation -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            @php
                $otherMember = $conversation->members->where('id', '!=', auth()->id())->first();
            @endphp
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($otherMember->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $otherMember->name ?? 'Conversation' }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" id="typing-indicator"></p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4">
            @foreach($messages as $message)
                @include('admin.messagerie-interne.message', ['message' => $message, 'currentUserId' => auth()->id()])
            @endforeach
        </div>

        <!-- Zone de saisie -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            <!-- Preview fichier uploadé -->
            <div id="file-preview" class="hidden mb-3">
                <div class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-700 rounded-lg">
                    <img id="file-preview-img" src="" alt="" class="w-16 h-16 object-cover rounded hidden">
                    <video id="file-preview-video" src="" class="w-16 h-16 object-cover rounded hidden"></video>
                    <div class="flex-1">
                        <p id="file-preview-name" class="text-sm font-medium text-slate-900 dark:text-white"></p>
                        <p id="file-preview-type" class="text-xs text-slate-500 dark:text-slate-400"></p>
                    </div>
                    <button id="file-preview-remove" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Barre d'outils -->
            <div class="flex items-center gap-2 mb-2">
                <button id="btn-upload-image" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition" title="Envoyer une image">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <input type="file" id="input-file" accept="image/*,video/*" class="hidden">
            </div>

            <!-- Zone de saisie -->
            <div class="flex items-end gap-2">
                <textarea 
                    id="message-input" 
                    rows="1" 
                    placeholder="Tapez votre message..."
                    class="flex-1 px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-green-500"
                ></textarea>
                <button 
                    id="btn-send" 
                    class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                    data-conversation-id="{{ $conversation->id }}"
                >
                    Envoyer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/admin-internal-messaging.js'])
<script>
document.addEventListener('DOMContentLoaded', function() {
    const conversationId = {{ $conversation->id }};
    const currentUserId = {{ auth()->id() }};
    
    // Initialiser la messagerie
    if (typeof AdminInternalMessaging !== 'undefined') {
        const messaging = new AdminInternalMessaging(conversationId, currentUserId);
        messaging.init();
    }

    // Nouveau chat
    const btnNouveauChat = document.getElementById('btn-nouveau-chat');
    const selectAdmin = document.getElementById('select-admin');
    const adminSelect = document.getElementById('admin-select');
    
    btnNouveauChat.addEventListener('click', function() {
        selectAdmin.classList.toggle('hidden');
    });
    
    adminSelect.addEventListener('change', function() {
        if (this.value) {
            fetch('{{ route("api.messagerie-interne.conversations") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    user_id: this.value
                })
            })
            .then(response => response.json())
            .then(data => {
                window.location.href = '/admin/messagerie-interne/' + data.conversation.id;
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création de la conversation');
            });
        }
    });
});
</script>
@endpush
@endsection
