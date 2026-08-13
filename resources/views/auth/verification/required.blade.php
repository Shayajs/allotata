<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Vérification de l'email - Allo Tata</title>
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
                    Vérification requise
                </h2>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
                <!-- Icône email -->
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>

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

                <div class="text-center space-y-4">
                    <p class="text-base text-slate-700 dark:text-slate-300">
                        Pour accéder à votre compte, vous devez vérifier votre adresse email.
                    </p>

                    @if($user && $email)
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Un email de vérification a été envoyé à :<br>
                            <strong class="text-slate-900 dark:text-white">{{ $email }}</strong>
                        </p>
                    @endif

                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Cliquez sur le lien dans l'email pour vérifier votre compte. Le lien est valide pendant 7 jours.
                    </p>
                </div>

                @if($user)
                    <div class="mt-8 space-y-4">
                        <form action="{{ route('verification.resend') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                                Renvoyer l'email de vérification
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Vous n'avez pas reçu l'email ? Vérifiez votre dossier spam ou
                            </p>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                                    vous connecter avec un autre compte
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mt-8 text-center">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                            Se connecter
                        </a>
                    </div>
                @endif
            </div>

            <div class="text-center text-sm text-slate-600 dark:text-slate-400">
                <p>Une fois votre email vérifié, vous pourrez accéder à toutes les fonctionnalités de votre compte.</p>
            </div>
        </div>
    </body>
</html>
