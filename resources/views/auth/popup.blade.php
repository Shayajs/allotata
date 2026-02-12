<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Allo Tata</title>
    @vite(['resources/css/app.css'])
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased p-4">
    <div class="w-full max-w-sm mx-auto">
        {{-- Logo --}}
        <div class="text-center mb-6">
            @php
                $logoUrl = \App\Helpers\SiteHelper::getLogo('transparent');
                $siteName = \App\Helpers\SiteHelper::getSiteName();
            @endphp
            <a href="{{ route('home') }}" class="block">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 mx-auto mb-3">
                @endif
                <h1 class="text-xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">
                    {{ $siteName }}
                </h1>
            </a>
        </div>

        {{-- Tabs Login / Register --}}
        <div class="flex border-b border-slate-200 dark:border-slate-700 mb-6">
            <button type="button" id="tab-login" onclick="switchTab('login')"
                    class="flex-1 py-2.5 text-sm font-medium text-center border-b-2 transition-colors border-green-500 text-green-600">
                Se connecter
            </button>
            <button type="button" id="tab-register" onclick="switchTab('register')"
                    class="flex-1 py-2.5 text-sm font-medium text-center border-b-2 transition-colors border-transparent text-slate-400 hover:text-slate-600">
                Créer un compte
            </button>
        </div>

        {{-- Messages --}}
        <div id="popup-message" class="hidden mb-4 p-3 rounded-xl text-sm font-medium"></div>

        {{-- Formulaire Login --}}
        <form id="form-login" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" required autocomplete="email"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="votre@email.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mot de passe</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <span class="text-xs text-slate-500">Se souvenir</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-xs text-green-600 hover:underline" target="_blank">
                    Mot de passe oublié ?
                </a>
            </div>
            <button type="submit" id="btn-login"
                    class="w-full py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg">
                Se connecter
            </button>
        </form>

        {{-- Formulaire Register --}}
        <form id="form-register" class="space-y-4 hidden">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom complet</label>
                <input type="text" name="name" required autocomplete="name"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="Jean Dupont">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" required autocomplete="email"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="votre@email.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mot de passe</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="8 caractères minimum">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full px-3 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <button type="submit" id="btn-register"
                    class="w-full py-3 bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold rounded-xl transition-all shadow-lg">
                Créer mon compte
            </button>
        </form>

        {{-- Verification message --}}
        <div id="verification-screen" class="hidden text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Vérifiez votre email</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4" id="verification-text">
                Un email de vérification a été envoyé. Vérifiez votre boîte de réception puis fermez cette fenêtre.
            </p>
            <button type="button" onclick="window.close()" class="px-6 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-xl transition">
                Fermer
            </button>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            Propulsé par <strong>Allo Tata</strong>
        </p>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function switchTab(tab) {
            const loginForm = document.getElementById('form-login');
            const registerForm = document.getElementById('form-register');
            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');
            const msg = document.getElementById('popup-message');
            msg.classList.add('hidden');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLogin.classList.add('border-green-500', 'text-green-600');
                tabLogin.classList.remove('border-transparent', 'text-slate-400');
                tabRegister.classList.remove('border-green-500', 'text-green-600');
                tabRegister.classList.add('border-transparent', 'text-slate-400');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabRegister.classList.add('border-green-500', 'text-green-600');
                tabRegister.classList.remove('border-transparent', 'text-slate-400');
                tabLogin.classList.remove('border-green-500', 'text-green-600');
                tabLogin.classList.add('border-transparent', 'text-slate-400');
            }
        }

        function showMessage(text, type) {
            const msg = document.getElementById('popup-message');
            msg.textContent = text;
            msg.className = 'mb-4 p-3 rounded-xl text-sm font-medium';
            if (type === 'error') {
                msg.classList.add('bg-red-50', 'dark:bg-red-900/20', 'text-red-700', 'dark:text-red-400');
            } else if (type === 'success') {
                msg.classList.add('bg-green-50', 'dark:bg-green-900/20', 'text-green-700', 'dark:text-green-400');
            } else {
                msg.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'text-blue-700', 'dark:text-blue-400');
            }
        }

        function showVerification(text) {
            document.getElementById('form-login').classList.add('hidden');
            document.getElementById('form-register').classList.add('hidden');
            document.getElementById('popup-message').classList.add('hidden');
            document.getElementById('verification-screen').classList.remove('hidden');
            if (text) document.getElementById('verification-text').textContent = text;
        }

        // Login
        document.getElementById('form-login').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-login');
            btn.disabled = true;
            btn.textContent = 'Connexion...';

            const fd = new FormData(this);
            try {
                const res = await fetch('{{ route("auth.popup.login") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(fd)),
                    credentials: 'same-origin',
                });
                const data = await res.json();

                if (data.success) {
                    // Connexion réussie → envoyer au parent
                    if (window.opener) {
                        window.opener.postMessage({ type: 'auth_success', user: data.user }, window.location.origin);
                    }
                    window.close();
                } else if (data.needs_verification) {
                    showVerification(data.error || data.message);
                } else if (data.needs_2fa) {
                    // Rediriger vers la page 2FA dans le popup
                    window.location.href = data.redirect;
                } else {
                    showMessage(data.error || 'Erreur de connexion.', 'error');
                }
            } catch (err) {
                showMessage('Erreur réseau. Veuillez réessayer.', 'error');
            }

            btn.disabled = false;
            btn.textContent = 'Se connecter';
        });

        // Register
        document.getElementById('form-register').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-register');
            btn.disabled = true;
            btn.textContent = 'Création...';

            const fd = new FormData(this);
            try {
                const res = await fetch('{{ route("auth.popup.register") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(fd)),
                    credentials: 'same-origin',
                });
                const data = await res.json();

                if (data.success && data.needs_verification) {
                    showVerification(data.message);
                } else if (data.success) {
                    if (window.opener) {
                        window.opener.postMessage({ type: 'auth_success', user: data.user }, window.location.origin);
                    }
                    window.close();
                } else {
                    showMessage(data.error || 'Erreur lors de la création.', 'error');
                }
            } catch (err) {
                // Validation errors (422) from Laravel
                try {
                    const errData = await err.json?.() || {};
                    const messages = errData.errors ? Object.values(errData.errors).flat().join(' ') : 'Erreur de validation.';
                    showMessage(messages, 'error');
                } catch (_) {
                    showMessage('Erreur réseau. Veuillez réessayer.', 'error');
                }
            }

            btn.disabled = false;
            btn.textContent = 'Créer mon compte';
        });

        // Init : mode par défaut
        @if($mode === 'register')
            switchTab('register');
        @endif
    </script>
</body>
</html>
