# Configuration SMS - Allotata

## Installation

### 1. Installer le package Twilio

```bash
./vendor/bin/sail composer require twilio/sdk
```

### 2. Exécuter les migrations

```bash
./vendor/bin/sail artisan migrate
```

### 3. Configuration du `.env`

Ajoutez ces variables dans votre fichier `.env` :

```env
# Choisissez 'log' pour tester gratuitement en local, 'twilio' pour la production
SMS_DRIVER=log

# Credentials Twilio (uniquement nécessaires si SMS_DRIVER=twilio)
TWILIO_USERNAME=ACxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_token
TWILIO_FROM=+33612345678
```

**Note :** 
- `TWILIO_USERNAME` contient votre Account SID (format: ACxxxxxxxxxxxxxxxxxxxx)
- `TWILIO_FROM` est le numéro Twilio depuis lequel les SMS seront envoyés
- En mode `log`, les SMS ne sont pas envoyés réellement mais écrits dans `laravel.log`

## Fonctionnalités

### Envoi automatique de SMS

Un SMS est automatiquement envoyé lorsque :
- Une réservation est créée avec le statut `confirmee`
- Une réservation passe du statut `en_attente` à `confirmee`

Le message contient :
> "Bonjour, votre réservation Allotata est confirmée. Retrouvez vos détails ici : [URL]"

### Rate Limiting (Anti-Spam)

Le système limite l'envoi à **3 SMS/heure** par :
- Adresse IP
- Utilisateur (si connecté)

Cette limite protège contre le SMS Pumping (fraude).

### Interface Admin

Accédez à la liste des logs SMS via :
- `/admin/sms-logs`

Fonctionnalités :
- Visualisation de tous les SMS envoyés
- Statistiques (total, envoyés, échecs, en attente, aujourd'hui)
- Filtres par statut, provider, destinataire, dates
- **Bouton "Test SMS"** pour envoyer un SMS de test à un numéro spécifique

### Logs et Traçabilité

Chaque tentative d'envoi est enregistrée dans la table `sms_logs` avec :
- Date d'envoi
- Destinataire (numéro de téléphone)
- Message envoyé
- Statut (envoyé, échec, en attente)
- Provider (twilio, log)
- ID du message chez le provider
- Message d'erreur (si échec)
- IP de l'expéditeur
- ID utilisateur (si applicable)
- ID réservation (si applicable)

## Architecture Technique

### Composants

1. **Modèle `SmsLog`** : Enregistre tous les SMS
2. **Notification `BookingConfirmedSms`** : Notification SMS de confirmation
3. **Canal `TwilioSmsChannel`** : Canal personnalisé gérant l'envoi réel et le mode log
4. **Observer `ReservationObserver`** : Déclenche l'envoi SMS après création/modification de réservation
5. **Contrôleur `Admin\SmsLogController`** : Interface d'administration des logs

### Mode Log vs Production

Le système fonctionne en deux modes :

**Mode Log (développement/local)** :
- `SMS_DRIVER=log`
- Les SMS sont écrits dans `laravel.log`
- Pas d'envoi réel
- Permet de tester sans coûts Twilio

**Mode Production** :
- `SMS_DRIVER=twilio`
- Envoi réel via l'API Twilio
- Nécessite les credentials Twilio configurés

## Sécurité

### Protection Anti-Spam

Le rate limiting empêche :
- L'envoi massif de SMS depuis une même IP
- L'abus par un utilisateur malveillant
- Les coûts excessifs sur Twilio

Les limites sont gérées par `Illuminate\Support\Facades\RateLimiter` avec une expiration de 1 heure.

## Tests

Pour tester le système :

1. **Via l'interface admin** :
   - Aller sur `/admin/sms-logs`
   - Cliquer sur "Envoyer un SMS de test"
   - Entrer un numéro (format international : +33612345678)
   - Cliquer sur "Envoyer"

2. **Via une réservation** :
   - Créer une réservation confirmée avec un numéro de téléphone
   - Le SMS sera envoyé automatiquement

## Dépannage

### Le SMS n'est pas envoyé

1. Vérifiez que `SMS_DRIVER` est correctement configuré
2. Vérifiez les credentials Twilio (si mode production)
3. Consultez `laravel.log` pour les erreurs
4. Vérifiez les logs SMS dans `/admin/sms-logs`

### Rate Limit dépassé

- Attendez 1 heure ou changez d'IP
- Les logs SMS afficheront "Rate limit dépassé" dans le champ `error_message`

### Format du numéro

- Le numéro doit être au format international
- Exemples : `+33612345678`, `0612345678` (automatiquement converti en `+33612345678`)

## Notes

- Le système fonctionne pour les clients inscrits (avec `telephone` dans la table `users`)
- Le système fonctionne aussi pour les clients non inscrits (avec `telephone_client` ou `telephone_client_non_inscrit` dans la table `reservations`)
- L'URL dans le SMS pointe vers la page publique de la réservation (via le hash)
