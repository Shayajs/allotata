<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->titre }}</title>
    @php
        $couleurs = $couleurs ?? [
            'primary' => '#5bbce4',
            'secondary' => '#0a0e1a',
            'text' => '#1a1a1a',
            'muted' => '#6b7280',
            'background' => '#f9fafb',
            'border' => '#e5e7eb',
        ];
    @endphp
    <style>
        @page {
            margin: 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: {{ $couleurs['text'] }};
            background: white;
            margin: 30px;
        }
        
        table, th, td, p, div {
            font-family: 'DejaVu Sans', sans-serif;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 50px;
        }
        
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: {{ $couleurs['secondary'] }};
            margin-bottom: 5px;
        }
        
        .company-info {
            color: {{ $couleurs['muted'] }};
            font-size: 11px;
        }
        
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .date_lieu {
            margin-bottom: 30px;
        }
        
        .destinataire {
            background: {{ $couleurs['background'] }};
            padding: 20px;
            border-radius: 5px;
            text-align: left;
            margin-left: 20px; /* Décalage pour faire "adresse destinataire" */
            display: inline-block;
            width: 100%;
        }
        
        .destinataire-nom {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .destinataire-adresse {
            white-space: pre-line;
        }
        
        .titre {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 50px 0;
            text-decoration: underline;
            color: {{ $couleurs['secondary'] }};
        }
        
        .content {
            text-align: justify;
            min-height: 400px;
            margin-bottom: 50px;
        }
        
        .signature {
            text-align: right;
            margin-top: 50px;
        }
        
        .signature-nom {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: {{ $couleurs['muted'] }};
            padding-top: 10px;
            border-top: 1px solid {{ $couleurs['border'] }};
        }
    </style>
</head>
<body>
    <div style="border: 2px solid #5bbce4; padding: 40px; min-height: 980px; position: relative; background: #fff;">
        <div class="header">
            <div class="header-left">
                @if(isset($logo) && $logo)
                    @php
                        $type = pathinfo($logo, PATHINFO_EXTENSION);
                        $data = file_get_contents($logo);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    @endphp
                    <img src="{{ $base64 }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px; display: block;">
                @endif
                <div class="company-name" style="color: #5bbce4;">{{ $entreprise['nom'] }}</div>
                <div class="company-info">{{ $entreprise['forme_juridique'] }}</div>
                <div class="company-info">SIRET : {{ $entreprise['siret'] }}</div>
                <div class="company-info">{{ $entreprise['email'] }}</div>
                <div class="company-info">{{ $entreprise['telephone'] }}</div>
            </div>
            
            <div class="header-right">
                <div class="date_lieu">
                    {{ $document->lieu }}, le {{ \Carbon\Carbon::parse($document->date_document)->format('d/m/Y') }}
                </div>
                
                <div class="destinataire">
                    <div style="margin-bottom: 2px;">À l’attention de {{ $document->destinataire_prenom }} {{ $document->destinataire_nom }}</div>
                    @if($document->destinataire_titre)
                        <div style="font-weight: bold; margin-bottom: 5px;">{{ $document->destinataire_titre }}</div>
                    @endif
                    <div class="destinataire-adresse">{{ $document->destinataire_adresse }}</div>
                </div>
            </div>
        </div>
        
        <div class="titre">{{ $document->titre }}</div>
        
        <div class="content">
            {!! $document->contenu !!}
        </div>

        @if($document->pieces_jointes)
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 15px; font-size: 10px;">
            <strong style="text-transform: uppercase; display: block; margin-bottom: 5px;">Pièces Jointes :</strong>
            <div style="white-space: pre-line;">{{ $document->pieces_jointes }}</div>
        </div>
        @endif
        
        <div class="signature">
            <div class="signature-nom">{{ $entreprise['responsable'] }}</div>
            <div>{{ $entreprise['nom'] }}</div>
            @if(isset($signature) && $signature)
                @php
                    $sType = pathinfo($signature, PATHINFO_EXTENSION);
                    $sData = file_get_contents($signature);
                    $sBase64 = 'data:image/' . $sType . ';base64,' . base64_encode($sData);
                @endphp
                <img src="{{ $sBase64 }}" alt="Signature" style="max-height: 60px; margin-top: 10px; filter: grayscale(1);">
            @endif
        </div>
    </div>
    
    <div class="footer">
        {{ $entreprise['nom'] }} - {{ $entreprise['forme_juridique'] }} - SIRET {{ $entreprise['siret'] }} - {{ $entreprise['email'] }}
    </div>
</body>
</html>
