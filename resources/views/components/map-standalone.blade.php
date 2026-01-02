{{-- 
    Composant Carte Interactive (Version Standalone)
    Fonctionne partout, ne nécessite pas de layout avec @stack
    
    Usage:
    @include('components.map-standalone', [
        'entreprises' => $entreprises,    // Collection d'entreprises avec lat/lng
        'center' => ['lat' => 46.2, 'lng' => 2.2],  // Centre de la carte
        'zoom' => 6,                       // Niveau de zoom
        'height' => '400px',               // Hauteur
        'single' => false,                 // Mode simple entreprise
        'enableClustering' => true,        // Activer le regroupement
    ])
--}}

@php
    $mapId = 'map-' . uniqid();
    $entreprises = $entreprises ?? collect([]);
    $center = $center ?? ['lat' => 46.603354, 'lng' => 1.888334];
    $zoom = $zoom ?? 6;
    $height = $height ?? '400px';
    $single = $single ?? false;
    $enableClustering = $enableClustering ?? true;
    $class = $class ?? '';
    
    // Préparer les données des marqueurs
    $markers = $entreprises->filter(function($e) {
        return $e->latitude && $e->longitude;
    })->map(function($e) {
        return [
            'id' => $e->id,
            'lat' => (float) $e->latitude,
            'lng' => (float) $e->longitude,
            'nom' => $e->nom,
            'type' => $e->type_activite ?? '',
            'adresse' => $e->formatted_address ?? $e->ville ?? '',
            'slug' => $e->slug,
            'logo' => $e->logo ? asset('media/' . $e->logo) : null,
            'note' => $e->avis && $e->avis->count() > 0 ? round($e->avis->avg('note'), 1) : null,
            'nombreAvis' => $e->avis ? $e->avis->count() : 0,
        ];
    })->values();
    
    // Ne pas afficher si pas de marqueurs valides
    $hasValidMarkers = $markers->count() > 0;
@endphp

