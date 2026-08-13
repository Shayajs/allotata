@extends('admin.layout')

@section('title', 'Médiathèque')
@section('header', 'Médiathèque')
@section('subheader', 'Gestion de tous vos fichiers multimédias')

@section('content')
<div class="space-y-6">
    <!-- Barre d'outils -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <!-- Recherche et filtres -->
            <div class="flex flex-1 items-center gap-3 w-full md:w-auto">
                <div class="relative flex-1 md:w-64">
                    <input 
                        type="text" 
                        id="media-search"
                        placeholder="Rechercher un fichier..."
                        class="no-ui-input w-full py-2 pl-10 pr-4 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white min-h-11"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <select id="media-type-filter" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Tous les types</option>
                    <option value="image">Images</option>
                    <option value="video">Vidéos</option>
                    <option value="audio">Audio</option>
                    <option value="pdf">PDF</option>
                    <option value="text">Textes</option>
                    <option value="document">Documents</option>
                </select>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center gap-2">
                <div id="multi-select-actions" class="hidden flex items-center gap-2 mr-2 border-r border-slate-300 dark:border-slate-600 pr-2">
                    <span id="selected-count" class="text-sm text-slate-600 dark:text-slate-400 font-medium">0 sélectionné</span>
                    <button 
                        onclick="mediaLibrary.moveSelectedFiles()"
                        class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition text-sm font-medium flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Déplacer
                    </button>
                    <button 
                        onclick="mediaLibrary.deleteSelectedFiles()"
                        class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium"
                    >
                        Supprimer
                    </button>
                </div>
                <button 
                    onclick="mediaLibrary.openCreateFolderModal()"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition font-medium flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouveau dossier
                </button>
                <label for="file-upload" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium cursor-pointer flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Uploader
                </label>
                <input type="file" id="file-upload" multiple class="hidden">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar - Arborescence des dossiers -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sticky top-24">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Dossiers
                </h3>
                <div id="folder-tree" class="space-y-1 max-h-[600px] overflow-y-auto folder-tree" 
                     ondrop="if(mediaLibrary && mediaLibrary.draggedFileId) { event.preventDefault(); event.stopPropagation(); mediaLibrary.moveFileToFolder(mediaLibrary.draggedFileId, '/'); }" 
                     ondragover="if(mediaLibrary && mediaLibrary.draggedFileId) { event.preventDefault(); event.stopPropagation(); event.currentTarget.style.backgroundColor = 'rgb(220, 252, 231)'; }" 
                     ondragleave="event.currentTarget.style.backgroundColor = '';">
                    <!-- Arborescence chargée dynamiquement -->
                </div>
            </div>
        </div>

        <!-- Zone principale - Liste des fichiers -->
        <div class="lg:col-span-3">
            <!-- Fil d'Ariane -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-4">
                <nav id="breadcrumb" class="flex items-center gap-2 text-sm">
                    <!-- Breadcrumb chargé dynamiquement -->
                </nav>
            </div>

            <!-- Zone de glisser-déposer -->
            <div id="drop-zone" class="hidden border-2 border-dashed border-green-500 bg-green-50 dark:bg-green-900/20 rounded-xl p-8 text-center mb-4 transition">
                <svg class="w-16 h-16 mx-auto text-green-600 dark:text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-lg font-semibold text-green-700 dark:text-green-300">Déposez vos fichiers ici</p>
                <p class="text-sm text-green-600 dark:text-green-400 mt-2">Ils seront automatiquement uploadés</p>
            </div>

            <!-- Barre de progression upload -->
            <div id="upload-progress" class="hidden mb-4 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Upload en cours...</span>
                    <span id="upload-percent" class="text-sm font-medium text-green-600 dark:text-green-400">0%</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div id="upload-progress-bar" class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="upload-status" class="text-xs text-slate-500 dark:text-slate-400 mt-2"></p>
            </div>

            <!-- Liste des fichiers -->
            <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <!-- Fichiers chargés dynamiquement -->
            </div>

            <!-- Chargement -->
            <div id="loading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                <p class="mt-2 text-slate-600 dark:text-slate-400">Chargement...</p>
            </div>

            <!-- Message vide -->
            <div id="empty-state" class="hidden text-center py-12">
                <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-slate-600 dark:text-slate-400">Aucun fichier trouvé</p>
            </div>

            <!-- Pagination -->
            <div id="pagination" class="mt-6 flex justify-center">
                <!-- Pagination chargée dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modale pour sélectionner un fichier (réutilisable) -->
