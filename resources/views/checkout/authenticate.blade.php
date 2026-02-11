<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="stripe-publishable-key" content="{{ config('services.stripe.key') }}">
        <title>Finaliser votre paiement – Allo Tata</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-script')
        @include('partials.favicon')
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold bg-gradient-to-r from-green-500 to-orange-500 bg-clip-text text-transparent">Allo Tata</a>
                    <a href="{{ route('checkout.index') }}" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-green-600 dark:hover:text-green-400 transition">Retour au paiement</a>
                </div>
            </div>
        </nav>

        <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/20 mb-4">
                        <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">
                        Authentification requise
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400">
                        Votre banque demande une confirmation pour finaliser le paiement de <strong class="text-slate-900 dark:text-white">{{ number_format($echeance->montant_final ?? $echeance->montant_du ?? 0, 2, ',', ' ') }} €</strong>
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-500 mt-2">
                        {{ $echeance->libelle() }} – {{ $echeance->periode_debut->format('d/m/Y') }} au {{ $echeance->periode_fin->format('d/m/Y') }}
                    </p>
                </div>

                <div id="authenticate-container" class="mt-8">
                    <div class="flex items-center justify-center gap-3 py-8 text-slate-500 dark:text-slate-400">
                        <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Chargement de l'authentification...</span>
                    </div>
                </div>

                <div id="authenticate-error" class="hidden mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-400 text-sm"></p>
                </div>
            </div>
        </main>

        <script type="module">
            import { loadStripe } from '@stripe/stripe-js';

            const stripePk = document.querySelector('meta[name="stripe-publishable-key"]')?.getAttribute('content');
            const clientSecret = @json($client_secret);
            const paymentIntentId = @json($payment_intent_id);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            async function initAuthentication() {
                if (!stripePk || !clientSecret) {
                    showError('Configuration de paiement invalide.');
                    return;
                }

                const stripe = await loadStripe(stripePk);
                if (!stripe) {
                    showError('Impossible de charger Stripe.');
                    return;
                }

                // Lancer automatiquement l'authentification 3DS
                const { error } = await stripe.handleCardAction(clientSecret);

                if (error) {
                    showError(error.message || 'Authentification échouée.');
                    return;
                }

                // Authentification réussie, confirmer le statut côté serveur
                try {
                    const response = await fetch('{{ route("checkout.confirm-status") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ payment_intent_id: paymentIntentId }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Rediriger vers le checkout avec un message de succès
                        window.location.href = '{{ route("checkout.index") }}?authenticated=1';
                    } else {
                        showError(data.error || 'Paiement non confirmé.');
                    }
                } catch (err) {
                    showError('Erreur lors de la confirmation. Réessayez.');
                }
            }

            function showError(message) {
                const container = document.getElementById('authenticate-container');
                const errorEl = document.getElementById('authenticate-error');
                if (container) container.innerHTML = '';
                if (errorEl) {
                    errorEl.querySelector('p').textContent = message;
                    errorEl.classList.remove('hidden');
                }
            }

            // Lancer l'authentification au chargement
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAuthentication);
            } else {
                initAuthentication();
            }
        </script>
    </body>
</html>