@if($hasValidMarkers)
<div class="allo-map-wrapper">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    
    <style>
        /* Style de la carte Allo Tata */
        .allo-tata-map {
            z-index: 1;
        }
        
        .allo-tata-map .leaflet-container {
            background: #f1f5f9;
            font-family: inherit;
        }
        
        .dark .allo-tata-map .leaflet-container {
            background: #1e293b;
        }
        
        .allo-tata-map .leaflet-control-zoom {
            border: none !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        
        .allo-tata-map .leaflet-control-zoom a {
            background: white !important;
            color: #334155 !important;
            border: none !important;
            width: 32px !important;
            height: 32px !important;
            line-height: 32px !important;
        }
        
        .dark .allo-tata-map .leaflet-control-zoom a {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }
        
        .allo-tata-map .leaflet-control-zoom a:hover {
            background: #f1f5f9 !important;
        }
        
        .dark .allo-tata-map .leaflet-control-zoom a:hover {
            background: #334155 !important;
        }
        
        .allo-tata-map .leaflet-control-attribution {
            background: rgba(255, 255, 255, 0.8) !important;
            font-size: 10px;
        }
        
        .dark .allo-tata-map .leaflet-control-attribution {
            background: rgba(30, 41, 59, 0.8) !important;
            color: #94a3b8;
        }
        
        .allo-marker {
            background: linear-gradient(135deg, #22c55e 0%, #f97316 100%);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid white;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.2);
        }
        
        .allo-marker-inner {
            transform: rotate(45deg);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: white;
            font-weight: bold;
        }
        
        .allo-tata-map .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1);
            padding: 0;
            overflow: hidden;
        }
        
        .dark .allo-tata-map .leaflet-popup-content-wrapper {
            background: #1e293b;
            color: #e2e8f0;
        }
        
        .allo-tata-map .leaflet-popup-content {
            margin: 0;
            min-width: 200px;
        }
        
        .allo-tata-map .leaflet-popup-tip {
            background: white;
        }
        
        .dark .allo-tata-map .leaflet-popup-tip {
            background: #1e293b;
        }
        
        .allo-popup {
            padding: 12px;
        }
        
        .allo-popup-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .allo-popup-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }
        
        .dark .allo-popup-logo {
            border-color: #475569;
        }
        
        .allo-popup-title {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
            margin: 0;
            line-height: 1.3;
        }
        
        .dark .allo-popup-title {
            color: #f1f5f9;
        }
        
        .allo-popup-type {
            font-size: 12px;
            color: #22c55e;
            margin: 0;
        }
        
        .allo-popup-address {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .allo-popup-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .allo-popup-rating .star {
            color: #fbbf24;
        }
        
        .allo-popup-link {
            display: block;
            text-align: center;
            padding: 8px 12px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .allo-popup-link:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }
        
        .dark .allo-tata-map .leaflet-tile-pane {
            filter: brightness(0.7) contrast(1.1) saturate(0.8);
        }
    </style>

    <div 
        id="{{ $mapId }}" 
        class="allo-tata-map rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 {{ $class }}"
        style="height: {{ $height }}; min-height: 250px;"
    >
        <div class="flex items-center justify-center h-full bg-slate-100 dark:bg-slate-800">
            <div class="animate-pulse text-slate-500 dark:text-slate-400">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-sm">Chargement...</span>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script>
    (function() {
        const containerId = '{{ $mapId }}';
        const markers = @json($markers);
        const center = @json($center);
        const zoom = {{ $zoom }};
        const enableClustering = {{ $enableClustering ? 'true' : 'false' }};
        const isSingle = {{ $single ? 'true' : 'false' }};
        
        function initMap() {
            const container = document.getElementById(containerId);
            if (!container || !window.L) return;
            
            container.innerHTML = '';
            
            const map = L.map(container, {
                center: [center.lat, center.lng],
                zoom: zoom,
                minZoom: 4,
                maxZoom: 18,
                zoomControl: true,
                scrollWheelZoom: true
            });
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
                maxZoom: 19
            }).addTo(map);
            
            function createCustomIcon(marker) {
                const hasLogo = marker.logo;
                return L.divIcon({
                    className: 'allo-marker-container',
                    html: `
                        <div class="allo-marker" style="width: 36px; height: 36px;">
                            <div class="allo-marker-inner">
                                ${hasLogo 
                                    ? `<img src="${marker.logo}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">` 
                                    : `<span style="font-size: 12px;">${marker.nom.charAt(0).toUpperCase()}</span>`
                                }
                            </div>
                        </div>
                    `,
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -36]
                });
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }
            
            function createPopupContent(marker) {
                const rating = marker.note 
                    ? `<div class="allo-popup-rating">
                           <span class="star">★</span>
                           <span>${marker.note}</span>
                           <span style="color: #94a3b8;">(${marker.nombreAvis} avis)</span>
                       </div>`
                    : '';
                
                const logo = marker.logo 
                    ? `<img src="${marker.logo}" alt="${escapeHtml(marker.nom)}" class="allo-popup-logo">`
                    : `<div class="allo-popup-logo" style="background: linear-gradient(135deg, #22c55e 0%, #f97316 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                           ${marker.nom.charAt(0).toUpperCase()}
                       </div>`;
                
                return `
                    <div class="allo-popup">
                        <div class="allo-popup-header">
                            ${logo}
                            <div>
                                <p class="allo-popup-title">${escapeHtml(marker.nom)}</p>
                                <p class="allo-popup-type">${escapeHtml(marker.type)}</p>
                            </div>
                        </div>
                        <div class="allo-popup-address">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            ${escapeHtml(marker.adresse)}
                        </div>
                        ${rating}
                        <a href="/p/${marker.slug}" class="allo-popup-link">
                            Voir l'entreprise →
                        </a>
                    </div>
                `;
            }
            
            let markerLayer;
            
            if (enableClustering && markers.length > 1) {
                markerLayer = L.markerClusterGroup({
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    iconCreateFunction: (cluster) => {
                        const count = cluster.getChildCount();
                        return L.divIcon({
                            html: `<div style="
                                background: linear-gradient(135deg, #22c55e 0%, #f97316 100%);
                                color: white;
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: 600;
                                font-size: 14px;
                                border: 3px solid white;
                                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.2);
                            ">${count}</div>`,
                            className: 'allo-cluster-icon',
                            iconSize: [40, 40]
                        });
                    }
                });
            } else {
                markerLayer = L.layerGroup();
            }
            
            markers.forEach(marker => {
                const leafletMarker = L.marker([marker.lat, marker.lng], {
                    icon: createCustomIcon(marker)
                });
                
                leafletMarker.bindPopup(createPopupContent(marker), {
                    maxWidth: 280,
                    minWidth: 220
                });
                
                markerLayer.addLayer(leafletMarker);
            });
            
            markerLayer.addTo(map);
            
            // Ajuster la vue
            if (markers.length > 1) {
                const bounds = markerLayer.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            } else if (markers.length === 1) {
                map.setView([markers[0].lat, markers[0].lng], isSingle ? 15 : 14);
            }
            
            // Stocker la référence
            container._alloMap = map;
        }
        
        // Initialiser quand le DOM est prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            // Attendre que Leaflet soit chargé
            setTimeout(initMap, 100);
        }
    })();
    </script>
</div>
@endif
