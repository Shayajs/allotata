#!/bin/bash

# Script pour lancer Laravel Sail + Stripe CLI en même temps
# Usage: ./dev.sh

set -e

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Démarrage de l'environnement de développement...${NC}"
echo ""

# Vérifier si Stripe CLI est installé
if ! command -v stripe &> /dev/null; then
    echo -e "${RED}❌ Stripe CLI n'est pas installé !${NC}"
    echo -e "${YELLOW}💡 Exécutez d'abord : ./setup-stripe-webhook.sh${NC}"
    exit 1
fi

# Vérifier si l'utilisateur est connecté à Stripe
if ! stripe config --list &> /dev/null; then
    echo -e "${YELLOW}⚠️  Vous n'êtes pas connecté à Stripe${NC}"
    echo -e "${BLUE}💡 Exécutez : stripe login${NC}"
    exit 1
fi

# Vérifier si STRIPE_WEBHOOK_SECRET est défini
if [ -z "$STRIPE_WEBHOOK_SECRET" ]; then
    # Essayer de le charger depuis .env
    if [ -f .env ]; then
        export $(grep STRIPE_WEBHOOK_SECRET .env | xargs)
    fi
    
    if [ -z "$STRIPE_WEBHOOK_SECRET" ]; then
        echo -e "${YELLOW}⚠️  STRIPE_WEBHOOK_SECRET n'est pas défini${NC}"
        echo -e "${BLUE}💡 Lancez d'abord 'stripe listen' pour obtenir le secret, puis ajoutez-le dans .env${NC}"
    fi
fi

# Port par défaut (peut être modifié dans .env avec APP_PORT)
APP_PORT=${APP_PORT:-80}
WEBHOOK_URL="localhost:${APP_PORT}/stripe/webhook"

echo -e "${GREEN}✅ Configuration détectée${NC}"
echo -e "   Port Laravel: ${APP_PORT}"
echo -e "   Webhook URL: ${WEBHOOK_URL}"
echo ""

# Fonction pour nettoyer les processus à l'arrêt
cleanup() {
    echo ""
    echo -e "${YELLOW}🛑 Arrêt des services...${NC}"
    # Tuer stripe listen si actif
    pkill -f "stripe listen" 2>/dev/null || true
    # Arrêter Sail
    ./vendor/bin/sail stop 2>/dev/null || true
    echo -e "${GREEN}✅ Services arrêtés${NC}"
    exit 0
}

# Capturer Ctrl+C
trap cleanup SIGINT SIGTERM

# Démarrer Laravel Sail
echo -e "${BLUE}🐳 Démarrage de Laravel Sail...${NC}"
./vendor/bin/sail up -d

# Attendre que Sail soit prêt
echo -e "${BLUE}⏳ Attente du démarrage de Sail...${NC}"
sleep 5

# Vérifier que Sail fonctionne
if ! ./vendor/bin/sail ps | grep -q "Up"; then
    echo -e "${RED}❌ Erreur lors du démarrage de Sail${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Laravel Sail démarré${NC}"
echo ""

# Démarrer Stripe CLI en arrière-plan
echo -e "${BLUE}🔗 Démarrage du tunnel Stripe...${NC}"
stripe listen --forward-to "${WEBHOOK_URL}" &
STRIPE_PID=$!

# Attendre un peu pour que Stripe CLI démarre
sleep 3

# Vérifier que Stripe CLI fonctionne
if ! ps -p $STRIPE_PID > /dev/null; then
    echo -e "${RED}❌ Erreur lors du démarrage de Stripe CLI${NC}"
    ./vendor/bin/sail stop
    exit 1
fi

echo -e "${GREEN}✅ Tunnel Stripe actif${NC}"
echo ""

# Afficher le secret du webhook (si disponible)
WEBHOOK_SECRET=$(stripe listen --print-secret 2>/dev/null || echo "")
if [ ! -z "$WEBHOOK_SECRET" ]; then
    echo -e "${YELLOW}📝 Webhook Secret: ${WEBHOOK_SECRET}${NC}"
    echo -e "${BLUE}💡 Assurez-vous qu'il est dans votre .env : STRIPE_WEBHOOK_SECRET=${WEBHOOK_SECRET}${NC}"
    echo ""
fi

echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Environnement de développement prêt !${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📋 Services actifs :${NC}"
echo -e "   • Laravel Sail (port ${APP_PORT})"
echo -e "   • Stripe CLI (tunnel webhook)"
echo ""
echo -e "${YELLOW}💡 Appuyez sur Ctrl+C pour arrêter tous les services${NC}"
echo ""

# Attendre indéfiniment (ou jusqu'à Ctrl+C)
wait $STRIPE_PID

