{{--
    URL canonique d'une page publique.

    Une meme page repond souvent depuis l'apex et depuis son sous-domaine
    (/p/{slug} et {slug}.*, /apprendre et learn.*) : on n'en fait indexer qu'une
    seule adresse. Les espaces de travail, eux, ne sont pas indexes : inutile de
    leur declarer quoi que ce soit.
--}}
@php
    $chemin = request()->attributes->get('subdomain.rewritten', request()->getPathInfo());
    $canonical = \App\Support\SubdomainHost::isIndexablePath((string) $chemin)
        ? \App\Support\SubdomainHost::canonicalUrl()
        : null;
@endphp
@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif
