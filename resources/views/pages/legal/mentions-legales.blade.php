@extends('layouts.user')

@section('title', 'Mentions Légales')

@section('content')
<div class="max-w-4xl mx-auto py-8 text-slate-900 dark:text-slate-100">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-8">Mentions Légales</h1>

    <div class="space-y-8">
        <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-bold mb-4">1. Éditeur du site</h2>
            <p>Le site <strong>Allo Tata</strong> est édité par :</p>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
                <li><strong>Raison sociale :</strong> Auto-entreprise BrightShell</li>
                <li><strong>Responsable de la publication :</strong> Lucas Espinar</li>
                <li><strong>Téléphone :</strong> 06.44.07.30.37</li>
                <li><strong>Email :</strong> <a href="mailto:lucas.espinar@brightshell.fr" class="text-green-600 hover:underline">lucas.espinar@brightshell.fr</a></li>
                <li><strong>Site Web :</strong> <a href="https://brightshell.fr" target="_blank" class="text-green-600 hover:underline">brightshell.fr</a></li>
                <li><strong>Numéro SIRET :</strong> 994 535 904 00019</li>
                <li><strong>Code APE :</strong> 6201Z</li>
            </ul>
        </section>

        <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-bold mb-4">2. Hébergement</h2>
            <p>Le site est hébergé par :</p>
            <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 dark:text-slate-400">
                <li><strong>Nom :</strong> OVHCloud</li>
                <li><strong>Raison sociale :</strong> OVH SAS</li>
                <li><strong>Adresse :</strong> 2 rue Kellermann, 59100 Roubaix, France</li>
                <li><strong>Téléphone :</strong> 1007</li>
                <li><strong>Site web :</strong> <a href="https://www.ovhcloud.com" class="text-green-600 hover:underline">www.ovhcloud.com</a></li>
            </ul>
        </section>

        <section class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-bold mb-4">3. Propriété intellectuelle</h2>
            <p class="text-slate-600 dark:text-slate-400">
                L'ensemble de ce site (structure, présentation, contenus, code source) relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.
                <br><br>
                Toute reproduction, représentation, modification, publication, transmission, dénaturation, totale ou partielle du site ou de son contenu, par quelque procédé que ce soit, et sur quelque support que ce soit est interdite.
            </p>
        </section>
    </div>
</div>
@endsection
