<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $entreprise->nom }} - Allo Tata</title>
    <meta name="description" content="{{ $entreprise->phrase_accroche ?? $entreprise->description }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $entreprise->nom }}">
    <meta property="og:description" content="{{ $entreprise->phrase_accroche ?? $entreprise->description }}">
    @if(!empty($entreprise->logo))
        <meta property="og:image" content="{{ route('storage.serve', ['path' => $entreprise->logo]) }}">
    @endif
    <meta property="og:type" content="website">
    
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Oswald:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Source+Sans+Pro:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css'])
    
    @php
        $theme = $entreprise->site_web_theme;
    @endphp
    
    <style>
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
        
        body {
            font-family: var(--site-font-body);
            background: var(--site-background);
            color: var(--site-text);
        }
        
        /* Animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .animate-fadeIn { animation: fadeIn 0.6s ease forwards; }
        .animate-slideUp { animation: slideUp 0.6s ease forwards; }
        .animate-slideLeft { animation: slideLeft 0.6s ease forwards; }
        .animate-zoomIn { animation: zoomIn 0.6s ease forwards; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideLeft {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="antialiased">
    {{-- Barre propriétaire en mode view --}}
    @if(isset($isOwner) && $isOwner && !empty($entreprise->slug_web))
        <div class="fixed top-0 left-0 right-0 z-50 bg-slate-900 text-white py-2 px-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <span class="text-sm">Vous visualisez votre site en mode public</span>
                <a href="{{ route('site-web.show', ['slug' => $entreprise->slug_web]) }}" 
                   class="px-4 py-1 text-sm font-medium bg-green-600 hover:bg-green-700 rounded-lg transition">
                    Retour à l'édition
                </a>
            </div>
        </div>
        <div class="h-10"></div>
    @endif
    
    {{-- Contenu principal --}}
    <main>
        @php
            $blocks = $entreprise->getSiteWebBlocks();
        @endphp
        
        @if(count($blocks) > 0)
            @foreach($blocks as $block)
                @php
                    $animation = $block['animation'] ?? 'none';
                    $animationClass = $animation !== 'none' ? "animate-on-scroll" : '';
                @endphp
                
                <div class="{{ $animationClass }}" data-animation="{{ $animation }}">
                    @switch($block['type'])
                        @case('hero')
                            <x-site-web.blocks.hero :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('text')
                            <x-site-web.blocks.text :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('image')
                            <x-site-web.blocks.image :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('gallery')
                            <x-site-web.blocks.gallery :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('contact')
                            <x-site-web.blocks.contact :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('video')
                            <x-site-web.blocks.video :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('services')
                            <x-site-web.blocks.services :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('testimonials')
                            <x-site-web.blocks.testimonials :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('cta')
                            <x-site-web.blocks.cta :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('divider')
                            <x-site-web.blocks.divider :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('iframe')
                            <x-site-web.blocks.iframe :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('faq')
                            <x-site-web.blocks.faq :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('team')
                            <x-site-web.blocks.team :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('stats')
                            <x-site-web.blocks.stats :block="$block" :entreprise="$entreprise" />
                            @break
                        @case('features')
                            <x-site-web.blocks.features :block="$block" :entreprise="$entreprise" />
                            @break
                    @endswitch
                </div>
            @endforeach
        @else
            {{-- Fallback si pas de contenu --}}
            <div class="min-h-screen flex items-center justify-center">
                <div class="text-center p-8">
                    @if(!empty($entreprise->logo))
                        <img src="{{ route('storage.serve', ['path' => $entreprise->logo]) }}" alt="{{ $entreprise->nom }}" class="w-32 h-32 mx-auto mb-6 rounded-xl object-cover">
                    @endif
                    <h1 class="text-4xl font-bold mb-4" style="font-family: var(--site-font-heading);">
                        {{ $entreprise->nom }}
                    </h1>
                    @if($entreprise->phrase_accroche)
                        <p class="text-xl text-slate-600 dark:text-slate-400 mb-6">
                            {{ $entreprise->phrase_accroche }}
                        </p>
                    @endif
                    <a href="{{ route('public.entreprise', ['slug' => $entreprise->slug]) }}" 
                       class="inline-block px-8 py-4 text-lg font-semibold text-white transition hover:opacity-90"
                       style="background: var(--site-primary); border-radius: var(--site-button-radius); box-shadow: var(--site-button-shadow);">
                        Voir la page entreprise
                    </a>
                </div>
            </div>
        @endif
    </main>
    
    {{-- Footer simple --}}
    <footer class="py-8 px-4 text-center border-t border-slate-200 dark:border-slate-700">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            © {{ date('Y') }} {{ $entreprise->nom }}. Tous droits réservés.
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
            Propulsé par <a href="{{ route('home') }}" class="hover:underline" style="color: var(--site-primary);">Allo Tata</a>
        </p>
    </footer>
    
    {{-- Script pour animations au scroll --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const animation = entry.target.dataset.animation;
                        entry.target.classList.add('visible');
                        if (animation && animation !== 'none') {
                            entry.target.classList.add('animate-' + animation);
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            animatedElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
