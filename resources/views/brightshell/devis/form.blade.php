@extends('brightshell.layout')

@section('title', $devis ? 'Modifier le devis' : 'Nouveau devis')

@push('styles')
<style>
    .devis-builder {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    @media (max-width: 1400px) {
        .devis-builder {
            grid-template-columns: 1fr;
        }
    }
    
    .ligne-item {
        background: var(--bs-bg-dark);
        border: 1px solid var(--bs-border);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .ligne-item.expanded {
        border-color: var(--bs-accent);
    }
    
    .ligne-main {
        display: grid;
        grid-template-columns: 1fr 80px 100px 100px auto;
        gap: 0.5rem;
        align-items: center;
    }

    @media (max-width: 768px) {
        .ligne-main {
            grid-template-columns: 1fr 1fr;
        }
        .ligne-main input.ligne-desc { grid-column: span 2; }
        .ligne-main .ligne-total { text-align: right; }
        .ligne-main .ligne-actions { grid-column: span 2; display: flex; gap: 0.5rem; justify-content: flex-end; }
    }
    
    .ligne-header {
        display: grid;
        grid-template-columns: 1fr 80px 100px 100px auto;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--bs-border);
    }
    
    .ligne-header span {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--bs-text-muted);
    }
    
    @media (max-width: 768px) {
        .ligne-header { display: none; }
    }
    
    .ligne-total {
        font-weight: 600;
        color: var(--bs-accent);
        font-size: 0.875rem;
        min-width: 80px;
        text-align: right;
    }
    
    .ligne-actions {
        display: flex;
        gap: 0.25rem;
    }
    
    .ligne-details {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--bs-border);
    }
    
    .ligne-description {
        margin-bottom: 0.75rem;
    }
    
    .ligne-description textarea {
        width: 100%;
        min-height: 60px;
        resize: vertical;
    }
    
    /* Sous-lignes */
    .sous-lignes-container {
        margin-top: 0.75rem;
        padding-left: 1rem;
        border-left: 2px solid var(--bs-accent);
    }
    
    .sous-ligne-item {
        display: grid;
        grid-template-columns: 1fr 60px 80px 80px 30px;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }
    
    @media (max-width: 768px) {
        .sous-ligne-item {
            grid-template-columns: 1fr 1fr;
        }
        .sous-ligne-item input:first-child { grid-column: span 2; }
    }
    
    .sous-ligne-item input {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
    }
    
    .sous-ligne-total {
        font-size: 0.8rem;
        color: var(--bs-text-muted);
        text-align: right;
    }
    
    .sous-ligne-header {
        display: grid;
        grid-template-columns: 1fr 60px 80px 80px 30px;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--bs-text-muted);
        opacity: 0.7;
    }
    
    @media (max-width: 768px) {
        .sous-ligne-header { display: none; }
    }
    
    .btn-expand {
        background: transparent;
        border: 1px solid var(--bs-border);
        color: var(--bs-text-muted);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    
    .btn-expand:hover {
        background: var(--bs-bg-hover);
        color: var(--bs-accent);
        border-color: var(--bs-accent);
    }
    
    .btn-expand.active {
        background: rgba(91, 188, 228, 0.1);
        color: var(--bs-accent);
        border-color: var(--bs-accent);
    }
    
    .totaux-section {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 2px solid var(--bs-border);
    }
    
    .totaux-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        font-size: 0.9rem;
    }
    
    .totaux-row.total-final {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--bs-accent);
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 1px solid var(--bs-border);
    }
    
    /* Preview */
    .preview-container {
        background: white;
        color: #1a1a1a;
        border-radius: 12px;
        padding: 2rem;
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 130px);
        overflow-y: auto;
    }

    @media (max-width: 768px) {
        .preview-container {
            position: static;
            padding: 1rem;
            max-height: none;
            margin-top: 1rem;
        }
    }
    
    .preview-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .preview-title {
        font-size: 2rem;
        font-weight: 700;
        color: #5bbce4;
    }
    
    .preview-numero {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0a0e1a;
    }
    
    .preview-section {
        margin-bottom: 1.5rem;
    }
    
    .preview-section-title {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .preview-client-box {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 8px;
    }
    
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    
    .preview-table th {
        background: #0a0e1a;
        color: white;
        padding: 0.75rem;
        font-size: 0.7rem;
        text-transform: uppercase;
        text-align: left;
    }
    
    .preview-table th:not(:first-child) {
        text-align: right;
    }
    
    .preview-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        color: #0a0e1a;
    }
    
    .preview-table td:not(:first-child) {
        text-align: right;
    }
    
    .preview-table tr.sous-ligne td {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        color: #6b7280;
        background: #f9fafb;
    }
    
    .preview-table tr.sous-ligne td:first-child {
        padding-left: 2rem;
    }
    
    .preview-table tr.ligne-parent td {
        font-weight: 600;
        background: #fafbfc;
    }
    
    .preview-table .ligne-description-cell {
        font-size: 0.8rem;
        color: #6b7280;
        font-style: italic;
        padding-top: 0.25rem;
    }
    
    .preview-totaux {
        text-align: right;
        margin-top: 1rem;
    }
    
    .preview-totaux-row {
        padding: 0.25rem 0;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    .preview-totaux-row.final {
        font-size: 1.25rem;
        font-weight: 700;
        color: #5bbce4;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .preview-mention-tva {
        text-align: right;
        font-size: 0.8rem;
        color: #6b7280;
        font-style: italic;
        margin-top: 0.5rem;
    }
    
    .preview-notes {
        background: #f9fafb;
        padding: 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #0a0e1a;
        white-space: pre-line;
    }
    
    /* Toggle TVA */
    .tva-toggle {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--bs-bg-dark);
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    
    .tva-toggle label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-size: 0.875rem;
    }
    
    .tva-input-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .tva-input-group input {
        width: 80px;
    }
    
    .radio-group {
        display: flex;
        gap: 1.5rem;
    }
    
    .radio-option {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    
    .radio-option input[type="radio"] {
        accent-color: var(--bs-accent);
    }
</style>
@endpush

@section('content')
<div class="devis-builder">
    <!-- Formulaire -->
    <div class="card">
        <form action="{{ $devis ? route('brightshell.devis.update', $devis->id) : route('brightshell.devis.store') }}" method="POST" id="devis-form">
            @csrf
            @if($devis) @method('PUT') @endif
            
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Client *</label>
                    <select name="client_id" id="client_id" class="form-input" required onchange="updatePreview()">
                        <option value="">Sélectionner un client</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" 
                                data-nom="{{ $client->nom }}" 
                                data-prenom="{{ $client->prenom ?? '' }}"
                                data-societe="{{ $client->societe ?? '' }}"
                                data-adresse="{{ $client->adresse ?? '' }}"
                                data-cp="{{ $client->code_postal ?? '' }}"
                                data-ville="{{ $client->ville ?? '' }}"
                                data-siret="{{ $client->siret ?? '' }}"
                                {{ old('client_id', $devis->client_id ?? '') == $client->id ? 'selected' : '' }}>
                            {{ $client->nom }} {{ $client->prenom ?? '' }} {{ $client->societe ? "({$client->societe})" : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date du devis</label>
                    <input type="date" name="date_devis" id="date_devis" class="form-input" 
                           value="{{ old('date_devis', $devis ? \Carbon\Carbon::parse($devis->date_devis ?? $devis->created_at)->format('Y-m-d') : date('Y-m-d')) }}" 
                           onchange="updatePreview()">
                </div>
                <div class="form-group">
                    <label class="form-label">Validité (jours)</label>
                    <input type="number" name="validite_jours" id="validite_jours" class="form-input" 
                           value="{{ old('validite_jours', $devis->validite_jours ?? 30) }}" min="1" onchange="updatePreview()">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Objet *</label>
                <input type="text" name="objet" id="objet" class="form-input" 
                       value="{{ old('objet', $devis->objet ?? '') }}" required 
                       placeholder="Ex: Développement site web" oninput="updatePreview()">
            </div>
            
            <!-- Options TVA -->
            <div class="tva-toggle" style="flex-wrap: wrap;">
                <div>
                    <label class="radio-option">
                        <input type="radio" name="mode_tva" value="non_assujetti" id="mode_non_assujetti" 
                               {{ old('mode_tva', $devis->mode_tva ?? 'non_assujetti') === 'non_assujetti' ? 'checked' : '' }}
                               onchange="updateTvaMode()">
                        <span>Non assujetti</span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <label class="radio-option">
                        <input type="radio" name="mode_tva" value="ht" id="mode_ht"
                               {{ old('mode_tva', $devis->mode_tva ?? '') === 'ht' ? 'checked' : '' }}
                               onchange="updateTvaMode()">
                        <span>HT + TVA</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mode_tva" value="ttc" id="mode_ttc"
                               {{ old('mode_tva', $devis->mode_tva ?? '') === 'ttc' ? 'checked' : '' }}
                               onchange="updateTvaMode()">
                        <span>TTC</span>
                    </label>
                    <div class="tva-input-group" id="tva-rate-group" style="display: none;">
                        <span>Taux:</span>
                        <input type="number" name="taux_tva" id="taux_tva" class="form-input" 
                               value="{{ old('taux_tva', $devis->taux_tva ?? 20) }}" 
                               min="0" max="100" step="0.1" onchange="recalculerTotaux()">
                        <span>%</span>
                    </div>
                </div>
            </div>
            
            <!-- Lignes de devis -->
            <div class="form-group">
                <label class="form-label">Lignes de devis</label>
                
                <div class="ligne-header">
                    <span>Description</span>
                    <span>Qté</span>
                    <span>Prix unit. €</span>
                    <span>Total €</span>
                    <span></span>
                </div>
                
                <div id="lignes-container"></div>
                
                <button type="button" onclick="ajouterLigne()" class="btn btn-secondary btn-sm mt-2">
                    + Ajouter une ligne
                </button>
                
                <!-- Totaux -->
                <div class="totaux-section">
                    <div class="totaux-row">
                        <span id="label-sous-total">Total HT</span>
                        <span id="montant-ht">0,00 €</span>
                    </div>
                    <div class="totaux-row" id="row-tva" style="display: none;">
                        <span>TVA (<span id="label-taux-tva">20</span>%)</span>
                        <span id="montant-tva">0,00 €</span>
                    </div>
                    <div class="totaux-row total-final">
                        <span>Total</span>
                        <span id="montant-total">0,00 €</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes / Conditions</label>
                <textarea name="notes" id="notes" class="form-textarea" 
                          placeholder="Conditions de paiement, délais, etc." 
                          oninput="updatePreview()">{{ old('notes', $devis->notes ?? '') }}</textarea>
            </div>
            
            <!-- Hidden fields pour les totaux -->
            <input type="hidden" name="montant_ht_calculated" id="montant_ht_calculated">
            <input type="hidden" name="montant_tva_calculated" id="montant_tva_calculated">
            <input type="hidden" name="montant_total_calculated" id="montant_total_calculated">
            
            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">{{ $devis ? 'Mettre à jour' : 'Créer le devis' }}</button>
                <a href="{{ route('brightshell.devis') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
    
    <!-- Preview -->
    <div class="preview-container" id="preview">
        <div class="preview-header">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: #0a0e1a; margin-bottom: 0.5rem;">BrightShell</h2>
                <p style="color: #6b7280; font-size: 0.875rem;">Auto-entreprise</p>
                <p style="color: #6b7280; font-size: 0.875rem;">SIRET: 994 535 904 00019</p>
                <p style="color: #6b7280; font-size: 0.875rem;">lucas.espinar@brightshell.fr</p>
                <p style="color: #6b7280; font-size: 0.875rem;">06.44.07.30.37</p>
            </div>
            <div style="text-align: right;">
                <div class="preview-title">DEVIS</div>
                <div class="preview-numero" id="preview-numero">DEV-{{ date('Y') }}-XXXX</div>
                <p style="color: #6b7280; margin-top: 1rem;">Date: <span id="preview-date">{{ date('d/m/Y') }}</span></p>
                <p style="color: #6b7280;">Validité: <span id="preview-validite">30</span> jours</p>
            </div>
        </div>
        
        <!-- Client -->
        <div class="preview-section">
            <div class="preview-section-title">Client</div>
            <div class="preview-client-box" id="preview-client">
                <p style="color: #6b7280; font-style: italic;">Sélectionnez un client...</p>
            </div>
        </div>
        
        <!-- Objet -->
        <div class="preview-section">
            <div class="preview-section-title">Objet</div>
            <p style="font-weight: 600; color: #0a0e1a;" id="preview-objet">-</p>
        </div>
        
        <!-- Lignes -->
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qté</th>
                    <th>Prix unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="preview-lignes">
                <tr>
                    <td colspan="4" style="text-align: center; color: #6b7280; font-style: italic;">
                        Ajoutez des lignes...
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Totaux preview -->
        <div class="preview-totaux">
            <div class="preview-totaux-row" id="preview-row-ht">
                <span id="preview-label-ht">Total HT:</span> <strong id="preview-montant-ht">0,00 €</strong>
            </div>
            <div class="preview-totaux-row" id="preview-row-tva" style="display: none;">
                TVA (<span id="preview-taux-tva">20</span>%): <strong id="preview-montant-tva">0,00 €</strong>
            </div>
            <div class="preview-totaux-row final">
                Total: <span id="preview-montant-total">0,00 €</span>
            </div>
        </div>
        
        <div class="preview-mention-tva" id="preview-mention-tva">
            TVA non applicable, art. 293 B du CGI
        </div>
        
        <!-- Notes -->
        <div class="preview-section" id="preview-notes-section" style="margin-top: 1.5rem; display: none;">
            <div class="preview-section-title">Notes & Conditions</div>
            <div class="preview-notes" id="preview-notes"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let ligneIndex = 0;

function formatMontant(value) {
    return new Intl.NumberFormat('fr-FR', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    }).format(value) + ' €';
}

function ajouterLigne(data = {}) {
    const container = document.getElementById('lignes-container');
    const div = document.createElement('div');
    div.className = 'ligne-item';
    div.dataset.index = ligneIndex;
    
    const description = data.description || '';
    const quantite = data.quantite || 1;
    const prixUnitaire = data.prix_unitaire || '';
    const details = data.details || '';
    const sousLignes = data.sous_lignes || [];
    const hasDetails = details || sousLignes.length > 0;
    
    div.innerHTML = `
        <div class="ligne-main">
            <input type="text" name="lignes[${ligneIndex}][description]" class="form-input ligne-desc" 
                   placeholder="Description de la prestation" value="${escapeHtml(description)}" required 
                   oninput="recalculerTotaux()">
            <input type="number" name="lignes[${ligneIndex}][quantite]" class="form-input ligne-qte" 
                   value="${quantite}" min="0.01" step="0.01" required 
                   onchange="recalculerTotaux()" oninput="recalculerTotaux()">
            <input type="number" name="lignes[${ligneIndex}][prix_unitaire]" class="form-input ligne-prix" 
                   placeholder="0.00" value="${prixUnitaire}" min="0" step="0.01" 
                   onchange="recalculerTotaux()" oninput="recalculerTotaux()">
            <span class="ligne-total">0,00 €</span>
            <div class="ligne-actions">
                <button type="button" onclick="toggleDetails(this)" class="btn-expand ${hasDetails ? 'active' : ''}" title="Ajouter des détails">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <button type="button" onclick="supprimerLigne(this)" class="btn btn-danger btn-sm">×</button>
            </div>
        </div>
        <div class="ligne-details" style="display: ${hasDetails ? 'block' : 'none'};">
            <div class="ligne-description">
                <textarea name="lignes[${ligneIndex}][details]" class="form-input" 
                          placeholder="Description détaillée (optionnel)" 
                          oninput="recalculerTotaux()">${escapeHtml(details)}</textarea>
            </div>
            <div class="sous-lignes-container">
                <div class="sous-ligne-header">
                    <span>Détail</span>
                    <span>Qté</span>
                    <span>Prix €</span>
                    <span>Total</span>
                    <span></span>
                </div>
                <div class="sous-lignes-list" data-ligne="${ligneIndex}"></div>
                <button type="button" onclick="ajouterSousLigne(${ligneIndex})" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem; font-size: 0.75rem;">
                    + Ajouter un détail
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(div);
    
    // Ajouter les sous-lignes existantes
    if (sousLignes.length > 0) {
        sousLignes.forEach(sl => {
            ajouterSousLigne(ligneIndex, sl);
        });
    }
    
    ligneIndex++;
    recalculerTotaux();
}

function toggleDetails(btn) {
    const ligneItem = btn.closest('.ligne-item');
    const details = ligneItem.querySelector('.ligne-details');
    const isVisible = details.style.display !== 'none';
    
    details.style.display = isVisible ? 'none' : 'block';
    btn.classList.toggle('active', !isVisible);
    ligneItem.classList.toggle('expanded', !isVisible);
}

function ajouterSousLigne(ligneIdx, data = {}) {
    const container = document.querySelector(`.sous-lignes-list[data-ligne="${ligneIdx}"]`);
    if (!container) return;
    
    const sousLigneIdx = container.children.length;
    const div = document.createElement('div');
    div.className = 'sous-ligne-item';
    
    const description = data.description || '';
    const quantite = data.quantite || 1;
    const prixUnitaire = data.prix_unitaire || '';
    
    div.innerHTML = `
        <input type="text" name="lignes[${ligneIdx}][sous_lignes][${sousLigneIdx}][description]" 
               class="form-input sl-desc" placeholder="Détail..." value="${escapeHtml(description)}" 
               oninput="recalculerTotaux()">
        <input type="number" name="lignes[${ligneIdx}][sous_lignes][${sousLigneIdx}][quantite]" 
               class="form-input sl-qte" value="${quantite}" min="0.01" step="0.01" 
               onchange="recalculerTotaux()" oninput="recalculerTotaux()">
        <input type="number" name="lignes[${ligneIdx}][sous_lignes][${sousLigneIdx}][prix_unitaire]" 
               class="form-input sl-prix" placeholder="0" value="${prixUnitaire}" min="0" step="0.01" 
               onchange="recalculerTotaux()" oninput="recalculerTotaux()">
        <span class="sous-ligne-total">0 €</span>
        <button type="button" onclick="supprimerSousLigne(this)" class="btn btn-danger btn-sm" style="padding: 0.2rem 0.4rem;">×</button>
    `;
    
    container.appendChild(div);
    recalculerTotaux();
}

function supprimerSousLigne(btn) {
    btn.closest('.sous-ligne-item').remove();
    recalculerTotaux();
}

function supprimerLigne(btn) {
    btn.closest('.ligne-item').remove();
    recalculerTotaux();
}

function updateTvaMode() {
    const mode = document.querySelector('input[name="mode_tva"]:checked').value;
    const tvaRateGroup = document.getElementById('tva-rate-group');
    const rowTva = document.getElementById('row-tva');
    const previewRowTva = document.getElementById('preview-row-tva');
    const previewMentionTva = document.getElementById('preview-mention-tva');
    const labelSousTotal = document.getElementById('label-sous-total');
    const previewLabelHt = document.getElementById('preview-label-ht');
    
    if (mode === 'non_assujetti') {
        tvaRateGroup.style.display = 'none';
        rowTva.style.display = 'none';
        previewRowTva.style.display = 'none';
        previewMentionTva.style.display = 'block';
        previewMentionTva.textContent = 'TVA non applicable, art. 293 B du CGI';
        labelSousTotal.textContent = 'Total HT';
        previewLabelHt.textContent = 'Total HT:';
    } else if (mode === 'ht') {
        tvaRateGroup.style.display = 'flex';
        rowTva.style.display = 'flex';
        previewRowTva.style.display = 'block';
        previewMentionTva.style.display = 'none';
        labelSousTotal.textContent = 'Total HT';
        previewLabelHt.textContent = 'Total HT:';
    } else if (mode === 'ttc') {
        tvaRateGroup.style.display = 'flex';
        rowTva.style.display = 'flex';
        previewRowTva.style.display = 'block';
        previewMentionTva.style.display = 'none';
        labelSousTotal.textContent = 'Total TTC';
        previewLabelHt.textContent = 'Total TTC:';
    }
    
    recalculerTotaux();
}

function recalculerTotaux() {
    const lignes = document.querySelectorAll('.ligne-item');
    let totalHt = 0;
    
    lignes.forEach(ligne => {
        const sousLignes = ligne.querySelectorAll('.sous-ligne-item');
        let ligneSousTotal = 0;
        
        // Calculer les sous-lignes
        sousLignes.forEach(sl => {
            const qte = parseFloat(sl.querySelector('.sl-qte').value) || 0;
            const prix = parseFloat(sl.querySelector('.sl-prix').value) || 0;
            const sousTotal = qte * prix;
            sl.querySelector('.sous-ligne-total').textContent = formatMontant(sousTotal);
            ligneSousTotal += sousTotal;
        });
        
        // Si des sous-lignes existent, le prix principal = somme des sous-lignes
        const prixInput = ligne.querySelector('.ligne-prix');
        const qteInput = ligne.querySelector('.ligne-qte');
        
        let ligneTotal = 0;
        if (sousLignes.length > 0) {
            // Le prix unitaire devient la somme des sous-lignes (on laisse l'input modifiable)
            // Mais on calcule le total à partir des sous-lignes
            const qte = parseFloat(qteInput.value) || 1;
            ligneTotal = ligneSousTotal * qte;
            // Mettre à jour le prix unitaire implicite (optionnel, pour affichage)
            // prixInput.value = ligneSousTotal.toFixed(2);
        } else {
            const qte = parseFloat(qteInput.value) || 0;
            const prix = parseFloat(prixInput.value) || 0;
            ligneTotal = qte * prix;
        }
        
        ligne.querySelector('.ligne-total').textContent = formatMontant(ligneTotal);
        totalHt += ligneTotal;
    });
    
    const mode = document.querySelector('input[name="mode_tva"]:checked').value;
    const tauxTva = parseFloat(document.getElementById('taux_tva').value) || 0;
    
    let montantTva = 0;
    let montantTotal = 0;
    
    if (mode === 'non_assujetti') {
        montantTotal = totalHt;
    } else if (mode === 'ht') {
        montantTva = totalHt * (tauxTva / 100);
        montantTotal = totalHt + montantTva;
    } else if (mode === 'ttc') {
        const htFromTtc = totalHt / (1 + tauxTva / 100);
        montantTva = totalHt - htFromTtc;
        montantTotal = totalHt;
    }
    
    // Mise à jour affichage formulaire
    document.getElementById('montant-ht').textContent = formatMontant(totalHt);
    document.getElementById('montant-tva').textContent = formatMontant(montantTva);
    document.getElementById('montant-total').textContent = formatMontant(montantTotal);
    document.getElementById('label-taux-tva').textContent = tauxTva;
    
    // Hidden fields
    document.getElementById('montant_ht_calculated').value = totalHt.toFixed(2);
    document.getElementById('montant_tva_calculated').value = montantTva.toFixed(2);
    document.getElementById('montant_total_calculated').value = montantTotal.toFixed(2);
    
    // Mise à jour preview
    document.getElementById('preview-montant-ht').textContent = formatMontant(totalHt);
    document.getElementById('preview-montant-tva').textContent = formatMontant(montantTva);
    document.getElementById('preview-montant-total').textContent = formatMontant(montantTotal);
    document.getElementById('preview-taux-tva').textContent = tauxTva;
    
    updatePreviewLignes();
}

function updatePreviewLignes() {
    const lignes = document.querySelectorAll('.ligne-item');
    const tbody = document.getElementById('preview-lignes');
    
    if (lignes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #6b7280; font-style: italic;">Ajoutez des lignes...</td></tr>`;
        return;
    }
    
    let html = '';
    lignes.forEach(ligne => {
        const desc = ligne.querySelector('.ligne-desc').value || '-';
        const details = ligne.querySelector('textarea')?.value || '';
        const qte = parseFloat(ligne.querySelector('.ligne-qte').value) || 0;
        const sousLignes = ligne.querySelectorAll('.sous-ligne-item');
        
        let ligneTotal = 0;
        let prix = parseFloat(ligne.querySelector('.ligne-prix').value) || 0;
        
        if (sousLignes.length > 0) {
            // Calcul depuis sous-lignes
            let sousTotal = 0;
            sousLignes.forEach(sl => {
                const slQte = parseFloat(sl.querySelector('.sl-qte').value) || 0;
                const slPrix = parseFloat(sl.querySelector('.sl-prix').value) || 0;
                sousTotal += slQte * slPrix;
            });
            prix = sousTotal;
            ligneTotal = sousTotal * qte;
        } else {
            ligneTotal = qte * prix;
        }
        
        // Ligne principale
        const hasSousLignes = sousLignes.length > 0;
        html += `
            <tr class="${hasSousLignes ? 'ligne-parent' : ''}">
                <td>
                    ${escapeHtml(desc)}
                    ${details ? `<div class="ligne-description-cell">${escapeHtml(details)}</div>` : ''}
                </td>
                <td>${qte}</td>
                <td>${formatMontant(prix)}</td>
                <td style="font-weight: 600;">${formatMontant(ligneTotal)}</td>
            </tr>
        `;
        
        // Sous-lignes
        if (hasSousLignes) {
            sousLignes.forEach(sl => {
                const slDesc = sl.querySelector('.sl-desc').value || '-';
                const slQte = parseFloat(sl.querySelector('.sl-qte').value) || 0;
                const slPrix = parseFloat(sl.querySelector('.sl-prix').value) || 0;
                const slTotal = slQte * slPrix;
                
                html += `
                    <tr class="sous-ligne">
                        <td>↳ ${escapeHtml(slDesc)}</td>
                        <td>${slQte}</td>
                        <td>${formatMontant(slPrix)}</td>
                        <td>${formatMontant(slTotal)}</td>
                    </tr>
                `;
            });
        }
    });
    
    tbody.innerHTML = html;
}

function updatePreview() {
    // Client
    const clientSelect = document.getElementById('client_id');
    const selectedOption = clientSelect.options[clientSelect.selectedIndex];
    const clientBox = document.getElementById('preview-client');
    
    if (selectedOption && selectedOption.value) {
        const nom = selectedOption.dataset.nom || '';
        const prenom = selectedOption.dataset.prenom || '';
        const societe = selectedOption.dataset.societe || '';
        const adresse = selectedOption.dataset.adresse || '';
        const cp = selectedOption.dataset.cp || '';
        const ville = selectedOption.dataset.ville || '';
        const siret = selectedOption.dataset.siret || '';
        
        let html = `<p style="font-weight: 600; color: #0a0e1a;">${societe || (nom + ' ' + prenom)}</p>`;
        if (adresse) html += `<p style="color: #6b7280; font-size: 0.875rem;">${adresse}</p>`;
        if (cp || ville) html += `<p style="color: #6b7280; font-size: 0.875rem;">${cp} ${ville}</p>`;
        if (siret) html += `<p style="color: #6b7280; font-size: 0.875rem;">SIRET: ${siret}</p>`;
        
        clientBox.innerHTML = html;
    } else {
        clientBox.innerHTML = '<p style="color: #6b7280; font-style: italic;">Sélectionnez un client...</p>';
    }
    
    // Objet
    const objet = document.getElementById('objet').value || '-';
    document.getElementById('preview-objet').textContent = objet;
    
    // Validité
    const validite = document.getElementById('validite_jours').value || 30;
    document.getElementById('preview-validite').textContent = validite;
    
    // Date
    const dateInput = document.getElementById('date_devis').value;
    if (dateInput) {
        const [year, month, day] = dateInput.split('-');
        document.getElementById('preview-date').textContent = `${day}/${month}/${year}`;
    }
    
    // Notes
    const notes = document.getElementById('notes').value;
    const notesSection = document.getElementById('preview-notes-section');
    if (notes.trim()) {
        notesSection.style.display = 'block';
        document.getElementById('preview-notes').textContent = notes;
    } else {
        notesSection.style.display = 'none';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    @if($devis && $devis->lignes)
        @foreach($devis->lignes as $ligne)
        ajouterLigne({
            description: {!! json_encode($ligne['description'] ?? '') !!},
            quantite: {!! json_encode($ligne['quantite'] ?? 1) !!},
            prix_unitaire: {!! json_encode($ligne['prix_unitaire'] ?? '') !!},
            details: {!! json_encode($ligne['details'] ?? '') !!},
            sous_lignes: {!! json_encode($ligne['sous_lignes'] ?? []) !!}
        });
        @endforeach
    @else
        ajouterLigne();
    @endif
    
    updateTvaMode();
    updatePreview();
});
</script>
@endpush
@endsection
