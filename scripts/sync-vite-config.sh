#!/bin/bash
# Script de synchronisation vite.config.js -> vite.config.js.server
# Utilisé pour maintenir la cohérence entre l'environnement local (Sail) et la production

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

SOURCE_FILE="$PROJECT_DIR/vite.config.js"
DEST_FILE="$PROJECT_DIR/vite.config.js.server"

if [ ! -f "$SOURCE_FILE" ]; then
    echo "❌ Erreur: $SOURCE_FILE n'existe pas"
    exit 1
fi

# Copier le fichier
cp "$SOURCE_FILE" "$DEST_FILE"

# Remplacer l'IP locale par 0.0.0.0 pour le serveur
sed -i "s/127.0.0.2/0.0.0.0/g" "$DEST_FILE"

echo "✅ Synchronisation terminée: vite.config.js -> vite.config.js.server"
echo "   IP remplacée: 127.0.0.2 -> 0.0.0.0"
