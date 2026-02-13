<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export des données personnelles — Allo Tata</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }
        .page-break { page-break-after: always; }

        .header { background: #f0fdf4; border-bottom: 3px solid #22c55e; padding: 20px 30px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #15803d; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #64748b; }

        .container { padding: 0 30px; }

        h2 { font-size: 14px; color: #15803d; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin: 20px 0 10px 0; }
        h3 { font-size: 12px; color: #334155; margin: 12px 0 6px 0; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
        table th { background: #f8fafc; color: #64748b; text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; }
        table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
        table tr:nth-child(even) td { background: #f8fafc; }

        .info-grid { display: table; width: 100%; margin-bottom: 12px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; padding: 4px 8px; font-weight: 600; color: #64748b; font-size: 10px; }
        .info-value { display: table-cell; padding: 4px 8px; color: #1e293b; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; padding: 10px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; }

        .legal-notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 10px 14px; margin: 16px 0; font-size: 10px; color: #92400e; }

        .empty { color: #94a3b8; font-style: italic; }
        .truncate { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>Export des données personnelles</h1>
        <p>
            Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} — 
            Conformément au RGPD (Règlement UE 2016/679), articles 15 et 20
        </p>
    </div>

    <div class="container">
        <!-- Profil -->
        <h2>Profil utilisateur</h2>
        @if(isset($data['profil.json']))
            @php $profil = $data['profil.json']; @endphp
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Nom complet</div><div class="info-value">{{ $profil['nom'] ?? '—' }} {{ $profil['prenom'] ?? '' }}</div></div>
                <div class="info-row"><div class="info-label">Email</div><div class="info-value">{{ $profil['email'] ?? '—' }}</div></div>
                <div class="info-row"><div class="info-label">Téléphone</div><div class="info-value">{{ $profil['telephone'] ?? '—' }}</div></div>
                <div class="info-row"><div class="info-label">Date de naissance</div><div class="info-value">{{ $profil['date_naissance'] ?? '—' }}</div></div>
                <div class="info-row"><div class="info-label">Adresse</div><div class="info-value">{{ $profil['adresse'] ?? '—' }}, {{ $profil['code_postal'] ?? '' }} {{ $profil['ville'] ?? '' }}</div></div>
                <div class="info-row"><div class="info-label">Type de compte</div><div class="info-value">{{ ($profil['est_gerant'] ?? false) ? 'Gérant' : '' }}{{ ($profil['est_client'] ?? false) ? ' Client' : '' }}</div></div>
                <div class="info-row"><div class="info-label">Compte créé le</div><div class="info-value">{{ $profil['compte_cree_le'] ? \Carbon\Carbon::parse($profil['compte_cree_le'])->format('d/m/Y') : '—' }}</div></div>
                <div class="info-row"><div class="info-label">Tracking consent</div><div class="info-value">{{ ($profil['tracking_consent'] ?? false) ? 'Oui' : 'Non' }}</div></div>
            </div>
        @endif

        <!-- Réservations -->
        <h2>Réservations ({{ count($data['reservations.json'] ?? []) }})</h2>
        @if(!empty($data['reservations.json']))
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Entreprise</th>
                        <th>Service</th>
                        <th>Prix</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($data['reservations.json'], 0, 50) as $r)
                        <tr>
                            <td>{{ isset($r['date_reservation']) ? \Carbon\Carbon::parse($r['date_reservation'])->format('d/m/Y H:i') : '—' }}</td>
                            <td>{{ $r['entreprise'] ?? '—' }}</td>
                            <td>{{ $r['service'] ?? '—' }}</td>
                            <td>{{ isset($r['prix']) ? number_format($r['prix'], 2, ',', ' ') . ' €' : '—' }}</td>
                            <td><span class="badge badge-gray">{{ $r['statut'] ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(count($data['reservations.json']) > 50)
                <p class="empty">... et {{ count($data['reservations.json']) - 50 }} autres réservations (voir le fichier JSON pour la liste complète).</p>
            @endif
        @else
            <p class="empty">Aucune réservation.</p>
        @endif

        <!-- Factures -->
        <h2>Factures ({{ count($data['factures.json'] ?? []) }})</h2>
        @if(!empty($data['factures.json']))
            <table>
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Entreprise</th>
                        <th>Montant TTC</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($data['factures.json'], 0, 50) as $f)
                        <tr>
                            <td>{{ $f['numero_facture'] ?? '—' }}</td>
                            <td>{{ isset($f['date_facture']) ? \Carbon\Carbon::parse($f['date_facture'])->format('d/m/Y') : '—' }}</td>
                            <td>{{ $f['entreprise'] ?? '—' }}</td>
                            <td>{{ isset($f['montant_ttc']) ? number_format($f['montant_ttc'], 2, ',', ' ') . ' €' : '—' }}</td>
                            <td><span class="badge badge-gray">{{ $f['statut'] ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Aucune facture.</p>
        @endif

        <!-- Conversations -->
        <h2>Conversations ({{ count($data['conversations.json'] ?? []) }})</h2>
        @if(!empty($data['conversations.json']))
            @foreach(array_slice($data['conversations.json'], 0, 10) as $conv)
                <h3>Conversation avec {{ $conv['entreprise'] ?? 'Entreprise inconnue' }} — {{ count($conv['messages'] ?? []) }} message(s)</h3>
                @if(!empty($conv['messages']))
                    <table>
                        <thead><tr><th>Date</th><th>Expéditeur</th><th>Message</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($conv['messages'], 0, 20) as $m)
                                <tr>
                                    <td style="width: 15%;">{{ isset($m['date']) ? \Carbon\Carbon::parse($m['date'])->format('d/m/Y H:i') : '—' }}</td>
                                    <td style="width: 10%;">{{ ($m['est_de_moi'] ?? false) ? 'Moi' : 'Entreprise' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($m['contenu'] ?? '', 200) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(count($conv['messages']) > 20)
                        <p class="empty">... et {{ count($conv['messages']) - 20 }} autres messages.</p>
                    @endif
                @endif
            @endforeach
        @else
            <p class="empty">Aucune conversation.</p>
        @endif

        <!-- Avis -->
        <h2>Avis laissés ({{ count($data['avis.json'] ?? []) }})</h2>
        @if(!empty($data['avis.json']))
            <table>
                <thead><tr><th>Type</th><th>Entreprise</th><th>Note</th><th>Commentaire</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($data['avis.json'] as $a)
                        <tr>
                            <td>{{ $a['type'] ?? '—' }}</td>
                            <td>{{ $a['entreprise'] ?? '—' }}</td>
                            <td>{{ $a['note'] ?? '—' }}/5</td>
                            <td>{{ \Illuminate\Support\Str::limit($a['commentaire'] ?? '', 100) }}</td>
                            <td>{{ isset($a['date']) ? \Carbon\Carbon::parse($a['date'])->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Aucun avis.</p>
        @endif

        <!-- Forum -->
        @if(isset($data['forum.json']))
            <h2>Forum — Posts ({{ count($data['forum.json']['posts'] ?? []) }}) / Commentaires ({{ count($data['forum.json']['commentaires'] ?? []) }})</h2>
            @if(!empty($data['forum.json']['posts']))
                <table>
                    <thead><tr><th>Titre</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($data['forum.json']['posts'] as $p)
                            <tr>
                                <td>{{ $p['titre'] ?? '—' }}</td>
                                <td>{{ isset($p['date']) ? \Carbon\Carbon::parse($p['date'])->format('d/m/Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty">Aucun post forum.</p>
            @endif
        @endif

        <!-- Entreprises (si gérant) -->
        @if(isset($data['entreprises.json']) && !empty($data['entreprises.json']))
            <div class="page-break"></div>
            <h2>Entreprises gérées ({{ count($data['entreprises.json']) }})</h2>
            @foreach($data['entreprises.json'] as $e)
                <h3>{{ $e['nom'] ?? 'Entreprise' }}</h3>
                <div class="info-grid">
                    <div class="info-row"><div class="info-label">SIREN</div><div class="info-value">{{ $e['siren'] ?? '—' }}</div></div>
                    <div class="info-row"><div class="info-label">Type d'activité</div><div class="info-value">{{ $e['type_activite'] ?? '—' }}</div></div>
                    <div class="info-row"><div class="info-label">Email</div><div class="info-value">{{ $e['email'] ?? '—' }}</div></div>
                    <div class="info-row"><div class="info-label">Téléphone</div><div class="info-value">{{ $e['telephone'] ?? '—' }}</div></div>
                    <div class="info-row"><div class="info-label">Adresse</div><div class="info-value">{{ $e['adresse_rue'] ?? '' }}, {{ $e['code_postal'] ?? '' }} {{ $e['ville'] ?? '' }}</div></div>
                    <div class="info-row"><div class="info-label">Services</div><div class="info-value">{{ count($e['services'] ?? []) }} service(s)</div></div>
                    <div class="info-row"><div class="info-label">Produits</div><div class="info-value">{{ count($e['produits'] ?? []) }} produit(s)</div></div>
                </div>
            @endforeach
        @endif

        <!-- Notice légale -->
        <div class="legal-notice">
            <strong>Notice légale :</strong> Ce document contient l'ensemble des données personnelles vous concernant détenues par Allo Tata, 
            conformément au Règlement Général sur la Protection des Données (RGPD - UE 2016/679). 
            Pour toute question, contactez-nous. Les fichiers JSON joints contiennent les mêmes données dans un format exploitable par machine (droit à la portabilité, article 20).
        </div>
    </div>

    <div class="footer">
        Export RGPD — Allo Tata — {{ $generatedAt->format('d/m/Y H:i') }} — Document confidentiel
    </div>
</body>
</html>
