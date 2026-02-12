#!/bin/bash

# Script de test pour la route de sauvegarde automatique
# Usage: ./test-backup-route.sh

echo "🔍 Test de la route de sauvegarde automatique"
echo ""

# Vérifier si le token est configuré
if [ -z "$AUTO_BACKUP_TOKEN" ]; then
    echo "⚠️  La variable AUTO_BACKUP_TOKEN n'est pas définie"
    echo "   Ajoutez-la dans votre .env :"
    echo "   AUTO_BACKUP_TOKEN=votre-token-secret"
    echo ""
    read -p "Voulez-vous utiliser un token de test ? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        TOKEN="test-token-$(date +%s)"
        echo "   Token de test: $TOKEN"
        echo "   (N'oubliez pas de configurer le vrai token dans .env)"
    else
        exit 1
    fi
else
    TOKEN="$AUTO_BACKUP_TOKEN"
    echo "✅ Token trouvé dans l'environnement"
fi

echo ""
echo "🌐 Test de la route..."
echo ""

# Déterminer l'URL
if [ -n "$DOCKER_HOST" ] || [ -f /.dockerenv ]; then
    URL="http://app/autosave?token=$TOKEN"
    echo "📍 Mode Docker détecté"
else
    URL="http://localhost/autosave?token=$TOKEN"
    echo "📍 Mode local détecté"
fi

echo "URL: $URL"
echo ""

# Faire la requête
RESPONSE=$(curl -s -w "\n%{http_code}" "$URL")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "📊 Réponse HTTP: $HTTP_CODE"
echo ""
echo "📄 Corps de la réponse:"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
echo ""

# Analyser la réponse
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Succès ! La sauvegarde a été créée."
    echo ""
    SUCCESS=$(echo "$BODY" | grep -o '"success":true' || echo "")
    if [ -n "$SUCCESS" ]; then
        echo "✅ La réponse indique un succès"
        FILENAME=$(echo "$BODY" | grep -o '"filename":"[^"]*"' | cut -d'"' -f4)
        if [ -n "$FILENAME" ]; then
            echo "📁 Fichier créé: $FILENAME"
        fi
    fi
elif [ "$HTTP_CODE" = "403" ]; then
    echo "❌ Erreur 403: Token invalide"
    echo "   Vérifiez que le token dans .env correspond à celui utilisé"
elif [ "$HTTP_CODE" = "500" ]; then
    echo "❌ Erreur 500: Problème serveur"
    echo "   Vérifiez les logs Laravel: storage/logs/laravel.log"
else
    echo "❌ Erreur inattendue: HTTP $HTTP_CODE"
fi

echo ""
echo "💡 Pour tester depuis un cron, utilisez:"
echo "   curl -s \"$URL\" > /dev/null"
