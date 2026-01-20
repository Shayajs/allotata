@php
    use App\Helpers\SiteHelper;
    
    $logoLight = SiteHelper::getFavicon('light');
    $logoDark = SiteHelper::getFavicon('dark');
@endphp

@if($logoLight)
    <link rel="icon" type="image/png" href="{{ $logoLight }}" media="(prefers-color-scheme: light)">
@endif
@if($logoDark)
    <link rel="icon" type="image/png" href="{{ $logoDark }}" media="(prefers-color-scheme: dark)">
@endif
@if($logoLight)
    <link rel="icon" type="image/png" href="{{ $logoLight }}">
@endif
