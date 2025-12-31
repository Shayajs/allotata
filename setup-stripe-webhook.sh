#!/bin/bash

# Script d'installation de Stripe CLI et configuration des webhooks
# À exécuter dans votre terminal

echo "🔧 Installation de Stripe CLI..."

# Étape 1 : Ajouter la clé GPG de Stripe
curl -s https://packages.stripe.dev/api/security/keyring.gpg | sudo gpg --dearmor -o /usr/share/keyrings/stripe.gpg

# Étape 2 : Ajouter le dépôt Stripe
echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/api/debian stable main" | sudo tee /etc/apt/sources.list.d/stripe.list

# Étape 3 : Mettre à jour les paquets
sudo apt update

# Étape 4 : Installer Stripe CLI
sudo apt install stripe -y

echo "✅ Stripe CLI installé !"
echo ""
echo "📝 Prochaines étapes :"
echo "1. Connectez-vous avec : stripe login"
echo "2. Dans un NOUVEAU terminal, lancez : stripe listen --forward-to localhost/stripe/webhook"
echo "3. Copiez le 'webhook signing secret' (whsec_xxxxx)"
echo "4. Ajoutez-le dans votre .env : STRIPE_WEBHOOK_SECRET=whsec_xxxxx"
echo "5. Redémarrez votre serveur Laravel"

