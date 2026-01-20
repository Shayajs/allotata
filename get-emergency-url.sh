#!/bin/bash

# Script pour obtenir l'URL de récupération d'urgence
# Fonctionne avec Docker ou sans Docker

echo "🔍 Recherche de l'URL de récupération d'urgence..."
echo ""

# Détecter si on est dans Docker ou non
if [ -f /.dockerenv ] || [ -n "$DOCKER_HOST" ]; then
    echo "📍 Mode Docker détecté"
    CONTAINER_NAME="allotata_app"
    
    # Vérifier si le conteneur existe
    if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        echo "✅ Conteneur trouvé: $CONTAINER_NAME"
        echo ""
        docker exec $CONTAINER_NAME php artisan emergency:url
    else
        echo "❌ Conteneur $CONTAINER_NAME non trouvé"
        echo ""
        echo "Conteneurs disponibles:"
        docker ps --format '{{.Names}}'
        echo ""
        echo "Essayez avec: docker exec [nom-conteneur] php artisan emergency:url"
    fi
else
    echo "📍 Mode local détecté"
    echo ""
    
    # Essayer directement avec php artisan
    if command -v php &> /dev/null; then
        php artisan emergency:url
    else
        echo "❌ PHP non trouvé dans le PATH"
        echo ""
        echo "Essayez avec:"
        echo "  - docker exec allotata_app php artisan emergency:url"
        echo "  - ./vendor/bin/sail artisan emergency:url (si vous utilisez Sail)"
    fi
fi
