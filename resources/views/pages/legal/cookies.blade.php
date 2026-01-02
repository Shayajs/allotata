@extends('layouts.user')

@section('title', 'Politique relative aux Cookies')

@section('content')
<div class="max-w-4xl mx-auto py-8 text-slate-900 dark:text-slate-100">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent mb-8">Politique relative aux Cookies</h1>

    <div class="space-y-8 bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
        
        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Qu'est-ce qu'un cookie ?</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur, tablette ou mobile) lors de la visite d'un site ou de la consultation d'une publicité. Il permet à son émetteur d'identifier le terminal dans lequel il est enregistré pendant la durée de validité ou d'enregistrement du cookie.
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Types de cookies utilisés</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Nous utilisons les catégories de cookies suivantes sur notre site :
            </p>
            <ul class="list-disc list-inside mt-2 space-y-2 text-slate-600 dark:text-slate-400">
                <li><strong>Cookies strictement nécessaires :</strong> Ils sont indispensables pour naviguer sur le site et profiter de ses fonctionnalités (authentification, session). Sans ces cookies, les services que vous demandez ne peuvent pas être fournis.</li>
                <li><strong>Cookies de performance :</strong> Ils collectent des informations sur la manière dont les visiteurs utilisent un site web (pages les plus visitées, messages d'erreur). Ces cookies ne collectent pas d'informations identifiant le visiteur.</li>
                <li><strong>Cookies de fonctionnalité :</strong> Ils permettent au site de mémoriser vos choix (nom d'utilisateur, langue, région) et de fournir des fonctionnalités améliorées et plus personnelles.</li>
            </ul>
        </section>

        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Gestion des cookies</h2>
            <p class="text-slate-600 dark:text-slate-400">
                Vous avez le choix de configurer votre navigateur pour accepter tous les cookies, rejeter tous les cookies, vous informer quand un cookie est émis, sa durée de validité et son contenu, ainsi que vous permettre de refuser son enregistrement dans votre terminal, et supprimer vos cookies périodiquement.
            </p>
            <p class="text-slate-600 dark:text-slate-400 mt-2">
                Pour la gestion des cookies et de vos choix, la configuration de chaque navigateur est différente. Elle est décrite dans le menu d'aide de votre navigateur, qui vous permettra de savoir de quelle manière modifier vos souhaits en matière de cookies.
            </p>
        </section>

    </div>
</div>
@endsection
