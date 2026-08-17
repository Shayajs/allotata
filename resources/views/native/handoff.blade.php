<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Ouverture de l’app</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #0f172a; color: #e2e8f0; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; text-align: center; padding: 2rem; }
        a { color: #4ade80; }
    </style>
</head>
<body>
    <div>
        <p>Votre journée, même sans réseau.</p>
        <p><a id="open" href="{{ $schemeUrl }}">Ouvrir Allo Tata</a></p>
    </div>
    <script>
        location.replace(@json($schemeUrl));
    </script>
</body>
</html>
