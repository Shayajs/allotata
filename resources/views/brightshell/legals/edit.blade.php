@extends('brightshell.layout')

@section('title', 'Modifier le document')

@section('content')
<div class="devis-builder">
    <div class="card">
        <form action="{{ route('brightshell.legals.update', $document->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-2 mb-4" style="align-items: end;">
                <div class="form-group">
                    <label class="form-label">Client (optionnel)</label>
                    <select name="client_id" class="form-input" onchange="remplirClient(this)">
                        <option value="">Destinataire libre</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" 
                                {{ $document->client_id == $client->id ? 'selected' : '' }}
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
                        <option value="attestation" {{ $document->type == 'attestation' ? 'selected' : '' }}>Attestation</option>
                        <option value="courrier" {{ $document->type == 'courrier' ? 'selected' : '' }}>Courrier</option>
                        <option value="autre" {{ $document->type == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Titre du document</label>
                    <input type="text" name="titre" id="titre" class="form-input" oninput="updatePreview()" value="{{ $document->titre }}" placeholder="Ex: Attestation de fin de stage" required>
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date_document" id="date_document" class="form-input" oninput="updatePreview()" value="{{ $document->date_document }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fait à</label>
                    <input type="text" name="lieu" id="lieu" class="form-input" oninput="updatePreview()" value="{{ $document->lieu }}" required>
                </div>
            </div>
            
            <fieldset style="border: 1px solid var(--bs-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <legend style="padding: 0 0.5rem; color: var(--bs-text-muted); font-size: 0.875rem;">Destinataire</legend>
                <div class="grid grid-3">
                    <div class="form-group">
                        <label class="form-label">Titre / Qualité</label>
                        <input type="text" name="destinataire_titre" id="destinataire-titre" class="form-input" oninput="updatePreview()" value="{{ $document->destinataire_titre }}" placeholder="Ex: Juge d'instruction">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="destinataire_nom" id="destinataire-nom" class="form-input" oninput="updatePreview()" value="{{ $document->destinataire_nom }}" placeholder="Nom">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="destinataire_prenom" id="destinataire-prenom" class="form-input" oninput="updatePreview()" value="{{ $document->destinataire_prenom }}" placeholder="Prénom">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse postale</label>
                    <textarea name="destinataire_adresse" id="destinataire-adresse" class="form-textarea" rows="3" oninput="updatePreview()" placeholder="Adresse...">{{ $document->destinataire_adresse }}</textarea>
                </div>
            </fieldset>
            
            <div class="form-group">
                <label class="form-label">Contenu du courrier</label>
                <textarea name="contenu" id="contenu" class="form-textarea" rows="12" oninput="updatePreview()" required style="font-family: monospace;">{{ $document->contenu }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Documents joints (un par ligne)</label>
                <textarea name="pieces_jointes" id="pieces_jointes" class="form-textarea" rows="3" oninput="updatePreview()" placeholder="- Pièce 1\n- Pièce 2">{{ $document->pieces_jointes }}</textarea>
            </div>
            
            <div class="flex gap-2" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="{{ route('brightshell.legals') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

    <!-- Preview -->
    <div style="position: sticky; top: 100px;">
        <div class="card" style="background: white; color: #1a1a1a; padding: 40px; min-height: 800px; font-family: 'DejaVu Sans', sans-serif; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div id="preview-header" style="overflow: hidden; margin-bottom: 40px;">
                <div style="float: right; text-align: right; width: 60%;">
                    <div id="preview-date-lieu" style="margin-bottom: 20px; font-size: 11px;">{{ $document->lieu }}, le {{ \Carbon\Carbon::parse($document->date_document)->format('d/m/Y') }}</div>
                    <div id="preview-destinataire" style="background: #f9fafb; padding: 15px; border-radius: 4px; text-align: left; margin-left: 20%; min-width: 250px;">
                        <div id="prev-dest-titre" style="font-weight: bold; font-size: 12px;">{{ $document->destinataire_titre }}</div>
                        <div id="prev-dest-nom" style="font-weight: bold; font-size: 13px;">{{ ($document->destinataire_titre ? 'À l’attention de ' : '') . $document->destinataire_prenom . ' ' . $document->destinataire_nom }}</div>
                        <div id="prev-dest-adresse" style="white-space: pre-line; font-size: 11px;">{{ $document->destinataire_adresse }}</div>
                    </div>
                </div>
            </div>
            
            <div id="preview-titre" style="text-align: center; font-size: 18px; font-weight: bold; margin: 40px 0; text-decoration: underline;">{{ $document->titre }}</div>
            
            <div id="preview-contenu" style="font-size: 11px; text-align: justify; line-height: 1.6; min-height: 300px;">
                {!! $document->contenu !!}
            </div>

            <div id="preview-pieces" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px; font-size: 10px; {{ $document->pieces_jointes ? '' : 'display: none;' }}">
                <strong style="text-transform: uppercase;">Pièces Jointes :</strong>
                <div id="prev-pieces-list" style="margin-top: 5px; white-space: pre-line;">{{ $document->pieces_jointes }}</div>
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
