@extends('brightshell.layout')

@section('title', 'Nouveau document')

@section('content')
<div class="devis-builder">
    <div class="card">
        <form action="{{ route('brightshell.legals.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-2 mb-4" style="align-items: end;">
                <div class="form-group">
                    <label class="form-label">Modèle rapide</label>
                    <select id="modele-select" class="form-input" onchange="chargerModele()">
                        <option value="">Sélectionner un modèle...</option>
                        <option value="attestation_stage">Attestation de stage</option>
                        <option value="justificatif_presence">Justificatif de présence</option>
                        <option value="courrier_libre">Courrier libre</option>
                        <option value="mise_en_demeure">Mise en demeure (impayé)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Client (optionnel)</label>
                    <select name="client_id" class="form-input" onchange="remplirClient(this)">
                        <option value="">Destinataire libre</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" 
                                data-nom="{{ $client->societe ?: $client->nom }}"
                                data-prenom="{{ $client->societe ? '' : $client->prenom }}"
                                data-adresse="{{ $client->adresse . "\n" . $client->code_postal . ' ' . $client->ville }}">
                            {{ $client->nom }} {{ $client->prenom }} {{ $client->societe ? "({$client->societe})" : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Type de document</label>
                    <select name="type" class="form-input" required>
                        <option value="attestation">Attestation</option>
                        <option value="courrier">Courrier</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Titre du document</label>
                    <input type="text" name="titre" id="titre" class="form-input" oninput="updatePreview()" placeholder="Ex: Attestation de fin de stage" required>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date_document" id="date_document" class="form-input" oninput="updatePreview()" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fait à</label>
                    <input type="text" name="lieu" id="lieu" class="form-input" oninput="updatePreview()" value="Vichy" required>
                </div>
            </div>
            
            <fieldset style="border: 1px solid var(--bs-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <legend style="padding: 0 0.5rem; color: var(--bs-text-muted); font-size: 0.875rem;">Destinataire</legend>
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Titre / Qualité</label>
                        <input type="text" name="destinataire_titre" id="destinataire-titre" class="form-input" oninput="updatePreview()" placeholder="Ex: Juge d'instruction">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="destinataire_nom" id="destinataire-nom" class="form-input" oninput="updatePreview()" placeholder="Nom">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="destinataire_prenom" id="destinataire-prenom" class="form-input" oninput="updatePreview()" placeholder="Prénom">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse postale</label>
                    <textarea name="destinataire_adresse" id="destinataire-adresse" class="form-textarea" rows="3" oninput="updatePreview()" placeholder="Adresse..."></textarea>
                </div>
            </fieldset>
            
            <div class="form-group">
                <label class="form-label">Contenu du courrier</label>
                <textarea name="contenu" id="contenu" class="form-textarea" rows="12" oninput="updatePreview()" required style="font-family: monospace;"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Documents joints (un par ligne)</label>
                <textarea name="pieces_jointes" id="pieces_jointes" class="form-textarea" rows="3" oninput="updatePreview()" placeholder="- Pièce 1\n- Pièce 2"></textarea>
            </div>
            
            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Créer le document</button>
                <a href="{{ route('brightshell.legals') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

    <!-- Preview -->
    <div style="position: sticky; top: 100px;">
        <div class="card" style="background: white; color: #1a1a1a; padding: 40px; min-height: 800px; font-family: 'DejaVu Sans', sans-serif; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div id="preview-header" style="overflow: hidden; margin-bottom: 40px;">
                <div style="float: right; text-align: right; width: 60%;">
                    <div id="preview-date-lieu" style="margin-bottom: 20px; font-size: 11px;">Vichy, le {{ date('d/m/Y') }}</div>
                    <div id="preview-destinataire" style="background: #f9fafb; padding: 15px; border-radius: 4px; text-align: left; margin-left: 20%; min-width: 250px;">
                        <div id="prev-dest-titre" style="font-weight: bold; font-size: 12px;"></div>
                        <div id="prev-dest-nom" style="font-weight: bold; font-size: 13px;">[Destinataire]</div>
                        <div id="prev-dest-adresse" style="white-space: pre-line; font-size: 11px;">[Adresse]</div>
                    </div>
                </div>
            </div>
            
            <div id="preview-titre" style="text-align: center; font-size: 18px; font-weight: bold; margin: 40px 0; text-decoration: underline;">[Titre]</div>
            
            <div id="preview-contenu" style="font-size: 11px; text-align: justify; line-height: 1.6; min-height: 300px;">
                [Contenu du document]
            </div>

            <div id="preview-pieces" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px; font-size: 10px;">
                <strong style="text-transform: uppercase;">Pièces Jointes :</strong>
                <div id="prev-pieces-list" style="margin-top: 5px; white-space: pre-line;"></div>
            </div>
            
            <div id="preview-signature" style="text-align: right; margin-top: 40px; font-size: 11px;">
                <div style="font-weight: bold;">Lucas Espinar</div>
                <div>BrightShell</div>
                <div style="color: #ccc; font-style: italic; margin-top: 10px; font-size: 10px;">(Signature)</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const modeles = {
    attestation_stage: {
        titre: "Attestation de stage",
        type: "attestation",
        contenu: `<p>Je soussigné(e) <strong>Lucas Espinar</strong>, agissant en qualité d’entrepreneur individuel sous l'enseigne <strong>BrightShell</strong>, certifie par la présente que :</p>\n\n<p style="text-align: center; font-weight: bold;">Madame/Monsieur [NOM_STAGIAIRE]</p>\n\n<p>A effectué un stage au sein de mon entreprise du [DATE_DEBUT] au [DATE_FIN]. Durant cette période, le stagiaire a participé activement aux missions de développement web et gestion de projet.</p>\n\n<p>Cette attestation est délivrée pour servir et valoir ce que de droit.</p>`
    },
    justificatif_presence: {
        titre: "Justificatif de présence",
        type: "attestation",
        contenu: `<p>Je soussigné(e) <strong>Lucas Espinar</strong>, agissant en qualité d’entrepreneur individuel sous l'enseigne <strong>BrightShell</strong>, atteste que :</p>\n\n<p style="text-align: center; font-weight: bold;">[NOM_PERSONNE]</p>\n\n<p>Était présent(e) dans nos locaux le [DATE] de [HEURE_DEBUT] à [HEURE_FIN] pour un professionnel concernant [SUJET].</p>`
    },
    courrier_libre: {
        titre: "Courrier Libre",
        type: "courrier",
        contenu: `<p>À l'attention de Madame, Monsieur,</p>\n\n<p>Par la présente, je souhaite vous informer que...</p>\n\n<p>[VOTRE TEXTE ICI]</p>\n\n<p>Je vous remercie de la bienveillance que vous porterez à cette demande et vous prie d'agréer, l'expression de ma haute considération.</p>`
    },
    mise_en_demeure: {
        titre: "Mise en demeure de payer",
        type: "courrier",
        contenu: `<p><strong>Objet : Mise en demeure de payer - Facture N° [NUMERO]</strong></p>\n\n<p>Madame, Monsieur,</p>\n\n<p>Sauf erreur ou omission de ma part, je constate que la facture N° [NUMERO] datée du [DATE_FACTURE] reste impayée à ce jour.</p>\n\n<p>Par la présente, je vous mets en demeure de procéder au règlement sous huitaine. À défaut, je me verrai contraint d'engager les procédures légales de recouvrement.</p>`
    }
};

function chargerModele() {
    const select = document.getElementById('modele-select');
    const key = select.value;
    if (key && modeles[key]) {
        const m = modeles[key];
        document.getElementById('titre').value = m.titre;
        document.querySelector(`select[name="type"]`).value = m.type;
        document.getElementById('contenu').value = m.contenu;
        updatePreview();
    }
}

function remplirClient(select) {
    const option = select.options[select.selectedIndex];
    if (option && option.value) {
        document.getElementById('destinataire-nom').value = option.dataset.nom || '';
        document.getElementById('destinataire-prenom').value = option.dataset.prenom || '';
        document.getElementById('destinataire-adresse').value = option.dataset.adresse || '';
    } else {
        document.getElementById('destinataire-nom').value = '';
        document.getElementById('destinataire-prenom').value = '';
        document.getElementById('destinataire-adresse').value = '';
    }
    updatePreview();
}

function updatePreview() {
    const titre = document.getElementById('titre').value || '[Titre]';
    const lieu = document.getElementById('lieu').value || 'Vichy';
    const date = document.getElementById('date_document').value;
    const dateFormatted = date ? new Date(date).toLocaleDateString('fr-FR') : '[Date]';
    
    const dNom = document.getElementById('destinataire-nom').value || '';
    const dPrenom = document.getElementById('destinataire-prenom').value || '';
    const dTitre = document.getElementById('destinataire-titre').value || '';
    const dAdresse = document.getElementById('destinataire-adresse').value || '[Adresse]';
    
    document.getElementById('preview-date-lieu').innerText = `${lieu}, le ${dateFormatted}`;
    document.getElementById('prev-dest-titre').innerText = dTitre;
    document.getElementById('prev-dest-nom').innerText = 'À l’attention de ' + dPrenom + ' ' + dNom;
    document.getElementById('prev-dest-adresse').innerText = dAdresse;
    document.getElementById('preview-titre').innerText = titre;
    document.getElementById('preview-contenu').innerHTML = document.getElementById('contenu').value || '[Contenu]';
    
    const pieces = document.getElementById('pieces_jointes').value;
    if (pieces.trim()) {
        document.getElementById('preview-pieces').style.display = 'block';
        document.getElementById('prev-pieces-list').innerText = pieces;
    } else {
        document.getElementById('preview-pieces').style.display = 'none';
    }
}

// Initial preview
window.onload = updatePreview;
</script>
@endpush
@endsection
