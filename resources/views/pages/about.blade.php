@extends('layouts.user')

@section('title', 'À propos & Mentions Légales')

@section('content')
<div class="max-w-4xl mx-auto space-y-12 py-8">
    
    <!-- En-tête -->
    <div class="text-center space-y-4">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
            À propos & Mentions Légales
        </h1>
        <p class="text-xl text-slate-600 dark:text-slate-400">
            Transparence, conformité et confiance.
        </p>
    </div>

    <!-- 1. Identification de l'Éditeur -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">1</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Identification de l'Éditeur</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <p><strong>Raison sociale :</strong> Auto-entreprise BrightShell</p>
            <p><strong>Nom du responsable :</strong> Lucas Espinar</p>
            <p><strong>Téléphone :</strong> 06.44.07.30.37</p>
            <p><strong>Site Web :</strong> <a href="https://brightshell.fr" target="_blank" class="text-green-600 hover:underline">brightshell.fr</a></p>
            <p><strong>Identifiants :</strong> SIRET 994 535 904 00019</p>
        </div>
    </section>

    <!-- 2. Contact -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">2</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Contact</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <p>Vous pouvez nous contacter directement via les moyens suivants :</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li><strong>Email :</strong> <a href="mailto:lucas.espinar@brightshell.fr" class="text-green-600 hover:underline">lucas.espinar@brightshell.fr</a></li>
                <li><strong>Téléphone :</strong> 06.44.07.30.37</li>
                <li>Via le <button onclick="document.getElementById('contact-modal').classList.remove('hidden')" class="text-green-600 hover:underline">formulaire de contact</button> présent sur le site.</li>
            </ul>
        </div>
    </section>

    <!-- 3. Hébergement -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">3</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Hébergement</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <p>Le site est hébergé par :</p>
            <p><strong>Nom :</strong> OVHCloud</p>
            <p><strong>Adresse :</strong> 2 rue Kellermann, 59100 Roubaix, France</p>
            <p><strong>Téléphone :</strong> 1007</p>
            <p><strong>Site web :</strong> <a href="https://www.ovhcloud.com" target="_blank" class="text-green-600 hover:underline">www.ovhcloud.com</a></p>
        </div>
    </section>

    <!-- 4. Propriété Intellectuelle -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">4</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Propriété Intellectuelle</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <p>
                L'ensemble du contenu présent sur ce site (structure, design, textes, images, logos, code) est la propriété exclusive de <strong>BrightShell</strong> ou de ses partenaires.
            </p>
            <p>
                Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite, sauf autorisation écrite préalable de l'éditeur.
            </p>
            <p>
                Toute exploitation non autorisée du site ou de l’un quelconque des éléments qu’il contient sera considérée comme constitutive d’une contrefaçon et poursuivie conformément aux dispositions des articles L.335-2 et suivants du Code de Propriété Intellectuelle.
            </p>
        </div>
    </section>

    <!-- 5. Gestion des Données Personnelles (RGPD) -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">5</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des Données Personnelles (RGPD)</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200">Finalité de la collecte</h3>
            <p>Les informations recueillies sur les formulaires sont enregistrées dans un fichier informatisé par <strong>BrightShell</strong> pour :</p>
            <ul class="list-disc list-inside space-y-1 ml-4">
                <li>La gestion de la clientèle</li>
                <li>Le suivi des demandes de support et de contact</li>
                <li>L'envoi d'informations relatives aux services (si consenti)</li>
            </ul>

            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 mt-4">Destinataires des données</h3>
            <p>Les données sont destinées exclusivement à l'éditeur du site et à son personnel habilité. Aucune information personnelle n'est cédée à des tiers sans votre consentement explicite, sauf obligation légale.</p>

            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 mt-4">Durée de conservation</h3>
            <p>Les données sont conservées pendant une durée maximale de 3 ans à compter du dernier contact pour les prospects, et durant toute la durée de la relation commerciale augmentée des délais légaux pour les clients.</p>

            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 mt-4">Vos droits</h3>
            <p>Conformément à la loi « informatique et libertés » et au RGPD, vous pouvez exercer votre droit d'accès, de rectification, de suppression et d'opposition aux données vous concernant en contactant : <strong>lucas.espinar@brightshell.fr</strong>.</p>
        </div>
    </section>

    <!-- 6. Cookies -->
    <section class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 text-xl font-bold">6</div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Utilisation des Cookies</h2>
        </div>
        <div class="space-y-4 text-slate-600 dark:text-slate-400">
            <p>
                Ce site utilise des cookies pour améliorer l'expérience utilisateur, réaliser des statistiques de visites et permettre le bon fonctionnement de certains services (comme l'authentification).
            </p>
            <p>
                Vous avez la possibilité de gérer vos préférences en matière de cookies via la bannière de consentement affichée lors de votre première visite ou via les paramètres de votre navigateur.
            </p>
        </div>
    </section>

</div>
@endsection
