@extends('brightshell.layout')

@section('title', 'Statistiques')

@section('content')
<!-- Compteurs -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Clients</div>
        <div class="stat-value text-accent">{{ $stats['nb_clients'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Devis {{ date('Y') }}</div>
        <div class="stat-value">{{ $stats['nb_devis'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Factures {{ date('Y') }}</div>
        <div class="stat-value">{{ $stats['nb_factures'] ?? 0 }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Projets en cours</div>
        <div class="stat-value text-warning">{{ $stats['nb_projets'] ?? 0 }}</div>
    </div>
</div>

<!-- Alertes paiements -->
@if(($stats['total_a_recevoir'] ?? 0) > 0 || ($stats['montant_impaye'] ?? 0) > 0)
<div class="stats-grid mt-4">
    @if(($stats['total_a_recevoir'] ?? 0) > 0)
    <div class="stat-card" style="border-left: 4px solid var(--bs-info);">
        <div class="stat-label">Échéances attendues</div>
        <div class="stat-value text-info">{{ number_format($stats['total_a_recevoir'], 2, ',', ' ') }} €</div>
        <div class="text-muted text-xs">Paiements échelonnés en cours</div>
    </div>
    @endif
    @if(($stats['montant_impaye'] ?? 0) > 0)
    <div class="stat-card" style="border-left: 4px solid var(--bs-warning);">
        <div class="stat-label">Factures impayées</div>
        <div class="stat-value text-warning">{{ number_format($stats['montant_impaye'], 2, ',', ' ') }} €</div>
        <div class="text-muted text-xs">{{ $stats['factures_impayees'] ?? 0 }} facture(s)</div>
    </div>
    @endif
</div>
@endif

<div class="grid grid-2 mt-4" style="gap: 2rem;">
    <!-- CA par mois -->
    <div class="card">
        <h3 class="card-title mb-4">Chiffre d'affaires par mois ({{ date('Y') }})</h3>
        @php
            $caParMois = $stats['ca_par_mois'] ?? [];
            $maxCa = max(array_values($caParMois)) ?: 1;
            $mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            $totalAnnuel = array_sum($caParMois);
        @endphp
        <div style="display: flex; align-items: flex-end; gap: 0.5rem; height: 200px; padding-bottom: 2rem;">
            @foreach($caParMois as $m => $montant)
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                <div style="width: 100%; background: linear-gradient(180deg, var(--bs-accent), rgba(91, 188, 228, 0.5)); border-radius: 4px 4px 0 0; height: {{ $maxCa > 0 ? ($montant / $maxCa) * 150 : 0 }}px; min-height: 4px;"></div>
                <span class="text-xs text-muted" style="margin-top: 0.5rem;">{{ $mois[$m - 1] }}</span>
            </div>
            @endforeach
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--bs-border); padding-top: 1rem; margin-top: 1rem;">
            <span class="text-muted">Total annuel</span>
            <span class="text-accent font-bold" style="font-size: 1.25rem;">{{ number_format($totalAnnuel, 2, ',', ' ') }} €</span>
        </div>
    </div>

    <!-- Paiements attendus par mois -->
    <div class="card">
        <h3 class="card-title mb-4">Paiements attendus (6 prochains mois)</h3>
        @php
            $paiementsParMois = $stats['paiements_par_mois'] ?? [];
            $maxPaiement = count($paiementsParMois) > 0 ? max(array_values($paiementsParMois)) : 1;
            $moisNoms = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Jun', 
                         '07' => 'Jul', '08' => 'Aoû', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];
        @endphp
        
        @if(count($paiementsParMois) > 0)
        <div style="display: flex; align-items: flex-end; gap: 1rem; height: 200px; padding-bottom: 2rem;">
            @foreach($paiementsParMois as $moisKey => $total)
            @php
                $parts = explode('-', $moisKey);
                $moisNom = $moisNoms[$parts[1]] ?? $parts[1];
            @endphp
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                <span class="text-xs text-success mb-1">{{ number_format($total, 0, ',', ' ') }} €</span>
                <div style="width: 100%; background: linear-gradient(180deg, #10b981, rgba(16, 185, 129, 0.5)); border-radius: 4px 4px 0 0; height: {{ $maxPaiement > 0 ? ($total / $maxPaiement) * 120 : 0 }}px; min-height: 4px;"></div>
                <span class="text-xs text-muted" style="margin-top: 0.5rem;">{{ $moisNom }}</span>
            </div>
            @endforeach
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--bs-border); padding-top: 1rem; margin-top: 1rem;">
            <span class="text-muted">Total attendu</span>
            <span class="text-success font-bold" style="font-size: 1.25rem;">{{ number_format(array_sum($paiementsParMois), 2, ',', ' ') }} €</span>
        </div>
        @else
        <div class="empty-state" style="padding: 2rem 0;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>Aucun paiement échelonné en attente.</p>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap: 2rem;">
    <!-- Top clients -->
    <div class="card">
        <h3 class="card-title mb-4">Top 5 Clients</h3>
        @if(!empty($stats['top_clients']) && count($stats['top_clients']) > 0)
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($stats['top_clients'] as $i => $client)
            @php
                $maxTotal = $stats['top_clients'][0]->total ?? 1;
                $pct = ($client->total / $maxTotal) * 100;
            @endphp
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span class="font-bold">{{ $i + 1 }}. {{ $client->societe ?? $client->nom }}</span>
                    <span class="text-accent">{{ number_format($client->total, 2, ',', ' ') }} €</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: {{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted text-center">Pas encore de données clients.</p>
        @endif
    </div>

    <!-- Prochaines échéances -->
    <div class="card">
        <h3 class="card-title mb-4">Prochaines échéances à recevoir</h3>
        @php
            $prochainsPaiements = $stats['prochains_paiements'] ?? [];
            $prochainsPaiementsLimites = array_slice($prochainsPaiements, 0, 8);
        @endphp
        
        @if(count($prochainsPaiementsLimites) > 0)
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Facture</th>
                    <th>Date</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prochainsPaiementsLimites as $echeance)
                <tr>
                    <td>{{ $echeance->client_societe ?? $echeance->client_nom ?? '-' }}</td>
                    <td>{{ $echeance->facture_numero ?? '-' }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($echeance->date_echeance)->format('d/m/Y') }}
                        @if(\Carbon\Carbon::parse($echeance->date_echeance)->isPast())
                        <span class="badge badge-danger">En retard</span>
                        @elseif(\Carbon\Carbon::parse($echeance->date_echeance)->isToday())
                        <span class="badge badge-warning">Aujourd'hui</span>
                        @elseif(\Carbon\Carbon::parse($echeance->date_echeance)->diffInDays(now()) <= 7)
                        <span class="badge badge-info">Cette semaine</span>
                        @endif
                    </td>
                    <td style="font-weight: 600;">{{ number_format($echeance->montant, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($prochainsPaiements) > 8)
        <p class="text-muted text-center text-xs mt-2">Et {{ count($prochainsPaiements) - 8 }} autres échéances...</p>
        @endif
        @else
        <div class="empty-state" style="padding: 2rem 0;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>Toutes les échéances ont été payées.</p>
        </div>
        @endif
    </div>
</div>
@endsection
