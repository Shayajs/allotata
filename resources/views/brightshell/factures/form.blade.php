@extends('brightshell.layout')

@section('title', isset($facture) ? 'Modifier la facture' : 'Nouvelle facture')

@section('content')
<div class="grid grid-2" style="gap: 2rem; align-items: start;">
    <!-- Formulaire -->
    <div class="card">
        <form action="{{ isset($facture) ? route('brightshell.factures.update', $facture->id) : route('brightshell.factures.store') }}" method="POST" id="facture-form">
            @csrf
            @if(isset($facture)) @method('PUT') @endif
            
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
                <label class="form-label">Objet *</label>
                <input type="text" name="objet" class="form-input" value="{{ old('objet', $facture->objet ?? '') }}" required id="objet-input" oninput="updatePreview()">
            </div>
            
            <div class="form-group">
                <label class="form-label">Lignes de facture</label>
                <div id="lignes-container">
                    <div class="ligne-header" style="display: grid; grid-template-columns: 1fr 80px 120px 40px; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span class="form-label text-xs">Description</span>
                        <span class="form-label text-xs">Qté</span>
                        <span class="form-label text-xs">Prix unit. €</span>
                        <span></span>
                    </div>
                </div>
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
    <div class="card" style="position: sticky; top: 1rem; background: white; color: #1a1a1a;">
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
                        <p style="color: #6b7280; font-size: 0.75rem;">{{ now()->format('d/m/Y') }}</p>
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
.radio-card:hover {
    border-color: var(--bs-accent);
}
.radio-card:has(input:checked) {
    border-color: var(--bs-accent);
    background: rgba(91, 188, 228, 0.1);
}
.radio-card input {
    display: none;
}
.radio-card span {
    font-weight: 600;
    font-size: 0.875rem;
}
.radio-card small {
    color: var(--bs-text-muted);
    font-size: 0.75rem;
}
#lignes-container .ligne-item:hover {
    background: #1f2937; /* Gris très foncé / Noir */
}
#lignes-container .ligne-item:hover input {
    background: #374151; /* Gris foncé pour l'input */
    color: white; /* Texte blanc */
    border-color: #4b5563;
}
#lignes-container .ligne-item:hover button.btn-danger {
    opacity: 1;
}
#lignes-container .ligne-item:hover button.btn-danger {
    background: #ef4444;
    color: white;
}
</style>

@push('scripts')
<script>
let ligneIndex = 0;

function ajouterLigne(description = '', quantite = 1, prixUnitaire = '') {
    const container = document.getElementById('lignes-container');
    const div = document.createElement('div');
    div.className = 'ligne-item';
    div.style.cssText = 'display: grid; grid-template-columns: 1fr 80px 120px 40px; gap: 0.5rem; margin-bottom: 0.5rem; padding: 0.25rem; border-radius: 4px;';
    div.innerHTML = `
        <input type="text" name="lignes[${ligneIndex}][description]" class="form-input" placeholder="Description" value="${description}" required oninput="updatePreview()">
        <input type="number" name="lignes[${ligneIndex}][quantite]" class="form-input" value="${quantite}" min="0.01" step="0.01" required oninput="updatePreview()">
        <input type="number" name="lignes[${ligneIndex}][prix_unitaire]" class="form-input" placeholder="0.00" value="${prixUnitaire}" min="0" step="0.01" required oninput="updatePreview()">
        <button type="button" onclick="supprimerLigne(this)" class="btn btn-danger btn-sm">x</button>
    `;
    container.appendChild(div);
    ligneIndex++;
    updatePreview();
}

function supprimerLigne(btn) {
    btn.parentElement.remove();
    updatePreview();
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
    
    // Lignes
    const lignesContainer = document.getElementById('preview-lignes');
    const lignes = document.querySelectorAll('#lignes-container .ligne-item');
    let html = '';
    let totalHT = 0;
    
    lignes.forEach(ligne => {
        const desc = ligne.querySelector('input[name*="description"]').value;
        const qte = parseFloat(ligne.querySelector('input[name*="quantite"]').value) || 0;
        const pu = parseFloat(ligne.querySelector('input[name*="prix_unitaire"]').value) || 0;
        const total = qte * pu;
        totalHT += total;
        
        if (desc) {
            html += `<tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 0.5rem; color: #0a0e1a;">${desc}</td>
                <td style="padding: 0.5rem; text-align: right; color: #6b7280;">${qte}</td>
                <td style="padding: 0.5rem; text-align: right; color: #6b7280;">${pu.toFixed(2)} €</td>
                <td style="padding: 0.5rem; text-align: right; font-weight: 600;">${total.toFixed(2)} €</td>
            </tr>`;
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
        ajouterLigne('{{ addslashes($ligne['description'] ?? '') }}', '{{ $ligne['quantite'] ?? 1 }}', '{{ $ligne['prix_unitaire'] ?? '' }}');
        @endforeach
    @else
        ajouterLigne();
    @endif
    updatePreview();
});
</script>
@endpush
@endsection
