<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation - {{ $invitation->entreprise->nom }} - Allo Tata</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-script')
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
            <!-- Logo/Header -->
            <div class="text-center mb-6">
                <a href="{{ route('home') }}" class="inline-block">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                        Allo Tata
                    </h1>
                </a>
            </div>

            <!-- Informations de l'invitation -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-r from-green-500 to-orange-500 flex items-center justify-center text-white text-2xl font-bold">
                    {{ strtoupper(substr($invitation->entreprise->nom, 0, 1)) }}
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                    Invitation à rejoindre
                </h2>
                <h3 class="text-xl font-semibold text-green-600 dark:text-green-400 mb-4">
                    {{ $invitation->entreprise->nom }}
                </h3>
                <p class="text-slate-600 dark:text-slate-400">
                    Vous avez été invité(e) à rejoindre cette entreprise en tant que <span class="font-semibold capitalize">{{ $invitation->role }}</span>.
                </p>
            </div>

            <!-- Détails -->
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 mb-6">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Email :</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $invitation->email }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Rôle :</span>
                        <span class="font-medium capitalize text-slate-900 dark:text-white">{{ $invitation->role }}</span>
                    </div>
                    @if($invitation->invitePar)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Invité(e) par :</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $invitation->invitePar->name }}</span>
                        </div>
                    @endif
                    @if($invitation->expire_at)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Expire le :</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $invitation->expire_at->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            @if($invitation->estEnAttenteAcceptation())
                @auth
                    @if($invitation->user_id === Auth::id())
                        <form action="{{ route('invitations.accepter', $invitation->token) }}" method="POST" class="mb-3">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition shadow-lg hover:shadow-xl">
                                ✅ Accepter l'invitation
                            </button>
                        </form>
                        <form action="{{ route('invitations.refuser', $invitation->token) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition">
                                ❌ Refuser l'invitation
                            </button>
                        </form>
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-center">
                            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                Cette invitation ne vous est pas destinée.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="space-y-3">
                        <a href="{{ route('login') }}" class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-center shadow-lg hover:shadow-xl">
                            Se connecter pour accepter
                        </a>
                        <p class="text-xs text-center text-slate-500 dark:text-slate-400">
                            Vous devez être connecté(e) avec le compte {{ $invitation->email }} pour accepter cette invitation.
                        </p>
                    </div>
                @endauth
            @elseif($invitation->estEnAttenteCompte())
                <div class="space-y-3">
                    <a href="{{ route('signup', ['invitation' => $invitation->token]) }}" class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg transition text-center shadow-lg hover:shadow-xl">
                        Créer un compte pour accepter
                    </a>
                    <p class="text-xs text-center text-slate-500 dark:text-slate-400">
                        Créez un compte avec l'email {{ $invitation->email }} pour rejoindre cette entreprise.
                    </p>
                </div>
            @endif

            <!-- Retour -->
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-green-600 dark:hover:text-green-400 transition">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
