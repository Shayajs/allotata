#!/bin/bash

# Script de vérification de la configuration Stripe
# Usage: ./check-stripe-config.sh [webhook_secret]

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 Vérification de la configuration Stripe...${NC}"
echo ""

# Vérifier si le fichier .env existe
if [ ! -f .env ]; then
    echo -e "${RED}❌ Le fichier .env n'existe pas${NC}"
    echo -e "${YELLOW}💡 Créez-le à partir de .env.example${NC}"
    exit 1
fi

# Vérifier STRIPE_KEY
if grep -q "STRIPE_KEY=" .env && ! grep -q "STRIPE_KEY=$" .env; then
    STRIPE_KEY=$(grep "STRIPE_KEY=" .env | cut -d '=' -f2)
    if [ ! -z "$STRIPE_KEY" ]; then
        echo -e "${GREEN}✅ STRIPE_KEY est configuré${NC}"
    else
        echo -e "${YELLOW}⚠️  STRIPE_KEY est vide${NC}"
    fi
else
    echo -e "${RED}❌ STRIPE_KEY n'est pas configuré dans .env${NC}"
fi

# Vérifier STRIPE_SECRET
if grep -q "STRIPE_SECRET=" .env && ! grep -q "STRIPE_SECRET=$" .env; then
    STRIPE_SECRET=$(grep "STRIPE_SECRET=" .env | cut -d '=' -f2)
    if [ ! -z "$STRIPE_SECRET" ]; then
        echo -e "${GREEN}✅ STRIPE_SECRET est configuré${NC}"
    else
        echo -e "${YELLOW}⚠️  STRIPE_SECRET est vide${NC}"
    fi
else
    echo -e "${RED}❌ STRIPE_SECRET n'est pas configuré dans .env${NC}"
fi

# Vérifier STRIPE_WEBHOOK_SECRET
WEBHOOK_SECRET=""
if grep -q "STRIPE_WEBHOOK_SECRET=" .env; then
    WEBHOOK_SECRET=$(grep "STRIPE_WEBHOOK_SECRET=" .env | cut -d '=' -f2)
    if [ ! -z "$WEBHOOK_SECRET" ]; then
        echo -e "${GREEN}✅ STRIPE_WEBHOOK_SECRET est configuré${NC}"
        echo -e "   Secret actuel: ${BLUE}${WEBHOOK_SECRET:0:20}...${NC}"
    else
        echo -e "${YELLOW}⚠️  STRIPE_WEBHOOK_SECRET est vide${NC}"
    fi
else
    echo -e "${RED}❌ STRIPE_WEBHOOK_SECRET n'est pas configuré dans .env${NC}"
fi

# Si un secret est fourni en argument, l'ajouter/mettre à jour
if [ ! -z "$1" ]; then
    NEW_SECRET="$1"
    echo ""
    echo -e "${BLUE}📝 Mise à jour du STRIPE_WEBHOOK_SECRET...${NC}"
    
    # Vérifier si la ligne existe déjà
    if grep -q "STRIPE_WEBHOOK_SECRET=" .env; then
        # Mettre à jour la ligne existante
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' "s|STRIPE_WEBHOOK_SECRET=.*|STRIPE_WEBHOOK_SECRET=${NEW_SECRET}|" .env
        else
            # Linux
            sed -i "s|STRIPE_WEBHOOK_SECRET=.*|STRIPE_WEBHOOK_SECRET=${NEW_SECRET}|" .env
        fi
        echo -e "${GREEN}✅ STRIPE_WEBHOOK_SECRET mis à jour${NC}"
    else
        # Ajouter la ligne
        echo "" >> .env
        echo "STRIPE_WEBHOOK_SECRET=${NEW_SECRET}" >> .env
        echo -e "${GREEN}✅ STRIPE_WEBHOOK_SECRET ajouté${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}⚠️  N'oubliez pas de vider le cache de configuration :${NC}"
    echo -e "   ${BLUE}./vendor/bin/sail artisan config:clear${NC}"
    echo -e "   ${BLUE}ou${NC}"
    echo -e "   ${BLUE}php artisan config:clear${NC}"
fi

echo ""
echo -e "${BLUE}📋 Prochaines étapes :${NC}"
echo -e "   1. Vérifiez que tous les secrets sont configurés"
if [ -z "$WEBHOOK_SECRET" ] && [ -z "$1" ]; then
    echo -e "   2. Ajoutez le secret du webhook :"
    echo -e "      ${BLUE}./check-stripe-config.sh whsec_xxxxx${NC}"
fi
echo -e "   3. Videz le cache : ${BLUE}./vendor/bin/sail artisan config:clear${NC}"
echo -e "   4. Testez le webhook : ${BLUE}stripe trigger payment_intent.succeeded${NC}"
