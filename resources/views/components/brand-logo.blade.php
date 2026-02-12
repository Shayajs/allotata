@php
    use App\Helpers\SiteHelper;
    
    // Par défaut, utiliser le logo transparent
    $logoUrl = SiteHelper::getLogo('transparent');
    $siteName = SiteHelper::getSiteName();
    
    // Classes personnalisables
    $textClass = $textClass ?? 'text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent';
    $logoClass = $logoClass ?? 'h-8 w-auto';
    $containerClass = $containerClass ?? 'flex items-center gap-3';
    
    // Taille du logo
    $logoSize = $logoSize ?? 'h-8';
    
    // Sur mobile, on préfère le logo seulement si $mobileLogoOnly est true
    $mobileLogoOnly = $mobileLogoOnly ?? false;
@endphp

@if($logoUrl)
    <div class="{{ $containerClass }}">
        <!-- Logo : visible sur tous les écrans -->
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="{{ $logoSize }} w-auto {{ $mobileLogoOnly ? '' : 'md:' }}{{ $logoClass }}">
        
        <!-- Texte : masqué sur mobile si $mobileLogoOnly, sinon toujours visible -->
        <span class="{{ $textClass }} {{ $mobileLogoOnly ? 'hidden md:inline' : '' }}">
            {{ $siteName }}
        </span>
    </div>
@else
    <!-- Fallback : texte seulement -->
    <span class="{{ $textClass }}">
        {{ $siteName }}
    </span>
@endif
