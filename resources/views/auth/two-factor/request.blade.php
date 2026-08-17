<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Vérification en deux étapes - Allo Tata</title>
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8{{ !empty($isCapacitor) ? ' android-auth-page' : '' }}">
        @if(!empty($isCapacitor))
            @include('partials.android-auth-brand')
        @endif
        <div class="max-w-md w-full space-y-6 sm:space-y-8">
            <div>
                <a href="{{ $brandUrl ?? route('home') }}" class="android-auth-web-brand flex justify-center">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </h1>
                </a>
                <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Vérification en deux étapes
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                    @if(isset($hasGoogle2fa) && $hasGoogle2fa)
                        Entrez le code TOTP depuis votre application d'authentification
                    @else
                        Entrez le code de vérification pour finaliser votre connexion
                    @endif
                </p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
                @if(session('status'))
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-sm text-green-800 dark:text-green-400">{{ session('status') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        @foreach($errors->all() as $error)
                            <p class="text-sm text-red-800 dark:text-red-400">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if(isset($hasGoogle2fa) && $hasGoogle2fa)
                    <!-- Mode TOTP (Google Authenticator) -->
                    <div class="mb-6">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 text-center">
                            Ouvrez votre application d'authentification (Google Authenticator, Microsoft Authenticator, etc.) et entrez le code à 6 chiffres affiché.
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 text-center">
                            Vous pouvez également utiliser un code de récupération si vous avez perdu l'accès à votre application.
                        </p>
                    </div>
                @else
                    <!-- Boutons pour choisir la méthode Email/SMS -->
                    <div class="mb-6">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Recevoir le code par :</p>
                        <div class="flex gap-3">
                            <form action="{{ route('two-factor.request') }}" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="method" value="email">
                                <button type="submit" class="w-full px-4 py-2 text-sm font-medium rounded-lg border transition
                                    {{ $method === 'email' ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700 text-green-700 dark:text-green-400' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                    📧 Email
                                </button>
                            </form>
                            @if($user->telephone)
                                <form action="{{ route('two-factor.request') }}" method="POST" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="method" value="sms">
                                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium rounded-lg border transition
                                        {{ $method === 'sms' ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700 text-green-700 dark:text-green-400' : 'border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                        📱 SMS
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Formulaire de saisie du code -->
                <form action="{{ route('two-factor.verify') }}" method="POST">
                    @csrf
                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            @if(isset($hasGoogle2fa) && $hasGoogle2fa)
                                Code TOTP ou code de récupération
                            @else
                                Code de vérification
                            @endif
                        </label>
                        <input 
                            id="code" 
                            name="code" 
                            type="text" 
                            required 
                            maxlength="8"
                            pattern="[0-9A-Za-z]{6,8}"
                            class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 text-center text-2xl font-mono tracking-widest"
                            placeholder="{{ isset($hasGoogle2fa) && $hasGoogle2fa ? '000000 ou CODE' : '000000' }}"
                            autocomplete="off"
                            inputmode="numeric"
                            autofocus
                        >
                        @if(isset($hasGoogle2fa) && $hasGoogle2fa)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 text-center">
                                Le code peut contenir des lettres si c'est un code de récupération (format: A1B2C3)
                            </p>
                        @endif
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Vérifier et se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-500 dark:text-slate-400">
                        Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
