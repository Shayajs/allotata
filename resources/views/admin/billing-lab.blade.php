@extends('admin.layout')

@section('title', 'Labo facturation')
@section('header', 'Laboratoire de facturation')
@section('subheader', 'Rejouer un mois de paiements sans attendre — preuves, pas des impressions')

@section('content')
<div x-data="billingLab()" x-cloak>
    @if($liveBlocked)
        <div class="mb-6 rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800 px-4 py-3 text-red-800 dark:text-red-200 text-sm">
            Clé Stripe <strong>LIVE</strong> détectée. Le labo refuse tout appel Stripe. Passe en <code>sk_test_</code> pour les scénarios horloge réelle.
        </div>
    @endif

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Mode</p>
            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $mode }}</p>
            <p class="text-sm text-slate-500">Stripe test {{ $canCallStripe ? 'disponible' : 'indisponible' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Double moteur ce mois</p>
            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $evidence['dual_engine_this_month']['count'] ?? 'n/a' }}</p>
            <p class="text-sm text-slate-500">Users Cashier actif + échéance Premium payée</p>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">jour_facturation</p>
            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                null {{ $evidence['jour_facturation']['null'] ?? 0 }}
                · jour 1 {{ $evidence['jour_facturation']['day_1'] ?? 0 }}
                · autres {{ $evidence['jour_facturation']['other'] ?? 0 }}
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" x-model="allowLive" class="rounded border-slate-300" @if(!$canCallStripe) disabled @endif>
            Inclure Stripe test réel (PI + Test Clock)
        </label>
        <button type="button" @click="runAll()" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-sm">
            Lancer toute la matrice
        </button>
        <button type="button" @click="cleanup()" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-semibold rounded-lg text-sm border border-red-200 dark:border-red-800">
            Nettoyer les fixtures
        </button>
        <span class="ml-auto text-sm text-slate-500" x-text="summaryLabel()"></span>
    </div>

    @foreach($catalog as $group => $scenarios)
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-2 mt-6">{{ $group }}</h2>
        <div class="grid gap-3 mb-4">
            @foreach($scenarios as $scenario)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                     :class="cardClass('{{ $scenario['id'] }}')">
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $scenario['label'] }}</p>
                            <p class="text-xs text-slate-500">{{ $scenario['id'] }}@if($scenario['requires_stripe_live']) · Stripe test @endif</p>
                            <p class="text-sm mt-1" x-text="results['{{ $scenario['id'] }}']?.message || ''"></p>
                        </div>
                        <button type="button" @click="runOne('{{ $scenario['id'] }}')" class="px-3 py-1.5 text-sm rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600">
                            Lancer
                        </button>
                    </div>
                    <pre class="px-5 pb-4 text-xs text-slate-600 dark:text-slate-300 overflow-x-auto" x-show="results['{{ $scenario['id'] }}']?.details" x-text="detailsText('{{ $scenario['id'] }}')"></pre>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="mt-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-2">Lecture seule — base courante</h2>
        <pre class="text-xs overflow-x-auto text-slate-600 dark:text-slate-300">{{ json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <p class="mt-3 text-sm text-slate-500">
            Prod <code>51.38.36.52</code> : ouvrir cette page sur le serveur, ou
            <code>php artisan billing-lab:run --all --json</code>. Aucune charge live.
        </p>
    </div>
</div>

@push('scripts')
<script>
function billingLab() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return {
        allowLive: false,
        results: {},
        running: false,
        async runOne(id) {
            this.running = true;
            const res = await fetch('{{ route("admin.billing-lab.run") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ scenario: id, allow_live: this.allowLive }),
            });
            this.results[id] = await res.json();
            this.running = false;
        },
        async runAll() {
            this.running = true;
            const res = await fetch('{{ route("admin.billing-lab.run-all") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ allow_live: this.allowLive }),
            });
            const data = await res.json();
            (data.results || []).forEach((row) => { this.results[row.id] = row; });
            this.running = false;
        },
        async cleanup() {
            await fetch('{{ route("admin.billing-lab.cleanup") }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
        },
        cardClass(id) {
            const s = this.results[id]?.status;
            if (s === 'pass' || s === 'evidence_safe') return 'ring-1 ring-green-200 dark:ring-green-800';
            if (s === 'evidence_risk') return 'ring-1 ring-amber-300 dark:ring-amber-700';
            if (s === 'fail') return 'ring-1 ring-red-200 dark:ring-red-800';
            return '';
        },
        detailsText(id) {
            const d = this.results[id];
            if (!d) return '';
            return JSON.stringify({ findings: d.findings, details: d.details }, null, 2);
        },
        summaryLabel() {
            const rows = Object.values(this.results);
            if (!rows.length) return '';
            const fail = rows.filter(r => r.status === 'fail').length;
            const risk = rows.filter(r => r.status === 'evidence_risk').length;
            return rows.length + ' scénario(s) · ' + fail + ' fail · ' + risk + ' preuve(s) à risque';
        },
    };
}
</script>
@endpush
@endsection
