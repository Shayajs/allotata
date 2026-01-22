@extends('brightshell.layout')

@section('title', 'Nouveau document')

@section('content')
<div class="card" style="max-width: 900px;">
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
                            data-nom="{{ $client->societe ?: ($client->nom . ' ' . $client->prenom) }}"
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
                <input type="text" name="titre" id="titre" class="form-input" placeholder="Ex: Attestation de fin de stage" required>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="date_document" class="form-input" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Fait à</label>
                <input type="text" name="lieu" class="form-input" value="Vichy" required>
            </div>
        </div>
        
        <fieldset style="border: 1px solid var(--bs-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
            <legend style="padding: 0 0.5rem; color: var(--bs-text-muted); font-size: 0.875rem;">Destinataire</legend>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Nom / Société</label>
                    <input type="text" name="destinataire_nom" id="destinataire-nom" class="form-input" placeholder="Nom du destinataire">
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse complète</label>
                    <textarea name="destinataire_adresse" id="destinataire-adresse" class="form-textarea" rows="3" placeholder="Adresse..."></textarea>
                </div>
            </div>
        </fieldset>
        
        <div class="form-group">
            <label class="form-label">Contenu du courrier</label>
            <p class="text-xs text-muted mb-2">Vous pouvez utiliser du HTML simple (p, br, strong, ul, li).</p>
            <textarea name="contenu" id="contenu" class="form-textarea" rows="15" required style="font-family: monospace;"></textarea>
        </div>
        
        <div class="flex gap-2" style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Créer le document</button>
            <a href="{{ route('brightshell.legals') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
const modeles = {
    attestation_stage: {
        titre: "Attestation de stage",
        type: "attestation",
        contenu: `<p>Je soussigné(e) <strong>Lucas Espinar</strong>, agissant en qualité d'Entrepreneur Individuel sous l'enseigne <strong>BrightShell</strong>, certifie par la présente que :</p>

<p style="text-align: center; font-weight: bold; margin: 20px 0;">Madame/Monsieur [NOM_STAGIAIRE]</p>

<p>A effectué un stage au sein de mon entreprise du [DATE_DEBUT] au [DATE_FIN].</p>

<p>Durant cette période, le stagiaire a participé activement aux missions suivantes :</p>
<ul>
    <li>Développement d'applications web</li>
    <li>Gestion de projet</li>
    <li>...</li>
</ul>

<p>Cette attestation est délivrée pour servir et valoir ce que de droit.</p>`
    },
    justificatif_presence: {
        titre: "Justificatif de présence",
        type: "attestation",
        contenu: `<p>Je soussigné(e) <strong>Lucas Espinar</strong>, gérant de <strong>BrightShell EI</strong>, atteste que :</p>

<p style="text-align: center; font-weight: bold; margin: 20px 0;">Madame/Monsieur [NOM_PERSONNE]</p>

<p>Était présent(e) dans nos locaux le [DATE] de [HEURE_DEBUT] à [HEURE_FIN] pour un rendez-vous professionnel concernant [SUJET].</p>

<p>Pour faire valoir ce que de droit.</p>`
    },
    courrier_libre: {
        titre: "Courrier",
        type: "courrier",
        contenu: `<p>Madame, Monsieur,</p>

<p>Par la présente, je souhaite vous informer que...</p>

<p>[VOTRE TEXTE ICI]</p>

<p>Restant à votre disposition pour tout complément d'information, je vous prie d'agréer, Madame, Monsieur, l'expression de mes salutations distinguées.</p>`
    },
    mise_en_demeure: {
        titre: "Mise en demeure de payer",
        type: "courrier",
        contenu: `<p><strong>Objet : Mise en demeure de payer - Facture N° [NUMERO]</strong></p>

<p>Madame, Monsieur,</p>

<p>Sauf erreur ou omission de ma part, je constate que la facture N° [NUMERO] datée du [DATE_FACTURE] pour un montant de [MONTANT] € reste impayée à ce jour, malgré mes précédentes relances.</p>

<p>Par la présente, je vous mets en demeure de procéder au règlement de la somme due sous huitaine à réception de ce courrier.</p>

<p>À défaut de paiement dans ce délai, je me verrai contraint d'engager les procédures légales nécessaires au recouvrement de ma créance.</p>

<p>Je vous prie d'agréer, Madame, Monsieur, l'expression de mes salutations distinguées.</p>`
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
    }
}

function remplirClient(select) {
    const option = select.options[select.selectedIndex];
    if (option && option.value) {
        document.getElementById('destinataire-nom').value = option.dataset.nom;
        document.getElementById('destinataire-adresse').value = option.dataset.adresse;
    } else {
        document.getElementById('destinataire-nom').value = '';
        document.getElementById('destinataire-adresse').value = '';
    }
}
</script>
@endpush
@endsection
