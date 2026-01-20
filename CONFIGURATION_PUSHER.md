# Configuration Pusher pour la Collaboration en Temps Réel

## Pourquoi Pusher ?

Pusher est un service WebSocket hébergé qui remplace Reverb. Il offre plusieurs avantages :

- ✅ **Gratuit** jusqu'à 100 connexions simultanées et 200 000 messages/jour
- ✅ **Pas de serveur à gérer** - service hébergé par Pusher
- ✅ **Configuration simple** - juste quelques clés API
- ✅ **Fiable** - infrastructure robuste et scalable
- ✅ **Support SSL/TLS** - sécurisé par défaut

## Étapes de Configuration

### 1. Créer un compte Pusher

1. Aller sur [pusher.com](https://pusher.com)
2. Cliquer sur "Sign up" (c'est gratuit)
3. Créer un compte avec votre email

### 2. Créer une application

1. Une fois connecté, aller dans le dashboard
2. Cliquer sur "Create app" ou "Channels apps"
3. Remplir le formulaire :
   - **App name** : `Allo Tata` (ou le nom de votre choix)
   - **Cluster** : Choisir le cluster le plus proche (ex: `eu`, `us`, `ap-southeast-1`)
   - **Front-end tech** : `Vanilla JS`
   - **Back-end tech** : `Laravel`

4. Cliquer sur "Create app"

### 3. Récupérer les clés API

Dans la page de votre application, vous verrez plusieurs onglets :
- **App Keys** : contient les clés nécessaires
- **App Settings** : paramètres de l'application

Vous aurez besoin de :
- **App ID** (ex: `1234567`)
- **Key** (ex: `abcdefghijklmnop`)
- **Secret** (ex: `1234567890abcdefghijklmnop`)
- **Cluster** (ex: `eu`, `us`)

### 4. Configurer Laravel

Éditer votre fichier `.env` :

```env
# Configuration Broadcasting
BROADCAST_CONNECTION=pusher

# Configuration Pusher
PUSHER_APP_ID=votre_app_id
PUSHER_APP_KEY=votre_key
PUSHER_APP_SECRET=votre_secret
PUSHER_APP_CLUSTER=votre_cluster
```

**Exemple :**
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=1234567
PUSHER_APP_KEY=abcdefghijklmnop
PUSHER_APP_SECRET=1234567890abcdefghijklmnop
PUSHER_APP_CLUSTER=eu
```

### 5. Vérifier la configuration

Le fichier `config/broadcasting.php` est déjà configuré pour Pusher. Assurez-vous juste que :
- Le driver est `pusher`
- Les variables d'environnement sont bien récupérées

### 6. Reconstruire les assets

```bash
./vendor/bin/sail npm run build
```

## Fonctionnalités Activées

Une fois Pusher configuré, vous aurez :

✅ **Collaboration en temps réel** - voir les changements des autres utilisateurs instantanément
✅ **Curseurs en couleurs** - chaque utilisateur a un curseur de couleur différente
✅ **Indicateurs de présence** - voir qui travaille sur la même note
✅ **Notifications** - être notifié quand un autre utilisateur modifie la note

## Coûts

- **Gratuit (Sandbox)** : 
  - 100 connexions simultanées max
  - 200 000 messages/jour
  - Parfait pour le développement et les petites équipes

- **Payant (Starter)** : $49/mois
  - 500 connexions simultanées
  - 10 000 000 messages/mois
  - Support par email

Pour la plupart des cas d'usage, le plan gratuit suffit largement.

## Dépannage

### Les événements ne sont pas reçus

1. Vérifier que `BROADCAST_CONNECTION=pusher` dans `.env`
2. Vérifier que les clés Pusher sont correctes
3. Vérifier la console du navigateur pour les erreurs
4. Vérifier que le cluster correspond à votre région

### Les curseurs ne s'affichent pas

1. Vérifier que plusieurs utilisateurs sont connectés à la même note
2. Vérifier la console du navigateur
3. Vérifier que les événements `NoteCursorMoved` sont bien émis dans Laravel

### Erreur de connexion

1. Vérifier que `PUSHER_APP_KEY` est bien défini dans `.env`
2. Vérifier que le cluster correspond à celui configuré dans Pusher
3. Vérifier les logs Laravel pour les erreurs de broadcasting

## Alternative : Désactiver Pusher

Si vous ne souhaitez pas utiliser Pusher, l'application fonctionnera avec un **polling HTTP** toutes les 3 secondes. Ce n'est pas en temps réel mais ça fonctionne sans configuration.

Pour désactiver Pusher, ne définissez simplement pas `PUSHER_APP_KEY` dans votre `.env` ou laissez-le vide.