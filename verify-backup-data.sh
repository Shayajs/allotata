#!/bin/bash

# Script pour vérifier qu'une sauvegarde contient bien des données

if [ -z "$1" ]; then
    echo "Usage: $0 <fichier-backup.sql>"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Fichier non trouvé: $BACKUP_FILE"
    exit 1
fi

echo "🔍 Vérification de la sauvegarde: $BACKUP_FILE"
echo ""

# Vérifier la taille
SIZE=$(stat -f%z "$BACKUP_FILE" 2>/dev/null || stat -c%s "$BACKUP_FILE" 2>/dev/null)
SIZE_MB=$(echo "scale=2; $SIZE / 1024 / 1024" | bc)
echo "📊 Taille du fichier: ${SIZE_MB} MB"
echo ""

# Vérifier la présence de structure
echo "🔍 Vérification de la structure..."
if grep -q "CREATE TABLE" "$BACKUP_FILE"; then
    TABLE_COUNT=$(grep -c "CREATE TABLE" "$BACKUP_FILE")
    echo "✅ Structure trouvée: $TABLE_COUNT table(s)"
else
    echo "❌ Aucune structure de table trouvée"
fi
echo ""

# Vérifier la présence de données
echo "🔍 Vérification des données..."
if grep -q "INSERT INTO" "$BACKUP_FILE"; then
    INSERT_COUNT=$(grep -c "INSERT INTO" "$BACKUP_FILE")
    echo "✅ Données trouvées: $INSERT_COUNT instruction(s) INSERT"
    
    # Compter les lignes de données approximatives
    VALUES_COUNT=$(grep -c "VALUES" "$BACKUP_FILE" || echo "0")
    echo "   Lignes de données (approximatif): $VALUES_COUNT"
else
    echo "⚠️  Aucune instruction INSERT trouvée"
    echo "   La sauvegarde pourrait ne contenir que la structure"
fi
echo ""

# Vérifier les routines et triggers
if grep -q "CREATE.*PROCEDURE\|CREATE.*FUNCTION" "$BACKUP_FILE"; then
    ROUTINE_COUNT=$(grep -c "CREATE.*PROCEDURE\|CREATE.*FUNCTION" "$BACKUP_FILE")
    echo "✅ Routines trouvées: $ROUTINE_COUNT"
fi

if grep -q "CREATE.*TRIGGER" "$BACKUP_FILE"; then
    TRIGGER_COUNT=$(grep -c "CREATE.*TRIGGER" "$BACKUP_FILE")
    echo "✅ Triggers trouvées: $TRIGGER_COUNT"
fi
echo ""

# Résumé
echo "════════════════════════════════════════════════"
if grep -q "INSERT INTO" "$BACKUP_FILE" && grep -q "CREATE TABLE" "$BACKUP_FILE"; then
    echo "✅ Sauvegarde complète: Structure + Données"
else
    echo "⚠️  Vérifiez manuellement le contenu de la sauvegarde"
fi
echo "════════════════════════════════════════════════"
