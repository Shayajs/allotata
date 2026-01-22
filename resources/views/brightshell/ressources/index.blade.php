@extends('brightshell.layout')

@section('title', 'Ressources & Trésorerie')

@section('actions')
<form action="{{ route('brightshell.ressources') }}" method="GET" class="flex gap-2">
    <select name="annee" class="form-input" style="width: auto;" onchange="this.form.submit()">
        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
        <option value="{{ $y }}" {{ (int) $year === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
</form>
<a href="{{ route('brightshell.achats.create') }}" class="btn btn-secondary">+ Achat</a>
@endsection

@section('content')
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-label">Monnaie (solde)</div>
        @php $solde = $tresorerie?->solde_courant ?? 0; @endphp
        <div class="stat-value {{ $solde >= 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format($solde, 2, ',', ' ') }} €
        </div>
        @if($tresorerie && $tresorerie->date_maj)
        <div class="text-xs text-muted">MAJ {{ \Carbon\Carbon::parse($tresorerie->date_maj)->format('d/m H:i') }}</div>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Entrées {{ $year }}</div>
        <div class="stat-value text-success">+{{ number_format($totalEntrees, 2, ',', ' ') }} €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sorties {{ $year }}</div>
        <div class="stat-value text-danger">-{{ number_format($totalSorties, 2, ',', ' ') }} €</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">À garder de côté</div>
        <div class="stat-value text-warning">{{ number_format($totalReserves, 2, ',', ' ') }} €</div>
    </div>
</div>

{{-- Monnaie --}}
@if($tresorerie)
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Monnaie (trésorerie courante)</h3>
    </div>
    <div style="padding: 1.5rem;">
        <form action="{{ route('brightshell.ressources.tresorerie') }}" method="POST" class="flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
            @csrf
            <div class="form-group" style="margin-bottom: 0; max-width: 200px;">
                <label class="form-label">Solde (€)</label>
                <input type="number" name="solde_courant" class="form-input" step="0.01" value="{{ $tresorerie->solde_courant }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    </div>
</div>
@endif

{{-- Entrées --}}
<div class="card mb-4">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <h3 class="card-title">Entrées {{ $year }}</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('form-entree').classList.toggle('d-none')">+ Entrée manuelle</button>
    </div>
    <div id="form-entree" class="d-none" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--bs-border);">
        <form action="{{ route('brightshell.ressources.mouvements.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="entree">
            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Libellé *</label>
                    <input type="text" name="libelle" class="form-input" required placeholder="Ex: Vente formation, Remboursement...">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Montant (€) *</label>
                    <input type="number" name="montant" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Catégorie</label>
                    <input type="text" name="categorie" class="form-input" placeholder="Optionnel">
                </div>
            </div>
            <div class="form-group" style="margin-top: 0.75rem; margin-bottom: 0;">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="Optionnel">
            </div>
            <div class="flex gap-2" style="margin-top: 0.75rem;">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('form-entree').classList.add('d-none')">Annuler</button>
            </div>
        </form>
    </div>
    @php
        $entrees = $recettes->map(fn($r) => (object)['date' => $r->date, 'libelle' => $r->description ?? $r->reference ?? $r->nature ?? '-', 'montant' => $r->montant, 'type' => 'recette'])
            ->concat($mouvements->where('type', 'entree')->map(fn($m) => (object)['date' => $m->date, 'libelle' => $m->libelle, 'montant' => $m->montant, 'type' => 'mouvement', 'id' => $m->id]));
        $entrees = $entrees->sortByDesc('date')->values();
    @endphp
    @if($entrees->isNotEmpty())
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Type</th>
                    <th style="text-align: right;">Montant</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($entrees as $e)
                <tr>
                    <td data-label="Date" class="text-muted">{{ \Carbon\Carbon::parse($e->date)->format('d/m/Y') }}</td>
                    <td data-label="Libellé">{{ Str::limit($e->libelle, 50) }}</td>
                    <td data-label="Type"><span class="badge badge-info">{{ $e->type === 'recette' ? 'Recette' : 'Manuel' }}</span></td>
                    <td data-label="Montant" class="font-bold text-success" style="text-align: right;">+{{ number_format($e->montant, 2, ',', ' ') }} €</td>
                    <td data-label="Actions">
                        @if(isset($e->id))
                        <form action="{{ route('brightshell.ressources.mouvements.delete', $e->id) }}" method="POST" style="display: flex; justify-content: flex-end;" onsubmit="return confirm('Supprimer ce mouvement ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">Suppr.</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucune entrée. Les recettes (factures payées) et les entrées manuelles apparaissent ici.</p>
    @endif
</div>

{{-- Sorties --}}
<div class="card mb-4">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <h3 class="card-title">Sorties {{ $year }}</h3>
        <div class="flex gap-2">
            <a href="{{ route('brightshell.achats.create') }}" class="btn btn-primary btn-sm">+ Achat</a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('form-sortie').classList.toggle('d-none')">+ Sortie manuelle</button>
        </div>
    </div>
    <div id="form-sortie" class="d-none" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--bs-border);">
        <form action="{{ route('brightshell.ressources.mouvements.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="sortie">
            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Libellé *</label>
                    <input type="text" name="libelle" class="form-input" required placeholder="Ex: Frais, Divers...">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Montant (€) *</label>
                    <input type="number" name="montant" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Catégorie</label>
                    <input type="text" name="categorie" class="form-input" placeholder="Optionnel">
                </div>
            </div>
            <div class="form-group" style="margin-top: 0.75rem; margin-bottom: 0;">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="Optionnel">
            </div>
            <div class="flex gap-2" style="margin-top: 0.75rem;">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('form-sortie').classList.add('d-none')">Annuler</button>
            </div>
        </form>
    </div>
    @php
        $sorties = $achats->map(fn($a) => (object)['date' => $a->date, 'libelle' => $a->description, 'fournisseur' => $a->fournisseur ?? null, 'montant' => $a->montant, 'type' => 'achat'])
            ->concat($mouvements->where('type', 'sortie')->map(fn($m) => (object)['date' => $m->date, 'libelle' => $m->libelle, 'fournisseur' => null, 'montant' => $m->montant, 'type' => 'mouvement', 'id' => $m->id]));
        $sorties = $sorties->sortByDesc('date')->values();
    @endphp
    @if($sorties->isNotEmpty())
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Fournisseur</th>
                    <th>Type</th>
                    <th style="text-align: right;">Montant</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sorties as $s)
                <tr>
                    <td data-label="Date" class="text-muted">{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
                    <td data-label="Libellé">{{ Str::limit($s->libelle, 40) }}</td>
                    <td data-label="Fournisseur">{{ $s->fournisseur ?? '-' }}</td>
                    <td data-label="Type"><span class="badge {{ $s->type === 'achat' ? 'badge-info' : 'badge-warning' }}">{{ $s->type === 'achat' ? 'Achat' : 'Manuel' }}</span></td>
                    <td data-label="Montant" class="font-bold text-danger" style="text-align: right;">-{{ number_format($s->montant, 2, ',', ' ') }} €</td>
                    <td data-label="Actions">
                        @if(isset($s->id))
                        <form action="{{ route('brightshell.ressources.mouvements.delete', $s->id) }}" method="POST" style="display: flex; justify-content: flex-end;" onsubmit="return confirm('Supprimer ce mouvement ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">Suppr.</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucune sortie. <a href="{{ route('brightshell.achats.create') }}">Enregistrer un achat</a> ou ajouter une sortie manuelle.</p>
    @endif
</div>

{{-- À garder de côté (réserves) --}}
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">À garder de côté</h3>
    </div>
    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--bs-border);">
        <form action="{{ route('brightshell.ressources.reserves.store') }}" method="POST">
            @csrf
            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Libellé *</label>
                    <input type="text" name="libelle" class="form-input" required placeholder="Ex: URSSAF T1, Impôt sur le revenu...">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Montant (€) *</label>
                    <input type="number" name="montant" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date prévue</label>
                    <input type="date" name="date_prevue" class="form-input">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-input" placeholder="Optionnel">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 0.75rem;">Ajouter</button>
        </form>
    </div>
    @if($reserves->isNotEmpty())
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th>Date prévue</th>
                    <th style="text-align: right;">Montant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($reserves as $r)
                <tr>
                    <td data-label="Libellé">{{ $r->libelle }}</td>
                    <td data-label="Date prévue" class="text-muted">{{ $r->date_prevue ? \Carbon\Carbon::parse($r->date_prevue)->format('d/m/Y') : '-' }}</td>
                    <td data-label="Montant" class="font-bold" style="text-align: right;">{{ number_format($r->montant, 2, ',', ' ') }} €</td>
                    <td data-label="Statut">
                        @if($r->payee)
                        <form action="{{ route('brightshell.ressources.reserves.toggle-paid', $r->id) }}" method="POST" style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            @csrf
                            <span class="badge badge-success">Payée</span>
                            <button type="submit" class="btn btn-sm btn-secondary">Non payée</button>
                        </form>
                        @else
                        <form action="{{ route('brightshell.ressources.reserves.toggle-paid', $r->id) }}" method="POST" style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            @csrf
                            <span class="badge badge-warning">À payer</span>
                            <button type="submit" class="btn btn-sm btn-success">Marquer payée</button>
                        </form>
                        @endif
                    </td>
                    <td data-label="Actions">
                        <form action="{{ route('brightshell.ressources.reserves.delete', $r->id) }}" method="POST" style="display: flex; justify-content: flex-end;" onsubmit="return confirm('Supprimer cette réserve ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">Suppr.</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucune réserve. Ajoutez ce que vous devez garder de côté (cotisations, impôts, etc.).</p>
    @endif
