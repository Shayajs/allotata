@extends('admin.layout')

@section('title', 'Messagerie interne')
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
            @forelse($conversations as $conv)
                @php
                    $otherMember = $conv->members->where('id', '!=', auth()->id())->first();
                    $lastMessage = $conv->dernierMessage;
                @endphp
                <a href="{{ route('admin.messagerie-interne.show', $conv->id) }}" 
                   class="block p-4 border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition {{ request()->route('conversation') == $conv->id ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
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
        <div class="flex-1 flex items-center justify-center text-slate-500 dark:text-slate-400">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="text-lg font-medium">Sélectionnez une conversation</p>
                <p class="text-sm mt-2">ou créez-en une nouvelle</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/admin-internal-messaging.js'])
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnNouveauChat = document.getElementById('btn-nouveau-chat');
    const selectAdmin = document.getElementById('select-admin');
    const adminSelect = document.getElementById('admin-select');
    
    btnNouveauChat.addEventListener('click', function() {
        selectAdmin.classList.toggle('hidden');
    });
    
    adminSelect.addEventListener('change', function() {
        if (this.value) {
            // Créer ou obtenir la conversation
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
                // Rediriger vers la conversation
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
