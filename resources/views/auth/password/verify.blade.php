<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Vérifier le code - Allo Tata</title>
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 sm:space-y-8">
            <div>
                <a href="{{ route('home') }}" class="flex justify-center">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </h1>
                </a>
                <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Vérifier le code
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                    Entrez le code à 6 chiffres que vous avez reçu
                </p>
            </div>

            @if(session('status'))
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-sm text-green-800 dark:text-green-400">{{ session('status') }}</p>
                </div>
            @endif

            <form class="mt-6 sm:mt-8 space-y-5 sm:space-y-6" action="{{ route('password.verify-code') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                
                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Code de réinitialisation
                    </label>
                    <input 
                        id="code" 
                        name="code" 
                        type="text" 
                        required 
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 text-center text-2xl font-mono tracking-widest"
                        placeholder="000000"
                        autocomplete="off"
                        inputmode="numeric"
                    >
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Vérifier
                    </button>
                </div>
            </form>

            <div class="text-center space-y-2">
                <a href="{{ route('password.request') }}" class="block text-sm font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                    Demander un nouveau code
                </a>
                <a href="{{ route('login') }}" class="block text-sm font-medium text-slate-600 hover:text-slate-500 dark:text-slate-400">
                    Retour à la connexion
                </a>
            </div>
        </div>
    </body>
</html>
