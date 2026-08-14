<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FAQ - Questions fréquentes - Allo Tata</title>
    <meta name="description" content="Réponses aux questions fréquentes sur Allo Tata : réservations, paiements, boutique, abonnement et support.">
    @include('partials.canonical')
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    @include('partials.super-user-banner')
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    Allo Tata
                </a>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                            Connexion
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="pt-24 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                    Questions fréquentes
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Trouvez rapidement les réponses à vos questions
                </p>
            </div>

            <div class="space-y-6">
                <!-- Question 1 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment créer une entreprise ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Pour créer une entreprise sur Allo Tata, connectez-vous à votre compte, puis allez dans votre dashboard. 
                        Cliquez sur "Créer une entreprise" et remplissez les informations demandées (nom, type d'activité, adresse, etc.). 
                        Une fois les informations complétées, votre entreprise sera en attente de vérification par notre équipe.
                    </p>
                </div>

                <!-- Question 2 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment fonctionne le paiement ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Allo Tata propose plusieurs modes de paiement sécurisés. Les paiements peuvent être effectués en ligne via 
                        notre système intégré, ou directement sur place selon les préférences de l'entreprise. 
                        Les factures sont générées automatiquement et peuvent être téléchargées depuis votre espace personnel.
                    </p>
                </div>

                <!-- Question 3 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment modifier une réservation ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Pour modifier une réservation, accédez à votre dashboard et allez dans la section "Mes réservations". 
                        Sélectionnez la réservation que vous souhaitez modifier et cliquez sur "Modifier". 
                        Vous pouvez changer la date, l'heure ou d'autres détails selon les disponibilités. 
                        Pour annuler, utilisez le bouton "Annuler" dans les détails de la réservation.
                    </p>
                </div>

                <!-- Question 4 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment gérer mes services et prestations ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Dans votre dashboard entreprise, allez dans l'onglet "Services" pour ajouter, modifier ou supprimer vos prestations. 
                        Vous pouvez définir le nom, la description, la durée, le prix et ajouter des images pour chaque service. 
                        Les services peuvent être activés ou désactivés à tout moment.
                    </p>
                </div>

                <!-- Question 5 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment activer la livraison pour mes produits ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Pour activer la livraison et permettre à vos clients de commander vos produits en ligne, 
                        allez dans les paramètres de votre entreprise et activez l'option "Mode de livraison disponible". 
                        Une fois activé, les boutons "Commander" apparaîtront sur votre page boutique publique.
                    </p>
                </div>

                <!-- Question 6 -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                        Comment contacter le support ?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        Trois façons : <a href="{{ route('contact.create') }}" class="text-green-600 dark:text-green-400 hover:underline">le formulaire de contact</a>
                        pour une question ponctuelle, <a href="{{ route('tickets.create') }}" class="text-green-600 dark:text-green-400 hover:underline">un ticket</a>
                        pour suivre un échange dans le temps, ou un email à support@allotata.com.
                        Nous nous efforçons de répondre à toutes les demandes dans les plus brefs délais.
                    </p>
                </div>
            </div>

            <div class="mt-12 text-center">
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Vous ne trouvez pas la réponse à votre question ?
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('contact.create') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition">
                        Écrire au support
                    </a>
                    <a href="{{ route('tickets.create') }}" class="inline-block px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:border-green-500 transition">
                        Créer un ticket
                    </a>
                    @auth
                        <a href="{{ route('tickets.index') }}" class="inline-block px-6 py-3 text-slate-600 dark:text-slate-400 font-semibold rounded-xl hover:text-green-600 dark:hover:text-green-400 transition">
                            Mes tickets
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
