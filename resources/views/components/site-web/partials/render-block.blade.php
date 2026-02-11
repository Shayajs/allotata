{{-- Rendu d'un bloc générique (factorisation du switch) --}}
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
    @case('map')
        <x-site-web.blocks.map :block="$block" :entreprise="$entreprise" />
        @break
    @case('columns')
        <x-site-web.blocks.columns :block="$block" :entreprise="$entreprise" />
        @break
    {{-- Blocs spéciaux --}}
    @case('reservation')
        @include('components.site-web.blocks.reservation', ['block' => $block, 'entreprise' => $entreprise])
        @break
    @case('agenda')
        @include('components.site-web.blocks.agenda', ['block' => $block, 'entreprise' => $entreprise])
        @break
    @case('login-cta')
        @include('components.site-web.blocks.login-cta', ['block' => $block, 'entreprise' => $entreprise])
        @break
@endswitch
