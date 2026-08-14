{{--
    URL canonique d'une page publique d'entreprise.

    La page repond a la fois depuis l'apex (/p/{slug}, /w/{slug}) et depuis le
    sous-domaine de l'entreprise : on n'en fait indexer qu'une seule adresse.
--}}
@php $canonical = \App\Support\SubdomainHost::canonicalUrl(); @endphp
@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif
