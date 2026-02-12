@extends('layouts.user')

@section('title', 'Politique de Confidentialité')

@section('content')
<div class="max-w-4xl mx-auto py-8 text-slate-900 dark:text-slate-100">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-8">Politique de Confidentialité</h1>

    <div class="space-y-8 bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">1. Collecte des données</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Nous collectons les informations suivantes lorsque vous utilisez notre site Allo Tata :
            </p>
            <ul class="list-disc list-inside pl-4 space-y-2 text-slate-600 dark:text-slate-400">
                <li>Informations d'identité (Nom, Prénom) lors de l'inscription ou du contact.</li>
                <li>Coordonnées (Email, Téléphone, Adresse) pour la gestion de votre compte et la facturation.</li>
                <li>Données relatives à votre entreprise pour les comptes professionnels (Nom de l'entreprise, SIRET, Adresse).</li>
                <li>Données de connexion et de navigation (Adresse IP, type de navigateur, pages visitées - via les cookies).</li>
                <li>Historique de vos réservations et de vos interactions avec la plateforme.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">2. Utilisation des données</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Vos données sont collectées pour les finalités suivantes :
            </p>
            <ul class="list-disc list-inside pl-4 space-y-2 text-slate-600 dark:text-slate-400">
                <li>Fournir et gérer nos services (réservations, gestion d'entreprise).</li>
                <li>Communiquer avec vous concernant votre compte ou nos services (notifications, support).</li>
                <li>Améliorer et optimiser notre plateforme et votre expérience utilisateur.</li>
                <li>Respecter nos obligations légales (facturation, comptabilité).</li>
                <li>Assurer la sécurité de notre plateforme.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">3. Partage des données</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Nous ne vendons, n'échangeons, ni ne louons vos informations personnelles à des tiers. Vos données peuvent être partagées uniquement dans les cas suivants :
            </p>
            <ul class="list-disc list-inside pl-4 space-y-2 text-slate-600 dark:text-slate-400">
                <li>Avec nos prestataires de services tiers qui nous aident à exploiter notre activité (hébergement OVH, paiement Stripe), sous réserve qu'ils s'engagent à garder ces informations confidentielles.</li>
                <li>Lorsque la loi nous y oblige ou pour protéger nos droits, notre propriété ou notre sécurité.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">4. Sécurité des données</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Nous mettons en œuvre une variété de mesures de sécurité pour préserver la sécurité de vos informations personnelles. Nous utilisons un cryptage de pointe pour protéger les informations sensibles transmises en ligne. Seuls les employés qui ont besoin d'effectuer un travail spécifique (par exemple, la facturation ou le service client) ont accès aux informations personnelles identifiables.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">5. Vos droits (RGPD)</h2>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                Conformément au RGPD, vous disposez des droits suivants concernant vos données personnelles :
            </p>
            <ul class="list-disc list-inside pl-4 space-y-2 text-slate-600 dark:text-slate-400">
                <li>Droit d'accès : Vous pouvez demander une copie de vos données.</li>
                <li>Droit de rectification : Vous pouvez demander la modification de vos données inexactes.</li>
                <li>Droit à l'effacement : Vous pouvez demander la suppression de vos données (droit à l'oubli).</li>
                <li>Droit à la limitation du traitement.</li>
                <li>Droit à la portabilité de vos données.</li>
            </ul>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed mt-2">
                Pour exercer ces droits, veuillez nous contacter à : <strong>contact@allo-tata.com</strong>.
            </p>
        </section>
    </div>
</div>
@endsection
