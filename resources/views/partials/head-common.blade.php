{{--
    En-tête commun (favicon site + thème).
    À inclure dans <head> des pages standalone, ou laisser le middleware InjectSiteFavicon s'en charger.

    Contre-règle : @include('partials.head-common', ['skipSiteFavicon' => true])
--}}
@includeUnless(!empty($skipSiteFavicon), 'partials.favicon')
@include('partials.theme-script')
@include('partials.api-base')
