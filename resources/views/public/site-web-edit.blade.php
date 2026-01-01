<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Éditeur - {{ $entreprise->nom }} - Allo Tata</title>
    
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Oswald:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Source+Sans+Pro:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css'])
    
    {{-- SortableJS pour le drag & drop --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    
    @php
        $theme = $entreprise->site_web_theme;
        $blocks = $entreprise->getSiteWebBlocks();
    @endphp
    
    <style>
        /* Variables du thème du site - EXACTEMENT comme en public */
        :root {
            --site-primary: {{ $theme['colors']['primary'] ?? '#22c55e' }};
            --site-secondary: {{ $theme['colors']['secondary'] ?? '#f97316' }};
            --site-accent: {{ $theme['colors']['accent'] ?? '#3b82f6' }};
            --site-background: {{ $theme['colors']['background'] ?? '#ffffff' }};
            --site-text: {{ $theme['colors']['text'] ?? '#1e293b' }};
            --site-font-heading: '{{ $theme['fonts']['heading'] ?? 'Poppins' }}', sans-serif;
            --site-font-body: '{{ $theme['fonts']['body'] ?? 'Inter' }}', sans-serif;
            --site-button-radius: {{ ($theme['buttons']['style'] ?? 'rounded') === 'rounded' ? '0.5rem' : (($theme['buttons']['style'] ?? 'rounded') === 'pill' ? '9999px' : '0') }};
            --site-button-shadow: {{ ($theme['buttons']['shadow'] ?? true) ? '0 4px 6px -1px rgba(0, 0, 0, 0.1)' : 'none' }};
        }
        
        /* Le site utilise le thème personnalisé, PAS le dark mode de l'app */
        .site-preview {
            font-family: var(--site-font-body);
            background: var(--site-background);
            color: var(--site-text);
        }
        
        /* Barre d'outils éditeur (celle-ci reste en dark pour contraste) */
        .editor-toolbar {
            background: #1e293b;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        /* Sidebar éditeur */
        .editor-sidebar {
            position: fixed;
            top: 56px;
            right: 0;
            bottom: 0;
            width: 320px;
            background: #1e293b;
            color: white;
            overflow-y: auto;
            z-index: 90;
            transform: translateX(0);
            transition: transform 0.3s ease;
            box-shadow: -4px 0 20px rgba(0,0,0,0.2);
        }
        
        .editor-sidebar.hidden {
            transform: translateX(100%);
        }
        
        /* Zone de preview du site */
        .site-preview {
            margin-top: 56px;
            margin-right: 320px;
            min-height: calc(100vh - 56px);
            transition: margin-right 0.3s ease;
        }
        
        .site-preview.full-width {
            margin-right: 0;
        }
        
        /* Bloc éditable */
        .editable-block {
            position: relative;
            cursor: grab;
            transition: outline 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .editable-block:hover {
            outline: 3px dashed var(--site-primary);
            outline-offset: -3px;
        }
        
        .editable-block.selected {
            outline: 3px solid var(--site-primary);
            outline-offset: -3px;
        }
        
        /* États de drag & drop */
        .editable-block.sortable-ghost {
            opacity: 0.4;
            outline: 3px dashed var(--site-primary) !important;
        }
        
        .editable-block.sortable-chosen {
            cursor: grabbing;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            transform: scale(1.01);
            z-index: 100;
        }
        
        .editable-block.sortable-drag {
            opacity: 1;
        }
        
        /* Indicateur de position de drop */
        .drop-indicator {
            height: 4px;
            background: var(--site-primary);
            border-radius: 2px;
            margin: 8px 0;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Handle de drag visible */
        .drag-handle {
            position: absolute;
            left: 50%;
            top: -12px;
            transform: translateX(-50%);
            background: var(--site-primary);
            color: white;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.2s ease;
            cursor: grab;
            z-index: 51;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: system-ui, sans-serif;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .editable-block:hover .drag-handle {
            opacity: 1;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .drag-handle svg {
            width: 14px;
            height: 14px;
        }
        
        /* Barre d'outils du bloc */
        .block-toolbar {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 2px;
            background: #1e293b;
            border-radius: 0 0 0 8px;
            padding: 4px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 50;
        }
        
        .editable-block:hover .block-toolbar,
        .editable-block.selected .block-toolbar {
            opacity: 1;
        }
        
        .block-toolbar button {
            padding: 8px;
            color: #94a3b8;
            border-radius: 4px;
            transition: all 0.15s ease;
        }
        
        .block-toolbar button:hover {
            background: #334155;
            color: white;
        }
        
        .block-toolbar button.danger:hover {
            background: #dc2626;
            color: white;
        }
        
        /* Label du type de bloc */
        .block-type-label {
            position: absolute;
            top: 0;
            left: 0;
            background: var(--site-primary);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 0 0 8px 0;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 50;
            font-family: system-ui, sans-serif;
        }
        
        .editable-block:hover .block-type-label,
        .editable-block.selected .block-type-label {
            opacity: 1;
        }
        
        /* Sidebar tabs */
        .sidebar-tabs {
            display: flex;
            border-bottom: 1px solid #334155;
        }
        
        .sidebar-tab {
            flex: 1;
            padding: 12px 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            border-bottom: 2px solid transparent;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        
        .sidebar-tab:hover {
            color: white;
        }
        
        .sidebar-tab.active {
            color: #22c55e;
            border-bottom-color: #22c55e;
        }
        
        .sidebar-tab-content {
            display: none;
        }
        
        .sidebar-tab-content.active {
            display: block;
        }
        
        /* Grille des blocs disponibles */
        .blocks-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 16px;
        }
        
        .block-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 12px 8px;
            background: #334155;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: center;
        }
        
        .block-item:hover {
            background: #475569;
            transform: translateY(-2px);
        }
        
        .block-item svg {
            width: 24px;
            height: 24px;
            color: #94a3b8;
        }
        
        .block-item span {
            font-size: 11px;
            color: #cbd5e1;
        }
        
        /* Accordion */
        .accordion-section {
            border-bottom: 1px solid #334155;
        }
        
        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 500;
            color: white;
            transition: background 0.15s ease;
        }
        
        .accordion-header:hover {
            background: #334155;
        }
        
        .accordion-content {
            display: none;
            padding: 0 16px 16px;
        }
        
        .accordion-section.open .accordion-content {
            display: block;
        }
        
        .accordion-icon {
            transition: transform 0.2s ease;
        }
        
        .accordion-section.open .accordion-icon {
            transform: rotate(180deg);
        }
        
        /* Color picker */
        .color-picker-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .color-picker-row input[type="color"] {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #475569;
            cursor: pointer;
            padding: 0;
            overflow: hidden;
        }
        
        .color-picker-row input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        
        .color-picker-row input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 6px;
        }
        
        /* Preset themes */
        .preset-themes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        
        .preset-theme-btn {
            aspect-ratio: 1;
            border-radius: 8px;
            border: 2px solid #475569;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }
        
        .preset-theme-btn:hover {
            border-color: #22c55e;
            transform: scale(1.05);
        }
        
        .preset-theme-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--theme-primary) 50%, var(--theme-secondary) 50%);
        }
        
        /* Save status */
        .save-status {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .save-status.saved { background: #166534; color: #bbf7d0; }
        .save-status.saving { background: #854d0e; color: #fef08a; }
        .save-status.unsaved { background: #92400e; color: #fed7aa; }
        .save-status.error { background: #991b1b; color: #fecaca; cursor: pointer; }
        
        /* Zone vide */
        .empty-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            margin: 40px;
            background: rgba(0,0,0,0.02);
        }
        
        .empty-zone svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            color: #94a3b8;
        }
        
        /* Formulaires sidebar */
        .sidebar-input {
            width: 100%;
            padding: 8px 12px;
            background: #334155;
            border: 1px solid #475569;
            border-radius: 6px;
            color: white;
            font-size: 14px;
        }
        
        .sidebar-input:focus {
            outline: none;
            border-color: #22c55e;
        }
        
        .sidebar-select {
            width: 100%;
            padding: 8px 12px;
            background: #334155;
            border: 1px solid #475569;
            border-radius: 6px;
            color: white;
            font-size: 14px;
        }
        
        .sidebar-btn {
            width: 100%;
            padding: 10px 16px;
            background: #22c55e;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: background 0.15s ease;
        }
        
        .sidebar-btn:hover {
            background: #16a34a;
        }
        
        /* Version items */
        .version-item {
            padding: 12px;
            background: #334155;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .editor-sidebar {
                transform: translateX(100%);
            }
            
            .editor-sidebar.visible {
                transform: translateX(0);
            }
            
            .site-preview {
                margin-right: 0;
            }
        }
    </style>
</head>
<body class="bg-slate-100">
    {{-- Barre d'outils éditeur --}}
    <header class="editor-toolbar">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-slate-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden sm:inline text-sm">Retour</span>
            </a>
            
            <div class="h-6 w-px bg-slate-600"></div>
            
            <div class="flex items-center gap-3">
                @if(!empty($entreprise->logo))
                    <img src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" alt="{{ $entreprise->nom }}" class="w-8 h-8 rounded-lg object-cover">
                @endif
                <div>
                    <h1 class="font-semibold text-white text-sm">{{ $entreprise->nom }}</h1>
                    <p class="text-xs text-slate-400">Éditeur de site web</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Indicateur de sauvegarde --}}
            <div id="save-status" class="save-status saved">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Sauvegardé</span>
            </div>
            
            {{-- Bouton preview --}}
            @if(!empty($entreprise->slug_web))
                <a href="{{ route('site-web.show', ['slug' => $entreprise->slug_web, 'mode' => 'view']) }}" 
                   target="_blank"
                   class="hidden sm:flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Aperçu
                </a>
            @endif
            
            {{-- Toggle sidebar --}}
            <button type="button" onclick="toggleSidebar()" class="p-2 text-slate-400 hover:text-white transition lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <button type="button" onclick="toggleSidebar()" class="hidden lg:flex p-2 text-slate-400 hover:text-white transition" title="Masquer/Afficher la sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </header>
    
    {{-- Zone de preview du site (EXACTEMENT comme le site public) --}}
    <main id="site-preview" class="site-preview">
        {{-- Avertissements --}}
        @php
            $aSiteWebActif = $entreprise->aSiteWebActif();
            $estVerifiee = $entreprise->est_verifiee;
        @endphp
        
        @if(!$aSiteWebActif || !$estVerifiee)
            <div class="bg-yellow-50 border-b border-yellow-200 px-4 py-3">
                <div class="max-w-4xl mx-auto flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-yellow-800 text-sm">Votre site n'est pas encore accessible publiquement</p>
                        <ul class="text-xs text-yellow-700 mt-1">
                            @if(!$aSiteWebActif)<li>• L'abonnement "Site Web Vitrine" n'est pas actif</li>@endif
                            @if(!$estVerifiee)<li>• Votre entreprise n'est pas encore vérifiée</li>@endif
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Rendu des blocs --}}
        <div id="blocks-container">
            @if(count($blocks) > 0)
                @foreach($blocks as $index => $block)
                    <div class="editable-block" data-block-id="{{ $block['id'] }}" data-block-index="{{ $index }}" onclick="selectBlock('{{ $block['id'] }}', event)">
                        {{-- Handle de drag --}}
                        <div class="drag-handle" title="Maintenez et glissez pour déplacer">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            Déplacer
                        </div>
                        
                        {{-- Label du type --}}
                        <div class="block-type-label">
                            {{ ucfirst($block['type']) }}
                        </div>
                        
                        {{-- Barre d'outils du bloc --}}
                        <div class="block-toolbar">
                            <button type="button" onclick="moveBlock('{{ $block['id'] }}', -1); event.stopPropagation();" title="Monter">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="moveBlock('{{ $block['id'] }}', 1); event.stopPropagation();" title="Descendre">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="duplicateBlock('{{ $block['id'] }}'); event.stopPropagation();" title="Dupliquer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="deleteBlock('{{ $block['id'] }}'); event.stopPropagation();" title="Supprimer" class="danger">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        
                        {{-- Contenu du bloc (MÊME RENDU QUE LE SITE PUBLIC) --}}
                        @switch($block['type'])
                            @case('hero')
                                <x-site-web.blocks.hero :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('text')
                                <x-site-web.blocks.text :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('image')
                                <x-site-web.blocks.image :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('gallery')
                                <x-site-web.blocks.gallery :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('contact')
                                <x-site-web.blocks.contact :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('video')
                                <x-site-web.blocks.video :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('services')
                                <x-site-web.blocks.services :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('testimonials')
                                <x-site-web.blocks.testimonials :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('cta')
                                <x-site-web.blocks.cta :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('divider')
                                <x-site-web.blocks.divider :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('iframe')
                                <x-site-web.blocks.iframe :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('faq')
                                <x-site-web.blocks.faq :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('team')
                                <x-site-web.blocks.team :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('stats')
                                <x-site-web.blocks.stats :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                            @case('features')
                                <x-site-web.blocks.features :block="$block" :entreprise="$entreprise" :editMode="true" />
                                @break
                        @endswitch
                    </div>
                @endforeach
            @endif
            
            @if(count($blocks) === 0)
                {{-- Zone vide --}}
                <div class="empty-zone" id="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <p class="text-xl font-semibold text-slate-700 mb-2">Commencez à créer votre site</p>
                    <p class="text-slate-500">Cliquez sur un bloc dans la barre latérale pour l'ajouter</p>
                </div>
            @else
                {{-- Zone pour ajouter un bloc à la fin --}}
                <div id="add-block-zone" onclick="openBlocksTab()" class="mx-8 my-8 py-8 border-2 border-dashed border-slate-300 rounded-xl text-center cursor-pointer hover:border-green-500 hover:bg-green-50/50 transition group">
                    <div class="flex items-center justify-center gap-2 text-slate-400 group-hover:text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span class="font-medium">Ajouter un bloc</span>
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Footer (comme le site public) --}}
        <footer class="py-8 px-4 text-center border-t" style="border-color: rgba(0,0,0,0.1);">
            <p class="text-sm" style="color: var(--site-text); opacity: 0.5;">
                © {{ date('Y') }} {{ $entreprise->nom }}. Tous droits réservés.
            </p>
            <p class="text-xs mt-2" style="color: var(--site-text); opacity: 0.3;">
                Propulsé par <a href="{{ route('home') }}" style="color: var(--site-primary);">Allo Tata</a>
            </p>
        </footer>
    </main>
    
    {{-- Sidebar --}}
    <aside id="editor-sidebar" class="editor-sidebar">
        {{-- Tabs --}}
        <div class="sidebar-tabs">
            <button type="button" class="sidebar-tab active" data-tab="blocks">Blocs</button>
            <button type="button" class="sidebar-tab" data-tab="properties">Propriétés</button>
            <button type="button" class="sidebar-tab" data-tab="theme">Thème</button>
            <button type="button" class="sidebar-tab" data-tab="settings">Réglages</button>
        </div>
        
        {{-- Tab: Blocs --}}
        <div id="tab-blocks" class="sidebar-tab-content active">
            <div class="p-4 border-b border-slate-700">
                <h3 class="font-semibold text-white text-sm">Ajouter un bloc</h3>
                <p class="text-xs text-slate-400 mt-1">Cliquez pour ajouter à la fin</p>
            </div>
            
            <div class="blocks-grid">
                <div class="block-item" onclick="addBlock('hero')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"></path></svg>
                    <span>Hero</span>
                </div>
                <div class="block-item" onclick="addBlock('text')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    <span>Texte</span>
                </div>
                <div class="block-item" onclick="addBlock('image')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Image</span>
                </div>
                <div class="block-item" onclick="addBlock('gallery')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path></svg>
                    <span>Galerie</span>
                </div>
                <div class="block-item" onclick="addBlock('services')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2"></path></svg>
                    <span>Services</span>
                </div>
                <div class="block-item" onclick="addBlock('testimonials')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span>Avis</span>
                </div>
                <div class="block-item" onclick="addBlock('contact')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span>Contact</span>
                </div>
                <div class="block-item" onclick="addBlock('cta')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"></path></svg>
                    <span>CTA</span>
                </div>
                <div class="block-item" onclick="addBlock('video')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path></svg>
                    <span>Vidéo</span>
                </div>
                <div class="block-item" onclick="addBlock('stats')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Stats</span>
                </div>
                <div class="block-item" onclick="addBlock('features')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138"></path></svg>
                    <span>Features</span>
                </div>
                <div class="block-item" onclick="addBlock('faq')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>FAQ</span>
                </div>
                <div class="block-item" onclick="addBlock('team')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"></path></svg>
                    <span>Équipe</span>
                </div>
                <div class="block-item" onclick="addBlock('divider')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16"></path></svg>
                    <span>Séparateur</span>
                </div>
                <div class="block-item" onclick="addBlock('iframe')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    <span>Iframe</span>
                </div>
            </div>
        </div>
        
        {{-- Tab: Propriétés --}}
        <div id="tab-properties" class="sidebar-tab-content">
            <div id="block-properties" class="p-4">
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"></path>
                    </svg>
                    <p class="text-sm">Cliquez sur un bloc pour modifier ses propriétés</p>
                </div>
            </div>
        </div>
        
        {{-- Tab: Thème --}}
        <div id="tab-theme" class="sidebar-tab-content">
            {{-- Thèmes prédéfinis --}}
            <div class="accordion-section open">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Thèmes prédéfinis</span>
                    <svg class="accordion-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <div class="accordion-content">
                    <div class="preset-themes">
                        <button type="button" onclick="applyPresetTheme('moderne')" class="preset-theme-btn" style="--theme-primary: #22c55e; --theme-secondary: #f97316;" title="Moderne"></button>
                        <button type="button" onclick="applyPresetTheme('classique')" class="preset-theme-btn" style="--theme-primary: #1e40af; --theme-secondary: #b45309;" title="Classique"></button>
                        <button type="button" onclick="applyPresetTheme('bold')" class="preset-theme-btn" style="--theme-primary: #dc2626; --theme-secondary: #facc15;" title="Bold"></button>
                        <button type="button" onclick="applyPresetTheme('nature')" class="preset-theme-btn" style="--theme-primary: #15803d; --theme-secondary: #a16207;" title="Nature"></button>
                        <button type="button" onclick="applyPresetTheme('tech')" class="preset-theme-btn" style="--theme-primary: #7c3aed; --theme-secondary: #06b6d4;" title="Tech"></button>
                        <button type="button" onclick="applyPresetTheme('minimaliste')" class="preset-theme-btn" style="--theme-primary: #171717; --theme-secondary: #737373;" title="Minimaliste"></button>
                    </div>
                </div>
            </div>
            
            {{-- Couleurs --}}
            <div class="accordion-section open">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Couleurs</span>
                    <svg class="accordion-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <div class="accordion-content">
                    <div class="color-picker-row">
                        <input type="color" id="color-primary" value="{{ $theme['colors']['primary'] ?? '#22c55e' }}" onchange="updateThemeColor('primary', this.value)">
                        <div><label class="text-sm font-medium">Principale</label><p class="text-xs text-slate-400">Boutons, liens</p></div>
                    </div>
                    <div class="color-picker-row">
                        <input type="color" id="color-secondary" value="{{ $theme['colors']['secondary'] ?? '#f97316' }}" onchange="updateThemeColor('secondary', this.value)">
                        <div><label class="text-sm font-medium">Secondaire</label><p class="text-xs text-slate-400">Accents, dégradés</p></div>
                    </div>
                    <div class="color-picker-row">
                        <input type="color" id="color-background" value="{{ $theme['colors']['background'] ?? '#ffffff' }}" onchange="updateThemeColor('background', this.value)">
                        <div><label class="text-sm font-medium">Fond</label><p class="text-xs text-slate-400">Arrière-plan</p></div>
                    </div>
                    <div class="color-picker-row">
                        <input type="color" id="color-text" value="{{ $theme['colors']['text'] ?? '#1e293b' }}" onchange="updateThemeColor('text', this.value)">
                        <div><label class="text-sm font-medium">Texte</label><p class="text-xs text-slate-400">Titres, paragraphes</p></div>
                    </div>
                </div>
            </div>
            
            {{-- Polices --}}
            <div class="accordion-section">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Polices</span>
                    <svg class="accordion-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <div class="accordion-content">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Titres</label>
                        <select id="font-heading" class="sidebar-select" onchange="updateThemeFont('heading', this.value)">
                            @foreach(['Poppins', 'Inter', 'Playfair Display', 'Oswald', 'Merriweather', 'Space Grotesk', 'DM Sans'] as $font)
                                <option value="{{ $font }}" {{ ($theme['fonts']['heading'] ?? 'Poppins') === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Corps de texte</label>
                        <select id="font-body" class="sidebar-select" onchange="updateThemeFont('body', this.value)">
                            @foreach(['Inter', 'Roboto', 'Lora', 'Source Sans Pro', 'IBM Plex Sans', 'DM Sans'] as $font)
                                <option value="{{ $font }}" {{ ($theme['fonts']['body'] ?? 'Inter') === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Tab: Réglages --}}
        <div id="tab-settings" class="sidebar-tab-content">
            <form action="{{ route('site-web.update', $entreprise->slug_web ?? $entreprise->slug) }}" method="POST" class="p-4">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">URL du site</label>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 text-sm">/w/</span>
                        <input type="text" name="slug_web" value="{{ $entreprise->slug_web ?? $entreprise->slug }}" pattern="[a-z0-9-]+" required class="sidebar-input flex-1">
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Lettres minuscules, chiffres et tirets</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Phrase d'accroche</label>
                    <input type="text" name="phrase_accroche" value="{{ $entreprise->phrase_accroche }}" maxlength="500" class="sidebar-input" placeholder="Votre slogan...">
                </div>
                
                <button type="submit" class="sidebar-btn">Enregistrer</button>
            </form>
            
            {{-- Versions --}}
            <div class="border-t border-slate-700 mt-4 pt-4 px-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium">Historique</h4>
                    <button type="button" onclick="loadVersions()" class="text-xs text-green-400 hover:text-green-300">Actualiser</button>
                </div>
                <div id="versions-list" class="max-h-64 overflow-y-auto">
                    <p class="text-xs text-slate-400 text-center py-4">Cliquez sur "Actualiser" pour voir les versions</p>
                </div>
            </div>
        </div>
    </aside>
    
    <script>
        // État global
        const editorState = {
            slug: '{{ $entreprise->slug_web ?? $entreprise->slug }}',
            csrf: '{{ csrf_token() }}',
            content: @json($entreprise->site_web_content),
            selectedBlockId: null,
            hasUnsavedChanges: false,
            isSaving: false,
            saveTimeout: null
        };
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Tabs
            document.querySelectorAll('.sidebar-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.sidebar-tab-content').forEach(c => c.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
                });
            });
            
            // Initialiser le drag & drop avec SortableJS
            initSortable();
        });
        
        // Initialiser SortableJS
        function initSortable() {
            const container = document.getElementById('blocks-container');
            if (!container || container.querySelectorAll('.editable-block').length === 0) return;
            
            new Sortable(container, {
                animation: 200,
                // Pas de handle = on peut drag depuis n'importe où sur le bloc
                // Mais on filtre les éléments interactifs
                filter: '.block-toolbar, a, button, input, textarea, select, iframe',
                preventOnFilter: false,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                forceFallback: true, // Meilleur rendu cross-browser
                fallbackClass: 'sortable-fallback',
                fallbackOnBody: true,
                swapThreshold: 0.65,
                delay: 150, // Petit délai pour distinguer clic et drag
                delayOnTouchOnly: true,
                
                onStart: function(evt) {
                    // Désélectionner le bloc actuel pendant le drag
                    document.querySelectorAll('.editable-block.selected').forEach(el => el.classList.remove('selected'));
                },
                
                onEnd: function(evt) {
                    if (evt.oldIndex === evt.newIndex) return;
                    
                    // Mettre à jour l'ordre dans l'état
                    const [movedBlock] = editorState.content.blocks.splice(evt.oldIndex, 1);
                    editorState.content.blocks.splice(evt.newIndex, 0, movedBlock);
                    
                    // Mettre à jour les attributs data-block-index
                    container.querySelectorAll('.editable-block').forEach((el, index) => {
                        el.setAttribute('data-block-index', index);
                    });
                    
                    // Sauvegarder
                    scheduleAutoSave();
                    
                    // Sélectionner le bloc déplacé
                    selectBlock(movedBlock.id);
                    
                    // Feedback visuel
                    showToast('Bloc déplacé');
                }
            });
        }
        
        // Toast notification
        function showToast(message) {
            // Créer le toast s'il n'existe pas
            let toast = document.getElementById('toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast';
                toast.style.cssText = `
                    position: fixed;
                    bottom: 24px;
                    left: 50%;
                    transform: translateX(-50%) translateY(100px);
                    background: #1e293b;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 500;
                    z-index: 1000;
                    transition: transform 0.3s ease;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                `;
                document.body.appendChild(toast);
            }
            
            toast.textContent = message;
            toast.style.transform = 'translateX(-50%) translateY(0)';
            
            setTimeout(() => {
                toast.style.transform = 'translateX(-50%) translateY(100px)';
            }, 2000);
        }
        
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('editor-sidebar');
            const preview = document.getElementById('site-preview');
            sidebar.classList.toggle('hidden');
            preview.classList.toggle('full-width');
        }
        
        // Ouvrir l'onglet des blocs
        function openBlocksTab() {
            // S'assurer que la sidebar est visible
            const sidebar = document.getElementById('editor-sidebar');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                document.getElementById('site-preview').classList.remove('full-width');
            }
            
            // Ouvrir l'onglet blocs
            document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.sidebar-tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector('[data-tab="blocks"]').classList.add('active');
            document.getElementById('tab-blocks').classList.add('active');
        }
        
        // Toggle accordion
        function toggleAccordion(header) {
            header.closest('.accordion-section').classList.toggle('open');
        }
        
        // Sélectionner un bloc
        function selectBlock(blockId, event) {
            if (event) {
                event.stopPropagation();
                
                // Ne pas sélectionner si on clique sur le drag handle ou la toolbar
                if (event.target.closest('.drag-handle') || event.target.closest('.block-toolbar')) {
                    return;
                }
            }
            
            // Désélectionner l'ancien
            document.querySelectorAll('.editable-block.selected').forEach(el => el.classList.remove('selected'));
            
            // Sélectionner le nouveau
            const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
            if (blockEl) {
                blockEl.classList.add('selected');
                editorState.selectedBlockId = blockId;
                
                // Ouvrir l'onglet propriétés
                document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.sidebar-tab-content').forEach(c => c.classList.remove('active'));
                document.querySelector('[data-tab="properties"]').classList.add('active');
                document.getElementById('tab-properties').classList.add('active');
                
                // Afficher les propriétés du bloc
                showBlockProperties(blockId);
            }
        }
        
        // Afficher les propriétés d'un bloc
        function showBlockProperties(blockId) {
            const block = editorState.content.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            const panel = document.getElementById('block-properties');
            const typeNames = {
                hero: 'En-tête Hero', text: 'Texte', image: 'Image', gallery: 'Galerie',
                contact: 'Contact', video: 'Vidéo', services: 'Services', testimonials: 'Témoignages',
                cta: 'Appel à l\'action', divider: 'Séparateur', iframe: 'Iframe', faq: 'FAQ',
                team: 'Équipe', stats: 'Statistiques', features: 'Fonctionnalités'
            };
            
            let html = `
                <div class="border-b border-slate-700 pb-4 mb-4">
                    <h3 class="font-semibold text-white">${typeNames[block.type] || block.type}</h3>
                    <p class="text-xs text-slate-400 mt-1">ID: ${block.id}</p>
                </div>
            `;
            
            // Champs selon le type
            html += generateBlockFields(block);
            
            panel.innerHTML = html;
        }
        
        // Générer les champs d'édition
        function generateBlockFields(block) {
            let html = '';
            const c = block.content || {};
            const s = block.settings || {};
            
            switch (block.type) {
                case 'hero':
                    html = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Titre</label>
                            <input type="text" value="${escapeHtml(c.title || '')}" onchange="updateBlockContent('${block.id}', 'title', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Sous-titre</label>
                            <input type="text" value="${escapeHtml(c.subtitle || '')}" onchange="updateBlockContent('${block.id}', 'subtitle', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Texte du bouton</label>
                            <input type="text" value="${escapeHtml(c.buttonText || '')}" onchange="updateBlockContent('${block.id}', 'buttonText', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Lien du bouton</label>
                            <input type="text" value="${escapeHtml(c.buttonLink || '')}" onchange="updateBlockContent('${block.id}', 'buttonLink', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Hauteur</label>
                            <select onchange="updateBlockSetting('${block.id}', 'height', this.value)" class="sidebar-select">
                                <option value="small" ${s.height === 'small' ? 'selected' : ''}>Petite</option>
                                <option value="medium" ${s.height === 'medium' ? 'selected' : ''}>Moyenne</option>
                                <option value="large" ${s.height === 'large' ? 'selected' : ''}>Grande</option>
                                <option value="full" ${s.height === 'full' ? 'selected' : ''}>Plein écran</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'text':
                    html = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Contenu HTML</label>
                            <textarea rows="6" onchange="updateBlockContent('${block.id}', 'html', this.value)" class="sidebar-input">${escapeHtml(c.html || '')}</textarea>
                            <p class="text-xs text-slate-400 mt-1">HTML basique accepté</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Alignement</label>
                            <select onchange="updateBlockSetting('${block.id}', 'alignment', this.value)" class="sidebar-select">
                                <option value="left" ${s.alignment === 'left' ? 'selected' : ''}>Gauche</option>
                                <option value="center" ${s.alignment === 'center' ? 'selected' : ''}>Centre</option>
                                <option value="right" ${s.alignment === 'right' ? 'selected' : ''}>Droite</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'cta':
                    html = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Titre</label>
                            <input type="text" value="${escapeHtml(c.title || '')}" onchange="updateBlockContent('${block.id}', 'title', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Sous-titre</label>
                            <input type="text" value="${escapeHtml(c.subtitle || '')}" onchange="updateBlockContent('${block.id}', 'subtitle', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Texte du bouton</label>
                            <input type="text" value="${escapeHtml(c.buttonText || '')}" onchange="updateBlockContent('${block.id}', 'buttonText', this.value)" class="sidebar-input">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Style</label>
                            <select onchange="updateBlockSetting('${block.id}', 'style', this.value)" class="sidebar-select">
                                <option value="gradient" ${s.style === 'gradient' ? 'selected' : ''}>Dégradé</option>
                                <option value="simple" ${s.style === 'simple' ? 'selected' : ''}>Simple</option>
                                <option value="outlined" ${s.style === 'outlined' ? 'selected' : ''}>Contour</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'video':
                    html = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">URL de la vidéo</label>
                            <input type="url" value="${escapeHtml(c.url || '')}" onchange="updateBlockContent('${block.id}', 'url', this.value)" class="sidebar-input" placeholder="https://youtube.com/watch?v=...">
                            <p class="text-xs text-slate-400 mt-1">YouTube ou Vimeo</p>
                        </div>
                    `;
                    break;
                    
                case 'contact':
                    html = `
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Titre</label>
                            <input type="text" value="${escapeHtml(c.title || '')}" onchange="updateBlockContent('${block.id}', 'title', this.value)" class="sidebar-input">
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" ${c.showEmail ? 'checked' : ''} onchange="updateBlockContent('${block.id}', 'showEmail', this.checked)" class="rounded bg-slate-700 border-slate-600">
                                <span class="text-sm">Afficher l'email</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" ${c.showPhone ? 'checked' : ''} onchange="updateBlockContent('${block.id}', 'showPhone', this.checked)" class="rounded bg-slate-700 border-slate-600">
                                <span class="text-sm">Afficher le téléphone</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" ${c.showAddress ? 'checked' : ''} onchange="updateBlockContent('${block.id}', 'showAddress', this.checked)" class="rounded bg-slate-700 border-slate-600">
                                <span class="text-sm">Afficher l'adresse</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" ${c.showMap ? 'checked' : ''} onchange="updateBlockContent('${block.id}', 'showMap', this.checked)" class="rounded bg-slate-700 border-slate-600">
                                <span class="text-sm">Afficher la carte</span>
                            </label>
                        </div>
                    `;
                    break;
                    
                default:
                    html = `<p class="text-sm text-slate-400">Modifiez ce bloc directement sur le site ou supprimez-le pour en ajouter un autre.</p>`;
            }
            
            return html;
        }
        
        // Échapper HTML
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        
        // Ajouter un bloc
        function addBlock(type) {
            const newBlock = {
                id: 'block-' + Math.random().toString(36).substring(2, 9) + Date.now().toString(36),
                type: type,
                content: getDefaultContent(type),
                settings: getDefaultSettings(type),
                animation: 'fadeIn'
            };
            
            editorState.content.blocks.push(newBlock);
            scheduleAutoSave();
            
            // Recharger la page pour voir le nouveau bloc
            location.reload();
        }
        
        // Contenu par défaut
        function getDefaultContent(type) {
            const defaults = {
                hero: { title: 'Bienvenue', subtitle: 'Découvrez nos services', buttonText: 'En savoir plus', buttonLink: '#contact', overlay: true },
                text: { html: '<p>Votre texte ici...</p>' },
                image: { src: null, alt: 'Image', caption: '' },
                gallery: { images: [], columns: 3 },
                contact: { title: 'Contactez-nous', showEmail: true, showPhone: true, showAddress: true, showMap: false },
                video: { url: '', type: 'youtube' },
                services: { title: 'Nos Services', items: [] },
                testimonials: { title: 'Ce que disent nos clients', items: [] },
                cta: { title: 'Prêt à commencer ?', subtitle: 'Contactez-nous', buttonText: 'Contact', buttonLink: '#contact' },
                divider: { style: 'line' },
                iframe: { src: '', height: 400 },
                faq: { title: 'Questions fréquentes', items: [] },
                team: { title: 'Notre équipe', members: [] },
                stats: { items: [{ value: '100+', label: 'Clients' }] },
                features: { title: 'Pourquoi nous choisir ?', items: [] }
            };
            return defaults[type] || {};
        }
        
        // Settings par défaut
        function getDefaultSettings(type) {
            const defaults = {
                hero: { height: 'large', alignment: 'center' },
                text: { alignment: 'center' },
                cta: { style: 'gradient' },
                gallery: { gap: 'medium', rounded: true }
            };
            return defaults[type] || {};
        }
        
        // Supprimer un bloc
        function deleteBlock(blockId) {
            if (!confirm('Supprimer ce bloc ?')) return;
            
            const index = editorState.content.blocks.findIndex(b => b.id === blockId);
            if (index !== -1) {
                editorState.content.blocks.splice(index, 1);
                scheduleAutoSave();
                location.reload();
            }
        }
        
        // Dupliquer un bloc
        function duplicateBlock(blockId) {
            const index = editorState.content.blocks.findIndex(b => b.id === blockId);
            if (index === -1) return;
            
            const newBlock = JSON.parse(JSON.stringify(editorState.content.blocks[index]));
            newBlock.id = 'block-' + Math.random().toString(36).substring(2, 9) + Date.now().toString(36);
            
            editorState.content.blocks.splice(index + 1, 0, newBlock);
            scheduleAutoSave();
            location.reload();
        }
        
        // Déplacer un bloc
        function moveBlock(blockId, direction) {
            const index = editorState.content.blocks.findIndex(b => b.id === blockId);
            if (index === -1) return;
            
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= editorState.content.blocks.length) return;
            
            const [block] = editorState.content.blocks.splice(index, 1);
            editorState.content.blocks.splice(newIndex, 0, block);
            scheduleAutoSave();
            location.reload();
        }
        
        // Mettre à jour le contenu d'un bloc
        function updateBlockContent(blockId, field, value) {
            const block = editorState.content.blocks.find(b => b.id === blockId);
            if (block) {
                block.content[field] = value;
                scheduleAutoSave();
            }
        }
        
        // Mettre à jour un paramètre
        function updateBlockSetting(blockId, field, value) {
            const block = editorState.content.blocks.find(b => b.id === blockId);
            if (block) {
                block.settings[field] = value;
                scheduleAutoSave();
            }
        }
        
        // Mettre à jour une couleur du thème
        function updateThemeColor(key, value) {
            editorState.content.theme.colors[key] = value;
            document.documentElement.style.setProperty('--site-' + key, value);
            scheduleAutoSave();
        }
        
        // Mettre à jour une police
        function updateThemeFont(key, value) {
            editorState.content.theme.fonts[key] = value;
            document.documentElement.style.setProperty('--site-font-' + key, "'" + value + "', sans-serif");
            scheduleAutoSave();
        }
        
        // Appliquer un thème prédéfini
        function applyPresetTheme(name) {
            const presets = {
                moderne: { colors: { primary: '#22c55e', secondary: '#f97316', background: '#ffffff', text: '#1e293b' }, fonts: { heading: 'Poppins', body: 'Inter' } },
                classique: { colors: { primary: '#1e40af', secondary: '#b45309', background: '#fafaf9', text: '#292524' }, fonts: { heading: 'Playfair Display', body: 'Lora' } },
                bold: { colors: { primary: '#dc2626', secondary: '#facc15', background: '#ffffff', text: '#000000' }, fonts: { heading: 'Oswald', body: 'Roboto' } },
                nature: { colors: { primary: '#15803d', secondary: '#a16207', background: '#f0fdf4', text: '#14532d' }, fonts: { heading: 'Merriweather', body: 'Source Sans Pro' } },
                tech: { colors: { primary: '#7c3aed', secondary: '#06b6d4', background: '#ffffff', text: '#0f172a' }, fonts: { heading: 'Space Grotesk', body: 'IBM Plex Sans' } },
                minimaliste: { colors: { primary: '#171717', secondary: '#737373', background: '#ffffff', text: '#171717' }, fonts: { heading: 'DM Sans', body: 'DM Sans' } }
            };
            
            const preset = presets[name];
            if (!preset) return;
            
            editorState.content.theme = { ...editorState.content.theme, ...preset };
            
            // Appliquer les couleurs
            Object.entries(preset.colors).forEach(([k, v]) => {
                document.documentElement.style.setProperty('--site-' + k, v);
                const input = document.getElementById('color-' + k);
                if (input) input.value = v;
            });
            
            // Appliquer les polices
            Object.entries(preset.fonts).forEach(([k, v]) => {
                document.documentElement.style.setProperty('--site-font-' + k, "'" + v + "', sans-serif");
                const select = document.getElementById('font-' + k);
                if (select) select.value = v;
            });
            
            scheduleAutoSave();
        }
        
        // Planifier une sauvegarde automatique
        function scheduleAutoSave() {
            editorState.hasUnsavedChanges = true;
            updateSaveStatus('unsaved');
            
            clearTimeout(editorState.saveTimeout);
            editorState.saveTimeout = setTimeout(() => saveContent(true), 2000);
        }
        
        // Sauvegarder le contenu
        async function saveContent(isAutoSave = true) {
            if (editorState.isSaving) return;
            
            editorState.isSaving = true;
            updateSaveStatus('saving');
            
            try {
                const response = await fetch(`/w/${editorState.slug}/content`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': editorState.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: editorState.content,
                        is_auto_save: isAutoSave
                    })
                });
                
                if (!response.ok) throw new Error('Erreur');
                
                editorState.hasUnsavedChanges = false;
                updateSaveStatus('saved');
            } catch (error) {
                console.error('Erreur:', error);
                updateSaveStatus('error');
            } finally {
                editorState.isSaving = false;
            }
        }
        
        // Mettre à jour le statut
        function updateSaveStatus(status) {
            const el = document.getElementById('save-status');
            const states = {
                saved: { class: 'saved', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>', text: 'Sauvegardé' },
                saving: { class: 'saving', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>', text: 'Sauvegarde...' },
                unsaved: { class: 'unsaved', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"></path>', text: 'Non sauvegardé' },
                error: { class: 'error', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"></path>', text: 'Erreur - Réessayer' }
            };
            
            const s = states[status];
            el.className = 'save-status ' + s.class;
            el.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">${s.icon}</svg><span>${s.text}</span>`;
            
            if (status === 'error') {
                el.onclick = () => saveContent(false);
            } else {
                el.onclick = null;
            }
        }
        
        // Charger les versions
        async function loadVersions() {
            const list = document.getElementById('versions-list');
            list.innerHTML = '<p class="text-center py-4"><svg class="w-5 h-5 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9"></path></svg></p>';
            
            try {
                const response = await fetch(`/w/${editorState.slug}/versions`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': editorState.csrf }
                });
                
                if (!response.ok) throw new Error('Erreur');
                
                const data = await response.json();
                
                if (data.versions.length === 0) {
                    list.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">Aucune version</p>';
                    return;
                }
                
                list.innerHTML = data.versions.map(v => `
                    <div class="version-item">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium">v${v.version_number}</span>
                            <span class="text-xs px-2 py-0.5 rounded ${v.is_auto_save ? 'bg-blue-900/50 text-blue-300' : 'bg-green-900/50 text-green-300'}">${v.is_auto_save ? 'Auto' : 'Manuel'}</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-2">${v.created_at_human}</p>
                        <button type="button" onclick="restoreVersion(${v.id})" class="text-xs text-green-400 hover:text-green-300">Restaurer</button>
                    </div>
                `).join('');
            } catch (error) {
                list.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Erreur de chargement</p>';
            }
        }
        
        // Restaurer une version
        async function restoreVersion(versionId) {
            if (!confirm('Restaurer cette version ?')) return;
            
            try {
                const response = await fetch(`/w/${editorState.slug}/restore/${versionId}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': editorState.csrf }
                });
                
                if (!response.ok) throw new Error('Erreur');
                
                alert('Version restaurée !');
                location.reload();
            } catch (error) {
                alert('Erreur lors de la restauration');
            }
        }
        
        // Prévenir la fermeture si modifications non sauvegardées
        window.addEventListener('beforeunload', (e) => {
            if (editorState.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
