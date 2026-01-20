@extends('layouts.user')

@section('title', 'Fonctionnalités - Allo Tata')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Hero Section -->
    <section class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-900">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold mb-6">
                <span class="block text-slate-900 dark:text-white">Toutes les fonctionnalités</span>
                <span class="block bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    pour gérer votre entreprise
                </span>
            </h1>
            <p class="text-xl sm:text-2xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-10">
                Une plateforme complète pensée spécialement pour les micro-entreprises. 
                Tout ce dont vous avez besoin, au même endroit.
            </p>
            
            <!-- Navigation rapide -->
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <a href="#gestion-entreprise" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition text-sm font-medium">
                    Gestion d'Entreprise
                </a>
                <a href="#agenda-reservations" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition text-sm font-medium">
                    Agenda & Réservations
                </a>
                <a href="#site-vitrine" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition text-sm font-medium">
                    Site Vitrine
                </a>
                <a href="#messagerie" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition text-sm font-medium">
                    Messagerie
                </a>
                <a href="#finances" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-green-500 dark:hover:border-green-500 transition text-sm font-medium">
                    Finances
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Accéder au dashboard
                    </a>
                @else
                    <a href="{{ route('signup') }}" class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition">
                        Essayer gratuitement
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Sections de fonctionnalités -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 space-y-20">
        
        <!-- A. Gestion d'Entreprise -->
        <section id="gestion-entreprise" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Gestion d'Entreprise</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Créez et gérez votre entreprise en toute simplicité</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Dashboard centralisé</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Vue d'ensemble complète de votre activité, statistiques en temps réel et indicateurs clés.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Configuration complète</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Logo, image de fond, photos de réalisations, description et mots-clés pour améliorer votre visibilité.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Vérification SIREN</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Validation automatique de votre SIREN et vérification par nos administrateurs pour la confiance.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Géolocalisation</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Adresse complète avec géolocalisation automatique, rayon de déplacement pour les services mobiles.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Options avancées</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Langues parlées, modes de paiement acceptés, options spécifiques à votre activité.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Paramètres flexibles</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Prix négociables, rendez-vous uniquement par messagerie, affichage des coordonnées.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- B. Agenda & Réservations -->
        <section id="agenda-reservations" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Agenda & Réservations</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Gérez votre planning et vos rendez-vous efficacement</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Calendrier visuel</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Vue calendrier intuitive avec tous vos rendez-vous, créneaux disponibles et indisponibilités.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Horaires d'ouverture</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Définissez vos horaires hebdomadaires et gérez les jours exceptionnels (fermetures, horaires spéciaux).</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Types de services</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez vos services avec prix, durée, description et images pour faciliter la réservation.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Réservations en ligne</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les clients réservent directement depuis votre page publique avec disponibilité en temps réel.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Réservations manuelles</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez des réservations manuellement pour les clients non-inscrits ou les prises de rendez-vous téléphoniques.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Gestion des statuts</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">En attente, confirmée, terminée, annulée - Gérez chaque étape du cycle de vie de la réservation.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Notifications automatiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Emails et SMS automatiques pour les nouvelles réservations, confirmations et rappels.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Rappels de rendez-vous</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Rappels automatiques par email et SMS avant chaque rendez-vous pour réduire les oublis.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- C. Site Vitrine Professionnel -->
        <section id="site-vitrine" class="scroll-mt-32">
            <div class="bg-gradient-to-br from-green-50 to-orange-50 dark:from-green-900/20 dark:to-orange-900/20 rounded-2xl p-8 shadow-sm border-2 border-green-200 dark:border-green-800">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Site Vitrine Professionnel</h2>
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">ADD-ON</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Créez votre site web professionnel sans code</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Éditeur drag-and-drop</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez votre site web visuellement avec un éditeur intuitif, sans aucune connaissance technique.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">16 types de blocs</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Hero, Services, Galerie, Témoignages, FAQ, Contact, CTA, Stats, Équipe, et bien plus encore.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Personnalisation complète</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Thème personnalisable (couleurs, polices, styles) pour créer un site unique à votre image.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">URL personnalisée</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Votre site accessible sur /w/{slug} avec un design professionnel et responsive.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Système de versionning</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Sauvegardes automatiques et possibilité de restaurer une version précédente.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">SEO optimisé</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Optimisation automatique pour les moteurs de recherche et design responsive mobile.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- D. Gestion Multi-Personnes -->
        <section id="multi-personnes" class="scroll-mt-32">
            <div class="bg-gradient-to-br from-orange-50 to-pink-50 dark:from-orange-900/20 dark:to-pink-900/20 rounded-2xl p-8 shadow-sm border-2 border-orange-200 dark:border-orange-800">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Gestion Multi-Personnes</h2>
                            <span class="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">ADD-ON</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Gérez une équipe et répartissez les rendez-vous</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Gestion d'équipe</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Ajoutez des membres à votre équipe avec gestion des rôles (administrateur, membre).</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Disponibilités par membre</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Chaque membre a ses propres horaires et disponibilités, avec gestion des indisponibilités.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Assignation automatique</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les réservations peuvent être assignées automatiquement ou manuellement à un membre.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Statistiques individuelles</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suivez les performances de chaque membre avec statistiques détaillées.</p>
                    </div>
                    <div class="p-6 bg-white/70 dark:bg-slate-800/70 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Invitations sécurisées</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Invitez vos collaborateurs par email avec liens sécurisés et gestion des accès.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- E. Messagerie Intégrée -->
        <section id="messagerie" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Messagerie Intégrée</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Communiquez directement avec vos clients</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Conversations dédiées</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Une conversation par client avec historique complet des échanges et contexte.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Messages texte et images</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Envoyez des messages texte ou partagez des images pour mieux communiquer.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Propositions de rendez-vous</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Proposez directement des créneaux depuis la messagerie avec vue du calendrier.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Négociation de prix</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Négociez les prix directement dans la conversation et convertissez en réservation.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Commandes de produits</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les clients peuvent commander vos produits directement depuis la messagerie.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Demandes de services</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Gérez les demandes de services via messagerie avant de créer la réservation.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Notifications en temps réel</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Soyez alerté immédiatement des nouveaux messages et demandes.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- F. Gestion Financière -->
        <section id="finances" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Gestion Financière</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Suivez vos finances et générez vos factures</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Factures automatiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Génération automatique de factures PDF pour chaque réservation payée.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Factures groupées</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez des factures groupées pour plusieurs réservations d'un même client.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Système comptable</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suivez vos recettes et dépenses avec catégorisation et historique complet.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Paramètres fiscaux</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Calcul automatique de l'impôt avec prise en compte de votre situation familiale.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Rapports financiers</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Exportez vos rapports en PDF ou Excel pour votre comptabilité.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Historique des paiements</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suivez tous vos paiements avec dates, montants et méthodes de paiement.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- G. Stocks & Produits -->
        <section id="stocks-produits" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Stocks & Produits</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Gérez votre catalogue et vos stocks</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Catalogue complet</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez et gérez votre catalogue de produits avec descriptions détaillées.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Images produits</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Ajoutez plusieurs images par produit avec image de couverture.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Gestion des stocks</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suivez vos stocks en temps réel avec alertes de réapprovisionnement.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Promotions</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez des promotions temporaires avec dates de début et de fin.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Commandes via messagerie</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les clients peuvent commander vos produits directement depuis la messagerie.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Prix négociables</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Activez la négociation de prix pour certains produits ou services.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- H. Programme de Fidélité -->
        <section id="fidelite" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Programme de Fidélité</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Fidélisez vos clients automatiquement</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Points automatiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">1 point de fidélité par euro dépensé, attribué automatiquement après paiement.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Niveaux de fidélité</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Bronze, Silver, Gold, Platinum, VIP - Plus vos clients dépensent, plus ils montent de niveau.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Historique complet</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suivez toutes les transactions de points avec historique détaillé par client.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Suivi par entreprise</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Chaque entreprise gère son propre programme de fidélité indépendamment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- I. Sécurité des Comptes -->
        <section id="securite" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Sécurité des Comptes</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Protégez vos données et votre compte</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Authentification à deux facteurs</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">A2F par email ou SMS pour sécuriser vos connexions.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Google Authenticator</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Support TOTP avec Google Authenticator et codes de récupération.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Appareils de confiance</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Marquez vos appareils comme de confiance pour éviter les vérifications répétées.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Logs de sécurité</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Historique complet de toutes les actions de sécurité sur votre compte.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Vérification d'email</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Vérification obligatoire de l'adresse email pour sécuriser votre compte.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Gestion des sessions</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Visualisez et gérez toutes vos sessions actives pour une sécurité maximale.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- J. Avis & Réputation -->
        <section id="avis" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Avis & Réputation</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Construisez votre réputation en ligne</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Système d'avis clients</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les clients peuvent laisser des avis après chaque réservation terminée.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Notation 5 étoiles</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Système de notation simple avec 5 étoiles et commentaires détaillés.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Affichage public</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Vos avis sont visibles publiquement sur votre page d'entreprise.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Modération</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Les avis sont vérifiés et approuvés avant publication pour maintenir la qualité.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Note moyenne automatique</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Calcul automatique de votre note moyenne affichée sur votre profil.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- K. Recherche & Découvrabilité -->
        <section id="recherche" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Recherche & Découvrabilité</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Trouvez et soyez trouvé facilement</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Moteur de recherche avancé</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Recherche par nom, ville, type de service, mots-clés avec résultats pertinents.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Autocomplétion intelligente</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Suggestions en temps réel lors de la saisie pour une recherche rapide.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Page publique</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Votre entreprise accessible publiquement sur /p/{slug} avec toutes les informations.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">SEO optimisé</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Optimisation automatique pour les moteurs de recherche (Google, etc.).</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- L. Rapports & Analytics -->
        <section id="rapports" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Rapports & Analytics</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Analysez votre performance et prenez de meilleures décisions</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Dashboard avec statistiques</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Vue d'ensemble avec KPIs, graphiques et indicateurs en temps réel.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Rapports de réservations</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Analysez vos réservations par période, service, membre avec filtres avancés.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Rapports financiers</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Revenus, dépenses, profits avec analyses par période et catégories.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Export de données</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Exportez vos données en CSV ou PDF pour analyses externes.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Statistiques par période</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Comparez vos performances jour/jour, semaine/semaine, mois/mois.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- M. Support & Tickets -->
        <section id="support" class="scroll-mt-32">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Support & Tickets</h2>
                        <p class="text-slate-600 dark:text-slate-400 mt-1">Obtenez de l'aide quand vous en avez besoin</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Système de tickets intégré</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Créez des tickets de support directement depuis votre dashboard.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Support client direct</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Échangez avec notre équipe via messages pour résoudre vos problèmes.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Historique des demandes</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Retrouvez l'historique complet de tous vos tickets et échanges.</p>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h3 class="font-semibold text-lg text-slate-900 dark:text-white mb-2">Réponses organisées</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm">Conversations structurées avec suivi des statuts et priorités.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="bg-gradient-to-r from-green-600 to-orange-500 rounded-2xl p-12 text-center">
            <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                Prêt à transformer votre activité ?
            </h2>
            <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto">
                Rejoignez des centaines d'entrepreneurs qui font confiance à Allo Tata pour gérer leur entreprise.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-white text-green-600 font-bold rounded-xl shadow-2xl hover:shadow-3xl transition-all transform hover:-translate-y-1 hover:scale-105">
                        Accéder au dashboard
                    </a>
                @else
                    <a href="{{ route('signup') }}" class="px-8 py-4 bg-white text-green-600 font-bold rounded-xl shadow-2xl hover:shadow-3xl transition-all transform hover:-translate-y-1 hover:scale-105">
                        Créer mon compte gratuitement
                    </a>
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/20 transition">
                        Se connecter
                    </a>
                @endauth
            </div>
        </section>

    </div>
</div>

<!-- Script pour smooth scroll -->
<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
@endsection
