{{--
    Page de garde de api.allotata.* — volontairement autonome : le groupe de
    middleware « api » n'ouvre pas de session, on n'herite donc d'aucun layout.
--}}
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Allo Tata — v1</title>
    <meta name="description" content="Documentation de l'API Allo Tata : annuaire des entreprises et adresses françaises en accès libre, gestion de vos réservations, services et statistiques avec un jeton personnel.">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-950 text-slate-300 antialiased">

<div class="mx-auto max-w-4xl px-6 py-16 sm:py-20">

    <header>
        <div class="flex items-center gap-3">
            <span class="rounded-full bg-green-500/10 px-3 py-1 text-xs font-semibold text-green-400 ring-1 ring-green-500/30">v1</span>
            <span class="text-xs text-slate-500">stable · lecture seule</span>
        </div>

        <h1 class="mt-6 text-4xl font-bold tracking-tight text-white sm:text-5xl">
            API Allo Tata
        </h1>

        <p class="mt-4 max-w-2xl text-lg text-slate-400">
            Deux usages, une seule version. En accès libre : l'annuaire des entreprises publiées et la
            recherche d'adresses françaises. Avec un jeton personnel : la gestion de vos entreprises,
            réservations, créneaux, clientèle et finances. Tout est en JSON, en lecture seule.
        </p>

        <div class="mt-8 rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">URL de base</p>
            <p class="mt-1 font-mono text-base text-green-400 break-all">{{ $baseUrl }}/</p>
        </div>
    </header>

    <section class="mt-12 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5">
            <h2 class="text-sm font-semibold text-white">Authentification</h2>
            <p class="mt-2 text-sm text-slate-400">Aucune pour les données publiques. Un jeton personnel pour tout ce qui touche à un compte.</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5">
            <h2 class="text-sm font-semibold text-white">Limite d'appels</h2>
            <p class="mt-2 text-sm text-slate-400">{{ $limiteParMinute }} requêtes par minute et par adresse IP en accès libre, {{ $limiteGestionParMinute }} avec un jeton. Au-delà, la réponse est un <span class="font-mono text-slate-300">429</span>.</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5">
            <h2 class="text-sm font-semibold text-white">Format</h2>
            <p class="mt-2 text-sm text-slate-400">JSON en UTF-8, en <span class="font-mono text-slate-300">GET</span> uniquement. Les listes arrivent dans <span class="font-mono text-slate-300">donnees</span>, accompagnées de <span class="font-mono text-slate-300">pagination</span>.</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-5">
            <h2 class="text-sm font-semibold text-white">Versions</h2>
            <p class="mt-2 text-sm text-slate-400">La v1 est la seule version publique, et aucune v2 n'est prévue. Rien ne sera retiré de la v1 sans préavis.</p>
        </div>
    </section>

    <section class="mt-16">
        <h2 class="text-2xl font-bold text-white">Accès libre</h2>
        <p class="mt-2 text-sm text-slate-400">Données déjà publiques, aucun en-tête requis.</p>

        <div class="mt-8 space-y-10">
            @foreach ($endpoints as $endpoint)
                @include('api.partials.endpoint', ['endpoint' => $endpoint, 'baseUrl' => $baseUrl])
            @endforeach
        </div>
    </section>

    <section class="mt-20">
        <h2 class="text-2xl font-bold text-white">Gestion de votre compte</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">
            Ces endpoints lisent les données de votre compte et des entreprises que vous gérez.
            Ils demandent un jeton personnel, créé depuis vos réglages.
        </p>

        <div class="mt-8 rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="text-sm font-semibold text-white">Obtenir un jeton</h3>
            <p class="mt-2 text-sm text-slate-400">
                Rendez-vous dans <a href="{{ $jetonsUrl }}" class="font-medium text-green-400 hover:underline">Réglages → API &amp; jetons</a>,
                nommez le jeton, choisissez sa durée de validité. Il n'est affiché qu'une fois : la base n'en garde
                qu'une empreinte, donc un jeton perdu se remplace, il ne se retrouve pas.
            </p>

            <h3 class="mt-6 text-sm font-semibold text-white">L'envoyer à chaque appel</h3>
<pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-300 ring-1 ring-slate-800"><code>curl -H "Authorization: Bearer alto_votre_jeton" \
     "{{ $baseUrl }}/moi"</code></pre>

            <h3 class="mt-6 text-sm font-semibold text-white">Quand ça refuse</h3>
            <p class="mt-2 text-sm text-slate-400">
                Les erreurs ont toujours la même forme, avec un <span class="font-mono text-slate-300">code</span> stable
                pour être traité par programme :
            </p>
<pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-400 ring-1 ring-slate-800"><code>{
  "message": "Jeton d'API invalide, révoqué ou expiré.",
  "code": "jeton_invalide"
}</code></pre>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <li><span class="font-mono text-slate-300">401 jeton_absent</span> — l'en-tête Authorization manque.</li>
                <li><span class="font-mono text-slate-300">401 jeton_invalide</span> — jeton inconnu, révoqué ou expiré.</li>
                <li><span class="font-mono text-slate-300">403 entreprise_hors_perimetre</span> — l'entreprise existe mais ce compte ne la gère pas.</li>
                <li><span class="font-mono text-slate-300">404 entreprise_inconnue</span> — aucun slug ne correspond.</li>
                <li><span class="font-mono text-slate-300">422</span> — un paramètre est illisible (date, statut, type).</li>
            </ul>

            <h3 class="mt-6 text-sm font-semibold text-white">Pourquoi pas d'écriture</h3>
            <p class="mt-2 text-sm text-slate-400">
                Créer ou modifier une réservation déclenche des notifications, des e-mails, l'émission d'une facture
                et une synchronisation d'agenda. Tant que ces règles vivent dans l'application web, une écriture
                exposée ici en oublierait la moitié sans le dire. La v1 lit, et le fera bien.
            </p>
        </div>

        <div class="mt-8 space-y-10">
            @foreach ($endpointsGestion as $endpoint)
                @include('api.partials.endpoint', [
                    'endpoint' => $endpoint,
                    'baseUrl' => $baseUrl,
                    'entete' => '-H "Authorization: Bearer alto_votre_jeton"',
                ])
            @endforeach
        </div>
    </section>

    <section class="mt-16 rounded-xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 class="text-lg font-semibold text-white">Et la v3 ?</h2>
        <p class="mt-2 text-sm text-slate-400">
            Les chemins <span class="font-mono text-slate-300">/v3/</span> existent mais ne sont pas une suite de la v1 :
            c'est la version imposée par la spécification <em>Reserve with Google</em>, réservée aux serveurs de Google
            et protégée par une clé. Elle n'est pas ouverte aux intégrations tierces.
        </p>
    </section>

    <footer class="mt-16 border-t border-slate-800 pt-8 text-sm text-slate-500">
        <p>
            Un endpoint vous manque ou une réponse vous surprend ?
            <a href="{{ route('support.faq') }}" class="font-medium text-green-400 hover:underline">Contactez le support</a>.
        </p>
        <p class="mt-2">
            <a href="{{ url('/') }}" class="hover:text-slate-300">Allo Tata</a>
            ·
            <a href="{{ $baseUrl }}" class="font-mono hover:text-slate-300">{{ $baseUrl }}</a>
        </p>
    </footer>

</div>

</body>
</html>
