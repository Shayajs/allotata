@extends('brightshell.layout')

@section('title', isset($facture) ? 'Modifier la facture' : 'Nouvelle facture')

@section('content')
<div class="grid grid-2" id="billing-grid">
    <!-- Formulaire -->
    <div class="card">
        <form action="{{ isset($facture) ? route('brightshell.factures.update', $facture->id) : route('brightshell.factures.store') }}" method="POST" id="facture-form">
            @csrf
            @if(isset($facture)) @method('PUT') @endif
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Client *</label>
                    <select name="client_id" class="form-input" required id="client-select" onchange="updatePreview()">
                        <option value="">Sélectionner un client</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" 
                                data-nom="{{ $client->nom }}" 
                                data-prenom="{{ $client->prenom ?? '' }}"
                                data-societe="{{ $client->societe ?? '' }}"
                                data-adresse="{{ $client->adresse ?? '' }}"
                                data-cp="{{ $client->code_postal ?? '' }}"
                                data-ville="{{ $client->ville ?? '' }}"
                                {{ old('client_id', $facture->client_id ?? '') == $client->id ? 'selected' : '' }}>
                            {{ $client->nom }} {{ $client->prenom ?? '' }} {{ $client->societe ? "({$client->societe})" : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date de facture</label>
                    <input type="date" name="date_facture" id="date_facture" class="form-input" 
                           value="{{ old('date_facture', $facture ? \Carbon\Carbon::parse($facture->date_facture ?? $facture->created_at)->format('Y-m-d') : date('Y-m-d')) }}" 
                           onchange="updatePreview()">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Objet *</label>
                <input type="text" name="objet" class="form-input" value="{{ old('objet', $facture->objet ?? '') }}" required id="objet-input" oninput="updatePreview()">
            </div>
            
            <div class="form-group">
                <label class="form-label">Lignes de facture</label>
                <div id="lignes-container"></div>
                <button type="button" onclick="ajouterLigne()" class="btn btn-secondary btn-sm mt-2">+ Ajouter une ligne</button>
            </div>
            
            <!-- Options TVA -->
            <div class="form-group" style="background: var(--bs-bg-hover); padding: 1rem; border-radius: 8px;">
                <label class="form-label">Mode TVA</label>
                <div class="grid grid-3" style="gap: 0.5rem; margin-bottom: 1rem;">
                    <label class="radio-card" onclick="updatePreview()">
                        <input type="radio" name="mode_tva" value="non_assujetti" {{ old('mode_tva', $facture->mode_tva ?? 'non_assujetti') == 'non_assujetti' ? 'checked' : '' }}>
                        <span>Non assujetti</span>
                        <small>Art. 293 B CGI</small>
                    </label>
                    <label class="radio-card" onclick="updatePreview()">
                        <input type="radio" name="mode_tva" value="ht" {{ old('mode_tva', $facture->mode_tva ?? '') == 'ht' ? 'checked' : '' }}>
                        <span>HT + TVA</span>
                        <small>Prix HT</small>
                    </label>
                    <label class="radio-card" onclick="updatePreview()">
                        <input type="radio" name="mode_tva" value="ttc" {{ old('mode_tva', $facture->mode_tva ?? '') == 'ttc' ? 'checked' : '' }}>
                        <span>TTC</span>
                        <small>Prix TTC</small>
                    </label>
                </div>
                
                <div id="taux-tva-container" style="display: none;">
                    <label class="form-label">Taux TVA (%)</label>
                    <select name="taux_tva" class="form-input" id="taux-tva" onchange="updatePreview()">
                        <option value="20" {{ old('taux_tva', $facture->taux_tva ?? 20) == 20 ? 'selected' : '' }}>20%</option>
                        <option value="10" {{ old('taux_tva', $facture->taux_tva ?? 20) == 10 ? 'selected' : '' }}>10%</option>
                        <option value="5.5" {{ old('taux_tva', $facture->taux_tva ?? 20) == 5.5 ? 'selected' : '' }}>5,5%</option>
                        <option value="2.1" {{ old('taux_tva', $facture->taux_tva ?? 20) == 2.1 ? 'selected' : '' }}>2,1%</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Échéance (jours)</label>
                <input type="number" name="echeance_jours" class="form-input" value="{{ old('echeance_jours', $facture->echeance_jours ?? 30) }}" min="1">
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-textarea" id="notes-input" oninput="updatePreview()">{{ old('notes', $facture->notes ?? '') }}</textarea>
            </div>
            
            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">{{ isset($facture) ? 'Mettre à jour' : 'Créer la facture' }}</button>
                <a href="{{ route('brightshell.factures') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
    
    <!-- Prévisualisation -->
    <div class="card preview-card" style="background: white; color: #1a1a1a;">
        <div style="text-align: center; margin-bottom: 1rem;">
            <span class="badge badge-info">Prévisualisation</span>
        </div>
        
        <div style="transform: scale(0.85); transform-origin: top center;">
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                <!-- En-tête -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e5e7eb;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #0a0e1a;">BrightShell EI</h3>
                        <p style="color: #6b7280; font-size: 0.75rem;">Entrepreneur Individuel</p>
                    </div>
                    <div style="text-align: right;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #5bbce4;">FACTURE</h2>
                        <p style="color: #6b7280; font-size: 0.75rem;"><span id="preview-date">{{ now()->format('d/m/Y') }}</span></p>
                    </div>
                </div>
                
                <!-- Client -->
                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                    <p style="font-size: 0.625rem; text-transform: uppercase; color: #6b7280;">Client</p>
                    <p style="font-weight: 600; color: #0a0e1a; font-size: 0.875rem;" id="preview-client">Sélectionnez un client</p>
                    <p style="color: #6b7280; font-size: 0.75rem;" id="preview-client-adresse"></p>
                </div>
                
                <!-- Objet -->
                <div style="margin-bottom: 1rem;">
                    <p style="font-size: 0.625rem; text-transform: uppercase; color: #6b7280;">Objet</p>
                    <p style="font-weight: 600; color: #0a0e1a; font-size: 0.875rem;" id="preview-objet">-</p>
                </div>
                
                <!-- Lignes -->
                <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse; margin-bottom: 1rem;">
                    <thead>
                        <tr style="background: #0a0e1a; color: white;">
                            <th style="padding: 0.5rem; text-align: left;">Description</th>
                            <th style="padding: 0.5rem; text-align: right;">Qté</th>
                            <th style="padding: 0.5rem; text-align: right;">P.U.</th>
                            <th style="padding: 0.5rem; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="preview-lignes">
                        <tr><td colspan="4" style="padding: 0.5rem; color: #6b7280; text-align: center;">Aucune ligne</td></tr>
                    </tbody>
                    <tfoot id="preview-totaux">
                        <tr>
                            <td colspan="3" style="padding: 0.5rem; text-align: right; font-weight: 600;">Total</td>
                            <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: #5bbce4;">0,00 €</td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Mention TVA -->
                <p style="text-align: right; font-size: 0.625rem; color: #6b7280; font-style: italic;" id="preview-mention-tva">
                    TVA non applicable, art. 293 B du CGI
                </p>
            </div>
        </div>
    </div>
</div>

<style>
#billing-grid { gap: 2rem; align-items: start; }

.ligne-item {
    background: var(--bs-bg-dark);
    border: 1px solid var(--bs-border);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.ligne-item:hover {
    background: var(--bs-bg-hover);
    border-color: var(--bs-accent);
}

.ligne-item:hover .ligne-total, 
.ligne-item:hover .form-label {
    color: white;
}

.ligne-item:hover input,
.ligne-item:hover select,
.ligne-item:hover textarea {
    background: var(--bs-bg-card);
    color: white;
}

.ligne-item.expanded {
    border-color: var(--bs-accent);
}

.ligne-main {
    display: grid;
    grid-template-columns: 1fr 80px 120px auto;
    gap: 0.5rem;
    align-items: center;
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

.ligne-description textarea {
    width: 100%;
    min-height: 50px;
    resize: vertical;
}

.sous-lignes-container {
    margin-top: 0.75rem;
    padding-left: 1rem;
    border-left: 2px solid var(--bs-accent);
}

.sous-ligne-item {
    display: grid;
    grid-template-columns: 1fr 60px 80px 30px;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.sous-ligne-item input {
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
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

.preview-card { position: sticky; top: 1rem; }

@media (max-width: 768px) {
    #billing-grid { gap: 1rem; }
    .preview-card { position: static; margin-top: 1rem; }
    .ligne-main {
        grid-template-columns: 1fr 1fr;
    }
    .ligne-main input[name*="description"] { grid-column: span 2; }
    .ligne-actions { grid-column: span 2; justify-content: flex-end; }
    .sous-ligne-item {
        grid-template-columns: 1fr 1fr;
    }
    .sous-ligne-item input:first-child { grid-column: span 2; }
}

.radio-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem;
    border: 2px solid var(--bs-border);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.radio-card:hover { border-color: var(--bs-accent); }
.radio-card:has(input:checked) {
    border-color: var(--bs-accent);
    background: rgba(91, 188, 228, 0.1);
}
.radio-card input { display: none; }
.radio-card span { font-weight: 600; font-size: 0.875rem; }
.radio-card small { color: var(--bs-text-muted); font-size: 0.75rem; }
</style>

@push('scripts')
<script>
let ligneIndex = 0;

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
            <input type="text" name="lignes[${ligneIndex}][description]" class="form-input" 
                   placeholder="Description" value="${escapeHtml(description)}" required oninput="updatePreview()">
            <input type="number" name="lignes[${ligneIndex}][quantite]" class="form-input ligne-qte" 
                   value="${quantite}" min="0.01" step="0.01" required oninput="updatePreview()">
            <input type="number" name="lignes[${ligneIndex}][prix_unitaire]" class="form-input ligne-prix" 
                   placeholder="0.00" value="${prixUnitaire}" min="0" step="0.01" oninput="updatePreview()">
            <div class="ligne-actions">
                <button type="button" onclick="toggleDetails(this)" class="btn-expand ${hasDetails ? 'active' : ''}" title="Détails">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <button type="button" onclick="supprimerLigne(this)" class="btn btn-danger btn-sm">×</button>
            </div>
        </div>
        <div class="ligne-details" style="display: ${hasDetails ? 'block' : 'none'};">
            <div class="ligne-description">
                <textarea name="lignes[${ligneIndex}][details]" class="form-input" 
                          placeholder="Description détaillée (optionnel)" 
                          oninput="updatePreview()">${escapeHtml(details)}</textarea>
            </div>
            <div class="sous-lignes-container">
                <p style="font-size: 0.7rem; color: var(--bs-text-muted); margin-bottom: 0.5rem;">Sous-lignes de détail</p>
                <div class="sous-lignes-list" data-ligne="${ligneIndex}"></div>
                <button type="button" onclick="ajouterSousLigne(${ligneIndex})" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
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
    updatePreview();
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
               class="form-input" placeholder="Détail..." value="${escapeHtml(description)}" 
               oninput="updatePreview()">
        <input type="number" name="lignes[${ligneIdx}][sous_lignes][${sousLigneIdx}][quantite]" 
               class="form-input" value="${quantite}" min="0.01" step="0.01" 
               oninput="updatePreview()">
        <input type="number" name="lignes[${ligneIdx}][sous_lignes][${sousLigneIdx}][prix_unitaire]" 
               class="form-input" placeholder="0" value="${prixUnitaire}" min="0" step="0.01" 
               oninput="updatePreview()">
        <button type="button" onclick="supprimerSousLigne(this)" class="btn btn-danger btn-sm" style="padding: 0.2rem 0.4rem;">×</button>
    `;
    
    container.appendChild(div);
    updatePreview();
}

function supprimerSousLigne(btn) {
    btn.closest('.sous-ligne-item').remove();
    updatePreview();
}

function supprimerLigne(btn) {
    btn.closest('.ligne-item').remove();
    updatePreview();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updatePreview() {
    // Client
    const clientSelect = document.getElementById('client-select');
    const selectedOption = clientSelect.options[clientSelect.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const societe = selectedOption.dataset.societe;
        const nom = selectedOption.dataset.nom;
        const prenom = selectedOption.dataset.prenom || '';
        document.getElementById('preview-client').textContent = societe || `${nom} ${prenom}`;
        
        const adresse = selectedOption.dataset.adresse;
        const cp = selectedOption.dataset.cp;
        const ville = selectedOption.dataset.ville;
        document.getElementById('preview-client-adresse').textContent = adresse ? `${adresse}, ${cp} ${ville}` : '';
    } else {
        document.getElementById('preview-client').textContent = 'Sélectionnez un client';
        document.getElementById('preview-client-adresse').textContent = '';
    }
    
    // Objet
    const objet = document.getElementById('objet-input').value;
    document.getElementById('preview-objet').textContent = objet || '-';
    
    // Date
    const dateInput = document.getElementById('date_facture').value;
    if (dateInput) {
        const [year, month, day] = dateInput.split('-');
        document.getElementById('preview-date').textContent = `${day}/${month}/${year}`;
    }
    
    // Lignes
    const lignesContainer = document.getElementById('preview-lignes');
    const lignes = document.querySelectorAll('#lignes-container .ligne-item');
    let html = '';
    let totalHT = 0;
    
    lignes.forEach(ligne => {
        const desc = ligne.querySelector('input[name*="description"]').value;
        const qte = parseFloat(ligne.querySelector('.ligne-qte').value) || 0;
        const sousLignes = ligne.querySelectorAll('.sous-ligne-item');
        
        let pu = parseFloat(ligne.querySelector('.ligne-prix').value) || 0;
        let total = 0;
        
        // Si sous-lignes, calculer le total à partir d'elles
        if (sousLignes.length > 0) {
            let sousTotal = 0;
            sousLignes.forEach(sl => {
                const slQte = parseFloat(sl.querySelector('input[name*="quantite"]').value) || 0;
                const slPrix = parseFloat(sl.querySelector('input[name*="prix_unitaire"]').value) || 0;
                sousTotal += slQte * slPrix;
            });
            pu = sousTotal;
            total = sousTotal * qte;
        } else {
            total = qte * pu;
        }
        
        totalHT += total;
        
        if (desc) {
            const hasSL = sousLignes.length > 0;
            html += `<tr style="border-bottom: 1px solid #e5e7eb; ${hasSL ? 'background: #fafbfc;' : ''}">
                <td style="padding: 0.5rem; color: #0a0e1a; ${hasSL ? 'font-weight: 600;' : ''}">${escapeHtml(desc)}</td>
                <td style="padding: 0.5rem; text-align: right; color: #6b7280;">${qte}</td>
                <td style="padding: 0.5rem; text-align: right; color: #6b7280;">${pu.toFixed(2)} €</td>
                <td style="padding: 0.5rem; text-align: right; font-weight: 600;">${total.toFixed(2)} €</td>
            </tr>`;
            
            // Sous-lignes dans preview
            if (hasSL) {
                sousLignes.forEach(sl => {
                    const slDesc = sl.querySelector('input[name*="description"]').value || '-';
                    const slQte = parseFloat(sl.querySelector('input[name*="quantite"]').value) || 0;
                    const slPrix = parseFloat(sl.querySelector('input[name*="prix_unitaire"]').value) || 0;
                    const slTotal = slQte * slPrix;
                    
                    html += `<tr style="border-bottom: 1px dashed #e5e7eb; background: #f9fafb;">
                        <td style="padding: 0.35rem 0.5rem 0.35rem 1.5rem; color: #6b7280; font-size: 0.65rem;">↳ ${escapeHtml(slDesc)}</td>
                        <td style="padding: 0.35rem 0.5rem; text-align: right; color: #9ca3af; font-size: 0.65rem;">${slQte}</td>
                        <td style="padding: 0.35rem 0.5rem; text-align: right; color: #9ca3af; font-size: 0.65rem;">${slPrix.toFixed(2)} €</td>
                        <td style="padding: 0.35rem 0.5rem; text-align: right; color: #6b7280; font-size: 0.65rem;">${slTotal.toFixed(2)} €</td>
                    </tr>`;
                });
            }
        }
    });
    
    if (!html) {
        html = '<tr><td colspan="4" style="padding: 0.5rem; color: #6b7280; text-align: center;">Aucune ligne</td></tr>';
    }
    lignesContainer.innerHTML = html;
    
    // TVA
    const modeTva = document.querySelector('input[name="mode_tva"]:checked').value;
    const tauxTva = parseFloat(document.getElementById('taux-tva').value) || 20;
    
    // Afficher/masquer le taux TVA
    document.getElementById('taux-tva-container').style.display = (modeTva !== 'non_assujetti') ? 'block' : 'none';
    
    // Calculs et totaux
    let totauxHtml = '';
    let mentionTva = '';
    
    if (modeTva === 'non_assujetti') {
        totauxHtml = `<tr>
            <td colspan="3" style="padding: 0.5rem; text-align: right; font-weight: 600;">Total HT = TTC</td>
            <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: #5bbce4;">${totalHT.toFixed(2)} €</td>
        </tr>`;
        mentionTva = 'TVA non applicable, art. 293 B du CGI';
    } else if (modeTva === 'ht') {
        const tva = totalHT * (tauxTva / 100);
        const ttc = totalHT + tva;
        totauxHtml = `<tr>
            <td colspan="3" style="padding: 0.5rem; text-align: right;">Total HT</td>
            <td style="padding: 0.5rem; text-align: right;">${totalHT.toFixed(2)} €</td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 0.5rem; text-align: right; color: #6b7280;">TVA ${tauxTva}%</td>
            <td style="padding: 0.5rem; text-align: right; color: #6b7280;">${tva.toFixed(2)} €</td>
        </tr>
        <tr style="border-top: 2px solid #e5e7eb;">
            <td colspan="3" style="padding: 0.5rem; text-align: right; font-weight: 600;">Total TTC</td>
            <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: #5bbce4;">${ttc.toFixed(2)} €</td>
        </tr>`;
        mentionTva = '';
    } else if (modeTva === 'ttc') {
        const ht = totalHT / (1 + tauxTva / 100);
        const tva = totalHT - ht;
        totauxHtml = `<tr>
            <td colspan="3" style="padding: 0.5rem; text-align: right; font-weight: 600;">Total TTC</td>
            <td style="padding: 0.5rem; text-align: right; font-weight: 700; color: #5bbce4;">${totalHT.toFixed(2)} €</td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.625rem;">dont TVA ${tauxTva}%</td>
            <td style="padding: 0.5rem; text-align: right; color: #6b7280; font-size: 0.625rem;">${tva.toFixed(2)} €</td>
        </tr>`;
        mentionTva = '';
    }
    
    document.getElementById('preview-totaux').innerHTML = totauxHtml;
    document.getElementById('preview-mention-tva').textContent = mentionTva;
}

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($facture) && $facture->lignes)
        @foreach($facture->lignes as $ligne)
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
    updatePreview();
});
</script>
@endpush
@endsection
