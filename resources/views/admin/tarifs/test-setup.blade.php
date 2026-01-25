@extends('admin.layout')

@section('title', 'Test Setup Stripe')
@section('header', 'Test Setup')
@section('subheader', 'Enregistrer une carte (Setup Intent) sans débiter. Si OK, le débit API fonctionnera.')

@push('head-scripts')
    <meta name="stripe-publishable-key" content="{{ config('services.stripe.key') }}">
@endpush

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 p-4 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
        <p class="text-sm text-slate-700 dark:text-slate-300">
            <strong>Setup Intent</strong> : on enregistre la carte sans aucun débit. Idéal pour les <strong>X jours gratuits</strong> (carte enregistrée à l’inscription, débit après l’essai). Si le Setup fonctionne, le paiement fonctionnera au niveau API.
        </p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Enregistrer une carte de test</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Utilisez une carte test Stripe (ex. <code class="px-1 py-0.5 bg-slate-200 dark:bg-slate-600 rounded">4242 4242 4242 4242</code>). Aucun débit.</p>

        <form id="admin-test-setup-form">
            <div id="admin-test-setup-element" class="min-h-[200px] mb-4"></div>
            <p id="admin-test-setup-error" class="mb-4 text-sm text-red-600 dark:text-red-400" role="alert"></p>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition">
                    Enregistrer la carte (test)
                </button>
                <a href="{{ route('admin.stripe-prices.index') }}" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-600 hover:bg-slate-300 dark:hover:bg-slate-500 text-slate-800 dark:text-slate-200 font-medium rounded-xl transition">
                    Retour aux Tarifs
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="{{ asset('js/admin-test-setup.js') }}"></script>
@endpush
