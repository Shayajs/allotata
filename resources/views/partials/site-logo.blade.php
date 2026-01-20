@php
    use App\Helpers\SiteHelper;
    
    // Par défaut, utiliser le logo transparent pour l'affichage sur le site
    $type = $type ?? 'transparent';
    $class = $class ?? '';
    $alt = $alt ?? SiteHelper::getSiteName();
    $width = $width ?? null;
    $height = $height ?? null;
    
    $logoUrl = SiteHelper::getLogo($type);
@endphp

@if($logoUrl)
    <img 
        src="{{ $logoUrl }}" 
        alt="{{ $alt }}"
        @if($class) class="{{ $class }}" @endif
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        style="@if($width) width: {{ $width }}px; @endif @if($height) height: {{ $height }}px; @endif object-fit: contain;"
    >
@else
    <span class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent {{ $class }}">
        {{ SiteHelper::getSiteName() }}
    </span>
@endif
