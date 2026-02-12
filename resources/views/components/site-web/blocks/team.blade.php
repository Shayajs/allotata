@props(['block', 'entreprise', 'editMode' => false])

@php
    $content = $block['content'] ?? [];
    $settings = $block['settings'] ?? [];
    
    $title = $content['title'] ?? 'Notre équipe';
    $members = $content['members'] ?? [];
    
    // Si pas de membres définis, utiliser le gérant de l'entreprise
    if (empty($members) && $entreprise->user) {
        $members = [
            [
                'name' => $entreprise->afficher_nom_gerant ? $entreprise->user->name : $entreprise->nom,
                'role' => 'Gérant(e)',
                'photo' => $entreprise->user->photo_profil,
                'description' => $entreprise->type_activite,
            ]
        ];
    }
    
    $columns = $settings['columns'] ?? 3;
    $colClass = match($columns) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-2 lg:grid-cols-3',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3'
    };
@endphp

<section class="py-16 md:py-24 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-12"
            style="font-family: var(--site-font-heading); color: var(--site-text);"
            @if($editMode) data-editable="title" @endif>
            {{ $title }}
        </h2>
        
        @if(count($members) > 0)
            <div class="grid grid-cols-1 {{ $colClass }} gap-8">
                @foreach($members as $member)
                    <div class="text-center group">
                        <div class="relative w-40 h-40 mx-auto mb-6 overflow-hidden rounded-full">
                            @if(!empty($member['photo']))
                                <img 
                                    src="{{ str_starts_with($member['photo'], 'http') ? $member['photo'] : asset('storage/' . $member['photo']) }}"
                                    alt="{{ $member['name'] }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white text-4xl font-bold" style="background: linear-gradient(135deg, var(--site-primary), var(--site-secondary));">
                                    {{ strtoupper(substr($member['name'], 0, 1)) }}
                                </div>
                            @endif
                            
                            {{-- Overlay au survol --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                @if(!empty($member['social']))
                                    <div class="flex gap-3">
                                        {{-- Liens sociaux si définis --}}
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-bold mb-1" style="color: var(--site-text); font-family: var(--site-font-heading);">
                            {{ $member['name'] }}
                        </h3>
                        
                        <p class="font-medium mb-2" style="color: var(--site-primary);">
                            {{ $member['role'] ?? '' }}
                        </p>
                        
                        @if(!empty($member['description']))
                            <p class="text-slate-600 dark:text-slate-400 text-sm" style="font-family: var(--site-font-body);">
                                {{ $member['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <p>Ajoutez des membres d'équipe</p>
            </div>
        @endif
    </div>
</section>
