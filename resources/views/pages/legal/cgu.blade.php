@extends('layouts.user')

@section('title', 'Conditions Générales d\'Utilisation')

@section('content')
<div class="max-w-4xl mx-auto py-8 text-slate-900 dark:text-slate-100">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-8">Conditions Générales d'Utilisation (CGU)</h1>

    <div class="space-y-8 bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        
        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Objet</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les présentes Conditions Générales d'Utilisation ont pour objet de définir les modalités de mise à disposition des services du site Allo Tata et les conditions d'utilisation du Service par l'Utilisateur.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Accès au service</h2>
            <p class="text-slate-600 dark:text-slate-400">
                L'accès au site Allo Tata est possible 24 heures sur 24, 7 jours sur 7, sauf en cas de force majeure ou d'un événement hors du contrôle de l'éditeur et sous réserve des éventuelles pannes et interventions de maintenance nécessaires au bon fonctionnement du site et des matériels.
                L'éditeur se réserve le droit de refuser l'accès au Service, unilatéralement et sans notification préalable, à tout Utilisateur ne respectant pas les présentes conditions d'utilisation.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Responsabilité de l'éditeur</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Les informations et/ou documents figurant sur ce site et/ou accessibles par ce site proviennent de sources considérées comme étant fiables. Toutefois, ces informations et/ou documents sont susceptibles de contenir des inexactitudes techniques et des erreurs typographiques.
                L'éditeur ne pourra en aucun cas être tenu responsable de tout dommage de quelque nature qu'il soit résultant de l'interprétation ou de l'utilisation des informations et/ou documents disponibles sur ce site.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">4. Règles d'usage du site</h2>
            <p class="text-slate-600 dark:text-slate-400">
                L'utilisateur s'engage à n'utiliser les services du site ainsi que l'ensemble des informations auxquelles il pourra avoir accès que pour des raisons personnelles et dans un but conforme à l'ordre public, aux bonnes mœurs et aux droits des tiers.
                L'utilisateur s'engage à ne pas perturber l'usage que pourraient faire les autres utilisateurs du site et à ne pas accéder aux parties du site dont l'accès est réservé.
                Il est interdit de collecter ou d'utiliser les données personnelles présentes sur ce site à des fins commerciales.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">5. Trackers de visite et données de navigation</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Dans le cadre de l'amélioration continue de nos services et pour simplifier le travail des professionnels utilisant notre plateforme, Allo Tata utilise des trackers de visite sur les pages publiques des entreprises.
            </p>
            <p class="text-slate-600 dark:text-slate-400">
                Ces trackers permettent de collecter des données anonymisées concernant la navigation des visiteurs sur les pages publiques des entreprises, notamment :
            </p>
            <ul class="list-disc list-inside text-slate-600 dark:text-slate-400 space-y-2 ml-4">
                <li>Le nombre de visites sur les pages publiques des entreprises</li>
                <li>La durée de visite sur chaque page</li>
                <li>Les services et produits consultés</li>
                <li>Le temps entre la visite et la prise de rendez-vous (lorsqu'une réservation est effectuée)</li>
                <li>Les statistiques de rebond et d'exploration des pages</li>
            </ul>
            <p class="text-slate-600 dark:text-slate-400">
                Ces données sont utilisées exclusivement pour fournir des statistiques aux professionnels leur permettant d'améliorer leur activité et d'optimiser leur présence en ligne. Les données collectées ne sont pas utilisées à des fins commerciales ou publicitaires par Allo Tata.
            </p>
            <p class="text-slate-600 dark:text-slate-400">
                Conformément au Règlement Général sur la Protection des Données (RGPD), l'utilisation de ces trackers est soumise à votre consentement explicite. Vous pouvez à tout moment accepter ou refuser ces trackers via la bannière de cookies lors de votre première visite, ou modifier votre préférence dans les paramètres de votre compte dans la section "Confidentialité".
            </p>
            <p class="text-slate-600 dark:text-slate-400">
                Pour les utilisateurs non connectés, le consentement est géré via un mécanisme de cookies stocké localement. Pour les utilisateurs connectés, le consentement est enregistré dans votre profil et peut être modifié à tout moment. Le refus des trackers n'affecte pas votre capacité à utiliser les services du site.
            </p>
            <p class="text-slate-600 dark:text-slate-400">
                Les données collectées sont stockées de manière sécurisée et ne sont accessibles qu'aux professionnels propriétaires des pages concernées. Pour plus d'informations sur le traitement de vos données personnelles, consultez notre <a href="{{ route('legal.confidentialite') }}" class="text-green-600 dark:text-green-400 hover:underline">Politique de Confidentialité</a>.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">6. Modification des CGU</h2>
            <p class="text-slate-600 dark:text-slate-400">
                L'éditeur se réserve la possibilité de modifier, à tout moment et sans préavis, les présentes conditions d'utilisation afin de les adapter aux évolutions du site et/ou de son exploitation.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">7. Droit applicable</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Tant le présent site que les modalités et conditions de son utilisation sont régis par le droit français, quel que soit le lieu d'utilisation. En cas de contestation éventuelle, et après l'échec de toute tentative de recherche d'une solution amiable, les tribunaux français seront seuls compétents pour connaître de ce litige.
            </p>
        </section>
    </div>
</div>
@endsection
