<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contacter le support - Allo Tata</title>
    <meta name="description" content="Écrivez à l'équipe Allo Tata : une question, un problème, une demande de devis ou de partenariat.">
    @include('partials.canonical')
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    @include('partials.super-user-banner')

    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="text-2xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    Allo Tata
                </a>
                <a href="{{ route('support.faq') }}" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">
                    FAQ
                </a>
            </div>
        </div>
    </nav>

    <div class="pt-24 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Contacter le support</h1>
                <p class="mt-3 text-slate-600 dark:text-slate-400">
                    Une question, un souci, une idée : écrivez-nous. Pour un problème lié à votre compte,
                    <a href="{{ route('tickets.create') }}" class="text-green-600 dark:text-green-400 hover:underline">un ticket</a>
                    permet de suivre l'échange dans le temps.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <p class="text-green-800 dark:text-green-400">{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    @foreach($errors->all() as $error)
                        <p class="text-red-800 dark:text-red-400">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 space-y-5">
                @csrf

                <div>
                    <label for="nom" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Votre nom
                    </label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="{{ old('nom', auth()->user()->name ?? '') }}"
                        required
                        maxlength="255"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Votre email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', auth()->user()->email ?? '') }}"
                        required
                        maxlength="255"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">C'est à cette adresse que nous répondrons.</p>
                </div>

                <div>
                    <label for="sujet" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Sujet
                    </label>
                    <input
                        type="text"
                        id="sujet"
                        name="sujet"
                        value="{{ old('sujet') }}"
                        required
                        maxlength="255"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Votre message
                    </label>
                    <textarea
                        id="message"
                        name="message"
                        rows="7"
                        required
                        maxlength="5000"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-xl transition">
                    Envoyer le message
                </button>
            </form>
        </div>
    </div>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
