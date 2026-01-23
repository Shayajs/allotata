@extends('admin.layout')

@section('title', 'Gestion des Cours')
@section('header', 'Gestion des Cours')
@section('subheader', 'Créez et organisez vos modules de cours')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des Cours</h1>
        <button 
            onclick="document.getElementById('create-module-modal').classList.remove('hidden')"
            class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition-all"
        >
            ➕ Nouveau Module
        </button>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total Modules</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $modules->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Actifs</p>
                    <p class="text-2xl font-bold text-green-600">{{ $modules->where('est_actif', true)->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total Leçons</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $modules->sum(fn($m) => $m->lessons->count()) }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des modules avec drag & drop -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Ordre des modules (glissez-déposez pour réorganiser)
        </h2>
        
        <div id="modules-list" class="space-y-4">
            @foreach($modules as $module)
                <div 
                    class="module-item bg-slate-50 dark:bg-slate-700 rounded-lg p-4 border border-slate-200 dark:border-slate-600 cursor-move hover:shadow-md transition-shadow"
                    data-module-id="{{ $module->id }}"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center cursor-move">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </div>
                            
                            @if($module->image_path)
                                <img src="{{ asset('storage/' . $module->image_path) }}" alt="{{ $module->titre }}" class="w-16 h-16 object-cover rounded-lg">
                            @endif
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $module->titre }}</h3>
                                @if($module->description)
                                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1">{{ $module->description }}</p>
                                @endif
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    {{ $module->lessons->count() }} leçon{{ $module->lessons->count() > 1 ? 's' : '' }} • Ordre: {{ $module->ordre }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                @if($module->est_actif)
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded">Actif</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded">Inactif</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 ml-4">
                            <a 
                                href="{{ route('admin.courses.module.edit', $module) }}"
                                class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
                            >
                                Éditer
                            </a>
                            <form method="POST" action="{{ route('admin.courses.modules.destroy', $module) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce module ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($modules->count() === 0)
            <p class="text-center text-slate-500 dark:text-slate-400 py-8">
                Aucun module. Créez votre premier module pour commencer.
            </p>
        @endif
    </div>

    <!-- Modal création module -->
    <div id="create-module-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) document.getElementById('create-module-modal').classList.add('hidden')">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Nouveau Module</h3>
                <button onclick="document.getElementById('create-module-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.courses.modules.store') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Titre <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="titre" 
                            required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Description
                        </label>
                        <textarea 
                            name="description"
                            rows="3"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Image de couverture
                        </label>
                        <input 
                            type="file" 
                            name="image"
                            accept="image/*"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Ordre
                            </label>
                            <input 
                                type="number" 
                                name="ordre"
                                value="{{ $modules->max('ordre') + 1 }}"
                                min="0"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="flex items-center gap-3 mt-8">
                                <input 
                                    type="checkbox" 
                                    name="est_actif" 
                                    value="1"
                                    checked
                                    class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-green-600 focus:ring-green-500"
                                >
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Module actif</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        type="submit" 
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition"
                    >
                        Créer le module
                    </button>
                    <button 
                        type="button" 
                        onclick="document.getElementById('create-module-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modulesList = document.getElementById('modules-list');
        if (modulesList) {
            new Sortable(modulesList, {
                animation: 150,
                handle: '.module-item',
                onEnd: async function(evt) {
                    const order = Array.from(modulesList.children).map((item, index) => {
                        return item.dataset.moduleId;
                    });

                    try {
                        const response = await fetch('{{ route("admin.courses.modules.order") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ order: order })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // Optionnel: afficher un message de succès
                            location.reload(); // Recharger pour mettre à jour les ordres affichés
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Erreur lors de la mise à jour de l\'ordre');
                    }
                }
            });
        }
    });
</script>
@endpush
