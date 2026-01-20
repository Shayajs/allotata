@extends('admin.layout')

@section('title', 'Kanban')
@section('header', 'Kanban')
@section('subheader', 'Gérez vos tâches, réservations et tickets visuellement')

@push('styles')
<style>
    .kanban-column {
        min-height: 500px;
    }
    .kanban-card {
        transition: all 0.2s;
    }
    .kanban-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .kanban-card.dragging {
        opacity: 0.5;
    }
</style>
@endpush

@section('content')
<div x-data="kanbanData({{ $board->id }})" class="space-y-6">
    <!-- Actions -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $board->nom }}</h2>
            @if($board->description)
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $board->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button 
                @click="syncReservations()"
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition"
            >
                Synchroniser Réservations
            </button>
            <button 
                @click="syncTickets()"
                class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-medium transition"
            >
                Synchroniser Tickets
            </button>
            <button 
                @click="showCreateCardModal = true"
                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition"
            >
                + Nouvelle Carte
            </button>
        </div>
    </div>

    <!-- Board Kanban -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 overflow-x-auto pb-4">
        @foreach($board->columns as $column)
            <div 
                class="kanban-column bg-slate-100 dark:bg-slate-800 rounded-lg p-4 min-w-[280px]"
                data-column-id="{{ $column->id }}"
            >
                <!-- En-tête de colonne -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div 
                            class="w-3 h-3 rounded-full"
                            style="background-color: {{ $column->couleur ?? '#3b82f6' }}"
                        ></div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ $column->nom }}</h3>
                        <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs">
                            {{ $column->cards->count() }}
                        </span>
                    </div>
                </div>

                <!-- Cartes -->
                <div 
                    class="space-y-3"
                    data-column="{{ $column->id }}"
                    id="column-{{ $column->id }}"
                >
                    @foreach($column->cards as $card)
                        <div 
                            class="kanban-card bg-white dark:bg-slate-700 rounded-lg p-4 shadow-sm border border-slate-200 dark:border-slate-600 cursor-move"
                            data-card-id="{{ $card->id }}"
                            draggable="true"
                            @dragstart="handleDragStart($event, {{ $card->id }})"
                            @dragend="handleDragEnd($event)"
                            @click="editCard({{ $card->id }})"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-medium text-slate-900 dark:text-white text-sm">{{ $card->titre }}</h4>
                                <span class="px-2 py-0.5 text-xs rounded {{ $card->priorite === 'urgente' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : ($card->priorite === 'haute' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : ($card->priorite === 'basse' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-800 dark:bg-slate-600 dark:text-slate-300')) }}">
                                    {{ ucfirst($card->priorite) }}
                                </span>
                            </div>
                            
                            @if($card->description)
                                <p class="text-xs text-slate-600 dark:text-slate-400 mb-2 line-clamp-2">{{ Str::limit($card->description, 100) }}</p>
                            @endif

                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center gap-2">
                                    @if($card->assignee)
                                        <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-xs font-medium text-green-700 dark:text-green-400">
                                            {{ substr($card->assignee->name, 0, 1) }}
                                        </div>
                                    @endif
                                    @if($card->type !== 'tache')
                                        <span class="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded">
                                            {{ ucfirst($card->type) }}
                                        </span>
                                    @endif
                                </div>
                                @if($card->due_date)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ \Carbon\Carbon::parse($card->due_date)->format('d/m') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal Création/Édition Carte -->
<div 
    x-show="showCreateCardModal || showEditCardModal"
    x-cloak
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="showCreateCardModal = false; showEditCardModal = false"
>
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">
            <span x-text="showEditCardModal ? 'Modifier la carte' : 'Nouvelle carte'"></span>
        </h3>
        <form @submit.prevent="saveCard()">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre</label>
                    <input 
                        type="text" 
                        x-model="cardForm.titre"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        required
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea 
                        x-model="cardForm.description"
                        rows="3"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Colonne</label>
                    <select 
                        x-model="cardForm.column_id"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        required
                    >
                        <option value="">Sélectionner une colonne</option>
                        @foreach($board->columns as $column)
                            <option value="{{ $column->id }}">{{ $column->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Priorité</label>
                        <select 
                            x-model="cardForm.priorite"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="basse">Basse</option>
                            <option value="normale">Normale</option>
                            <option value="haute">Haute</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type</label>
                        <select 
                            x-model="cardForm.type"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                            <option value="tache">Tâche</option>
                            <option value="reservation">Réservation</option>
                            <option value="ticket">Ticket</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button 
                    type="button"
                    @click="showCreateCardModal = false; showEditCardModal = false"
                    class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
                >
                    Annuler
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg"
                >
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@vite(['resources/js/admin-kanban.js'])
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush
@endsection
