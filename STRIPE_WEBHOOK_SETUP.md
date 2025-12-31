# Configuration des Webhooks Stripe 🛡️

## Pourquoi c'est nécessaire ?

Quand un paiement réussit, Stripe essaie d'envoyer une notification (webhook) à votre site. En développement local, `localhost` n'est pas accessible depuis Internet. Il faut créer un **tunnel sécurisé** avec Stripe CLI.

## Installation de Stripe CLI

### Option 1 : Script automatique

```bash
chmod +x setup-stripe-webhook.sh
./setup-stripe-webhook.sh
```

### Option 2 : Installation manuelle

```bash
# 1. Ajouter la clé GPG
curl -s https://packages.stripe.dev/api/security/keyring.gpg | sudo gpg --dearmor -o /usr/share/keyrings/stripe.gpg

# 2. Ajouter le dépôt
echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/api/debian stable main" | sudo tee /etc/apt/sources.list.d/stripe.list

# 3. Mettre à jour
sudo apt update

# 4. Installer
sudo apt install stripe -y
```

## Configuration

### Étape 1 : Se connecter à Stripe

```bash
stripe login
```

Un lien s'ouvrira dans votre navigateur. Validez la connexion.

### Étape 2 : Lancer le tunnel (dans un NOUVEAU terminal)

```bash
stripe listen --forward-to localhost/stripe/webhook
```

⚠️ **IMPORTANT** : Cette commande doit rester active pendant vos tests !

### Étape 3 : Récupérer le secret du webhook

Une fois `stripe listen` lancé, vous verrez une ligne comme :

```
Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxx
```

### Étape 4 : Ajouter le secret dans `.env`

Ouvrez votre fichier `.env` et ajoutez (ou modifiez) :

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

### Étape 5 : Redémarrer Laravel Sail

```bash
# Arrêtez Sail (Ctrl+C) puis relancez
./vendor/bin/sail up -d
```

## Vérification

1. Effectuez un test de paiement
2. Regardez le terminal où tourne `stripe listen`
3. Vous devriez voir des lignes comme :
   ```
   2024-12-31 10:30:45   --> checkout.session.completed [evt_xxxxx]
   2024-12-31 10:30:45  <--  [200] POST http://localhost/stripe/webhook [evt_xxxxx]
   ```
4. Vérifiez votre base de données : la table `subscriptions` devrait se remplir !

## Sécurité 🛡️

Le `STRIPE_WEBHOOK_SECRET` permet à Laravel Cashier de **vérifier la signature numérique** de chaque message venant de Stripe. Sans ce secret, n'importe qui pourrait simuler un paiement en envoyant une requête POST sur `/stripe/webhook`.

C'est une protection vitale contre la fraude !

## Dépannage

### Le webhook ne fonctionne pas ?

1. Vérifiez que `stripe listen` est toujours actif
2. Vérifiez que `STRIPE_WEBHOOK_SECRET` est bien dans votre `.env`
3. Vérifiez que votre serveur Laravel écoute sur `localhost`
4. Regardez les logs de `stripe listen` pour voir les erreurs

### Erreur "Invalid signature" ?

- Le secret du webhook a peut-être changé
- Relancez `stripe listen` et copiez le nouveau secret
- Mettez à jour votre `.env`