<div id="media-selector-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col">
        <!-- En-tête -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sélectionner un fichier</h2>
            <button id="close-selector-modal" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Contenu -->
        <div class="flex-1 overflow-hidden flex">
            <!-- Sidebar de la modale -->
            <div class="w-64 border-r border-slate-200 dark:border-slate-700 p-4 overflow-y-auto">
                <div class="mb-4">
                    <input 
                        type="text" 
                        id="modal-search"
                        placeholder="Rechercher..."
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm"
                    >
                </div>
                <div id="modal-folder-tree" class="space-y-1">
                    <!-- Arborescence chargée dynamiquement -->
                </div>
            </div>

            <!-- Zone de sélection -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <nav id="modal-breadcrumb" class="flex items-center gap-2 text-sm">
                        <!-- Breadcrumb chargé dynamiquement -->
                    </nav>
                </div>
                <div id="modal-media-grid" class="flex-1 p-4 overflow-y-auto grid grid-cols-4 gap-4">
                    <!-- Fichiers chargés dynamiquement -->
                </div>
            </div>
        </div>

        <!-- Pied -->
        <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
            <button id="cancel-selector" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                Annuler
            </button>
            <button id="confirm-selector" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                Sélectionner
            </button>
        </div>
    </div>
</div>

<!-- Modale de détails/édition -->
<div id="file-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div id="file-details-content" class="p-6">
            <!-- Contenu chargé dynamiquement -->
        </div>
    </div>
</div>

<!-- Modale de création de dossier -->
<div id="create-folder-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Créer un nouveau dossier</h2>
                <button onclick="mediaLibrary.closeCreateFolderModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nom du dossier
                    </label>
                    <input 
                        type="text" 
                        id="new-folder-name"
                        placeholder="Mon nouveau dossier"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Emplacement
                    </label>
                    <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-3 border border-slate-200 dark:border-slate-600">
                        <div id="create-folder-location" class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span id="create-folder-path">Racine</span>
                        </div>
                    </div>
                    <button 
                        onclick="mediaLibrary.openFolderSelectorForCreate()"
                        class="mt-2 text-sm text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Changer l'emplacement
                    </button>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button 
                    onclick="mediaLibrary.closeCreateFolderModal()"
                    class="flex-1 px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                >
                    Annuler
                </button>
                <button 
                    onclick="mediaLibrary.createFolder()"
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
                >
                    Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale de sélection de dossier (pour créer un dossier ou déplacer) -->
<div id="folder-selector-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 id="folder-selector-title" class="text-xl font-bold text-slate-900 dark:text-white">Sélectionner un dossier</h2>
            <button onclick="mediaLibrary.closeFolderSelectorModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-hidden flex">
            <!-- Sidebar avec arborescence -->
            <div class="w-64 border-r border-slate-200 dark:border-slate-700 p-4 overflow-y-auto">
                <div id="folder-selector-tree" class="space-y-1">
                    <!-- Arborescence chargée dynamiquement -->
                </div>
            </div>
            <!-- Zone principale avec breadcrumb -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <nav id="folder-selector-breadcrumb" class="flex items-center gap-2 text-sm">
                        <!-- Breadcrumb chargé dynamiquement -->
                    </nav>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
            <button 
                onclick="mediaLibrary.closeFolderSelectorModal()"
                class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
            >
                Annuler
            </button>
            <button 
                id="folder-selector-confirm"
                onclick="mediaLibrary.confirmFolderSelection()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
            >
                Sélectionner
            </button>
        </div>
    </div>
</div>

<!-- Modale de déplacement de fichiers -->
<div id="move-files-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Déplacer des fichiers</h2>
                <p id="move-files-count" class="text-sm text-slate-600 dark:text-slate-400 mt-1">1 fichier sélectionné</p>
            </div>
            <button onclick="mediaLibrary.closeMoveFilesModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-hidden flex">
            <!-- Sidebar avec arborescence -->
            <div class="w-64 border-r border-slate-200 dark:border-slate-700 p-4 overflow-y-auto">
                <div id="move-files-tree" class="space-y-1">
                    <!-- Arborescence chargée dynamiquement -->
                </div>
            </div>
            <!-- Zone principale -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <nav id="move-files-breadcrumb" class="flex items-center gap-2 text-sm">
                        <!-- Breadcrumb chargé dynamiquement -->
                    </nav>
                </div>
                <div class="p-4 flex-1 overflow-y-auto">
                    <div class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        Sélectionnez le dossier de destination :
                    </div>
                    <div id="move-files-selected-folder" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-3 mb-4">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span id="move-files-folder-name">Racine</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
            <button 
                onclick="mediaLibrary.closeMoveFilesModal()"
                class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
            >
                Annuler
            </button>
            <button 
                onclick="mediaLibrary.confirmMoveFiles()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
            >
                Déplacer
            </button>
        </div>
    </div>
</div>

<!-- Menu contextuel -->
<div id="file-context-menu" class="hidden fixed bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl z-50 min-w-[180px] py-1">
    <button onclick="mediaLibrary.contextMenuAction('view')" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        Voir détails
    </button>
    <button onclick="mediaLibrary.contextMenuAction('rename')" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Renommer
    </button>
    <button onclick="mediaLibrary.contextMenuAction('move')" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        Déplacer
    </button>
    <button onclick="mediaLibrary.contextMenuAction('copy-url')" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        Copier l'URL
    </button>
    <hr class="my-1 border-slate-200 dark:border-slate-700">
    <button onclick="mediaLibrary.contextMenuAction('delete')" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        Supprimer
    </button>
</div>
@endsection

@push('styles')
@vite('resources/css/media-library.css')
@endpush

@push('scripts')
@vite('resources/js/media-library.js')
@endpush