</div>

{{-- Abonnements --}}
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Abonnements</h3>
    </div>
    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--bs-border);">
        <form action="{{ route('brightshell.ressources.abonnements.store') }}" method="POST" id="form-abo">
            @csrf
            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-input" required>
                        <option value="entree">Entrée (on nous paye)</option>
                        <option value="sortie">Sortie (on paye)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Libellé *</label>
                    <input type="text" name="libelle" class="form-input" required placeholder="Ex: Abo Figma, Client X facilité...">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Bénéficiaire / Payeur</label>
                    <input type="text" name="beneficiaire" class="form-input" placeholder="Qui paye ou qui est payé">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Montant (€) *</label>
                    <input type="number" name="montant" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Fréquence *</label>
                    <select name="frequence" class="form-input" id="freq-select">
                        <option value="mensuel">Mensuel</option>
                        <option value="semaines_strictes">Semaines strictes</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;" id="wrap-intervalle">
                    <label class="form-label">Toutes les … semaines</label>
                    <input type="number" name="intervalle_semaines" class="form-input" min="1" max="52" value="4" placeholder="4">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date de début *</label>
                    <input type="date" name="date_debut" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date de fin</label>
                    <input type="date" name="date_fin" class="form-input" placeholder="Facilité de paiement, fin abo">
                </div>
            </div>
            <div class="form-group" style="margin-top: 0.75rem; margin-bottom: 0;">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-input" placeholder="Optionnel">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 0.75rem;">Ajouter l'abonnement</button>
        </form>
    </div>
    @if($abonnements->isNotEmpty())
    <div class="table-container" style="border: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Libellé</th>
                    <th>Bénéficiaire</th>
                    <th>Montant</th>
                    <th>Fréquence</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Prochaine échéance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($abonnements as $a)
                <tr class="{{ !$a->actif ? 'opacity-50' : '' }}">
                    <td data-label="Type"><span class="badge {{ $a->type === 'entree' ? 'badge-success' : 'badge-danger' }}">{{ $a->type === 'entree' ? 'Entrée' : 'Sortie' }}</span></td>
                    <td data-label="Libellé">{{ $a->libelle }}</td>
                    <td data-label="Bénéficiaire">{{ $a->beneficiaire ?? '-' }}</td>
                    <td data-label="Montant">{{ number_format($a->montant, 2, ',', ' ') }} €</td>
                    <td data-label="Fréquence">
                        @if($a->frequence === 'mensuel')
                        <span class="badge badge-info">Mensuel</span>
                        @else
                        <span class="badge badge-warning">Toutes les {{ $a->intervalle_semaines ?? 4 }} sem.</span>
                        @endif
                    </td>
                    <td data-label="Début" class="text-muted">{{ \Carbon\Carbon::parse($a->date_debut)->format('d/m/Y') }}</td>
                    <td data-label="Fin" class="text-muted">{{ $a->date_fin ? \Carbon\Carbon::parse($a->date_fin)->format('d/m/Y') : '-' }}</td>
                    <td data-label="Prochaine échéance">{{ $a->prochaine_echeance ? \Carbon\Carbon::parse($a->prochaine_echeance)->format('d/m/Y') : '-' }}</td>
                    <td data-label="Actions">
                        <form action="{{ route('brightshell.ressources.abonnements.delete', $a->id) }}" method="POST" style="display: flex; justify-content: flex-end;" onsubmit="return confirm('Supprimer cet abonnement ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">Suppr.</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-muted text-center" style="padding: 2rem;">Aucun abonnement. Entrées récurrentes (facilités de paiement, abos clients) ou sorties (SaaS, hebdo…).</p>
    @endif
</div>

<style>
.d-none { display: none !important; }
.opacity-50 { opacity: 0.5; }
</style>
<script>
document.getElementById('freq-select').addEventListener('change', function() {
    document.getElementById('wrap-intervalle').style.display = this.value === 'semaines_strictes' ? 'block' : 'none';
});
document.getElementById('wrap-intervalle').style.display = document.getElementById('freq-select').value === 'semaines_strictes' ? 'block' : 'none';
</script>
@endsection
