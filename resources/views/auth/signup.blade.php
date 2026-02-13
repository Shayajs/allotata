<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Inscription - Allo Tata</title>
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200 min-h-screen flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 sm:space-y-8">
            <div>
                <a href="{{ route('home') }}" class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    @php
                        use App\Helpers\SiteHelper;
                        $logoUrl = SiteHelper::getLogo('transparent');
                        $siteName = SiteHelper::getSiteName();
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-12 w-auto sm:h-10">
                    @endif
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        {{ $siteName }}
                    </h1>
                </a>
                <h2 class="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Créer un compte
                </h2>
                <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                    Ou
                    <a href="{{ route('login') }}" class="font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                        connectez-vous à votre compte existant
                    </a>
                </p>
            </div>
            <form class="mt-6 sm:mt-8 space-y-5 sm:space-y-6 mobile-form" action="{{ route('register') }}" method="POST">
                @csrf
                <div class="rounded-md shadow-sm space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Prénom *
                            </label>
                            <input 
                                id="name" 
                                name="name" 
                                type="text" 
                                required 
                                value="{{ old('name') }}"
                                class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                                placeholder="Votre prénom"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="surname" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Nom de famille
                            </label>
                            <input 
                                id="surname" 
                                name="surname" 
                                type="text" 
                                value="{{ old('surname') }}"
                                class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                                placeholder="Votre nom de famille"
                            >
                            @error('surname')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Adresse email
                        </label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            required 
                            value="{{ old('email', isset($invitation) && $invitation ? $invitation->email : '') }}"
                            {{ isset($invitation) && $invitation ? 'readonly' : '' }}
                            class="appearance-none relative block w-full px-3 py-3 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 {{ isset($invitation) && $invitation ? 'bg-slate-100 dark:bg-slate-700' : '' }} touch-target"
                            placeholder="votre@email.com"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                class="appearance-none relative block w-full px-3 py-3 pr-12 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                                placeholder="••••••••"
                            >
                            <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Confirmer le mot de passe
                        </label>
                        <div class="relative">
                            <input 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                type="password" 
                                required 
                                class="appearance-none relative block w-full px-3 py-3 pr-12 text-base border border-slate-300 dark:border-slate-600 placeholder-slate-500 dark:placeholder-slate-400 text-slate-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-slate-800 touch-target"
                                placeholder="••••••••"
                            >
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Afficher le mot de passe">
                                <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path></svg>
                                <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mention CGU / CGV -->
                <p class="text-xs text-slate-500 dark:text-slate-400 text-center">
                    En créant un compte, vous acceptez nos 
                    <a href="{{ route('legal.cgu') }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">Conditions Générales d'Utilisation</a> 
                    et nos 
                    <a href="{{ route('legal.cgv') }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">Conditions Générales de Vente</a>.
                </p>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all touch-target"
                    >
                        Créer mon compte
                    </button>
                </div>
            </form>
        </div>
        <script>
            function togglePasswordVisibility(inputId, btn) {
                const input = document.getElementById(inputId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.querySelector('.eye-off').classList.toggle('hidden', isPassword);
                btn.querySelector('.eye-on').classList.toggle('hidden', !isPassword);
                btn.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            }
        </script>
    </body>
</html>

