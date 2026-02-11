# Système d'emails complet - Documentation

## Vue d'ensemble

Le système d'emails d'Allo Tata utilise un système de **templates en base de données** qui permet de gérer facilement tous les emails avec des variables dynamiques comme `{nom_client}`, `{nom_entreprise}`, etc.

## Architecture

### 1. Templates en base de données
- **Table** : `email_templates`
- **Modèle** : `App\Models\EmailTemplate`
- **Gestion** : Interface admin `/admin/email-templates`

### 2. Service de templates
- **Service** : `App\Services\EmailTemplateService`
- **Fonction** : Envoi d'emails avec remplacement automatique des variables

### 3. Helper pour faciliter l'utilisation
- **Helper** : `App\Helpers\EmailHelper`
- **Fonctions** : Méthodes simples pour chaque type d'email

## Utilisation

### Méthode recommandée : EmailHelper

```php
use App\Helpers\EmailHelper;

// Email de bienvenue (après vérification d'email)
EmailHelper::sendWelcome($user);

// Confirmation de réservation
EmailHelper::sendReservationConfirmationClient($reservation);
EmailHelper::sendReservationConfirmationGerant($reservation);

// Rappel de rendez-vous
EmailHelper::sendReservationReminder($reservation, 24); // 24h avant

// Paiement reçu
EmailHelper::sendPaymentReceived($reservation);

// Nouveau message
EmailHelper::sendNewMessage($message, $conversation);

// Annulation
EmailHelper::sendReservationCancelledClient($reservation, 'client');

// Rapport hebdomadaire
EmailHelper::sendWeeklyReport($user, $entreprise, $stats);
```

### Méthode avancée : EmailTemplateService

```php
use App\Services\EmailTemplateService;

EmailTemplateService::send('welcome', $user->email, [
    'nom_client' => $user->name,
    'url_dashboard' => route('dashboard'),
], [
    'cc' => 'admin@example.com', // Optionnel
]);
```

## Variables disponibles

### Variables communes
- `{nom_client}` - Nom du client
- `{nom_entreprise}` - Nom de l'entreprise
- `{nom_gerant}` - Nom du gérant
- `{url_dashboard}` - Lien vers le dashboard
- `{url_reservation}` - Lien vers une réservation (utilise le hash)
- `{url_messagerie}` - Lien vers la messagerie
- `{url_entreprise}` - Lien vers la page publique

### Variables spécifiques aux réservations
- `{nom_service}` - Nom du service
- `{date_reservation}` - Date formatée (d/m/Y à H:i)
- `{duree}` - Durée en minutes
- `{prix}` - Prix formaté (ex: 50,00 €)
- `{montant}` - Montant formaté
- `{telephone}` - Téléphone du client
- `{heures_avant}` - Nombre d'heures avant le rendez-vous
- `{contact_entreprise}` - Contact de l'entreprise

### Variables HTML conditionnelles
Ces variables sont remplacées par du HTML si la donnée existe, sinon vide :
- `{lieu_html}` - Paragraphe avec le lieu
- `{membre_html}` - Paragraphe avec le membre assigné
- `{notes_html}` - Paragraphe avec les notes
- `{remboursement_html}` - Message de remboursement si applicable
- `{message_annulation}` - Message d'annulation personnalisé

## Gestion des templates

### Depuis l'interface admin

1. Connectez-vous en tant qu'administrateur
2. Accédez à `/admin/email-templates`
3. Cliquez sur "Modifier" pour éditer un template
4. Modifiez le sujet et le corps
5. Utilisez les variables entre accolades : `{nom_client}`
6. Cliquez sur "Enregistrer"

### Exemple de template

**Sujet :**
```
Réservation confirmée - {nom_entreprise}
```

**Corps :**
```html
<h1>Bonjour {nom_client} !</h1>
<p>Votre réservation chez <strong>{nom_entreprise}</strong> est confirmée.</p>
<p>Date : {date_reservation}</p>
<p>Service : {nom_service}</p>
<p>Prix : {prix} €</p>
{lieu_html}
{membre_html}
<a href="{url_reservation}">Voir ma réservation</a>
```

## Intégration avec les hashs de réservation

Le système utilise automatiquement les **hashs de réservation** au lieu des IDs pour plus de sécurité :

- Les liens dans les emails utilisent `$reservation->hash ?? $reservation->id`
- Les hashs permettent un accès sécurisé sans authentification
- Support des alias courts (8 caractères) pour SMS

## Configuration SMTP

Le système est configuré pour utiliser :
- **Serveur** : mail.allotata.fr
- **Port** : 465 (SSL) ou 587 (TLS)
- **Email** : noreply@allotata.fr
- **Adresse d'expéditeur** : noreply@allotata.fr / "Allo Tata"

Voir `MAIL_CONFIGURATION.md` pour plus de détails.

## Templates disponibles

1. **welcome** - Email de bienvenue (après vérification)
2. **reservation_confirmation_client** - Confirmation client
3. **reservation_confirmation_gerant** - Notification gérant
4. **reservation_reminder** - Rappel de rendez-vous
5. **payment_received** - Confirmation de paiement
6. **new_message** - Nouveau message
7. **reservation_cancelled_client** - Annulation client
8. **weekly_report** - Rapport hebdomadaire
9. **email_verification** - Vérification d'email (optionnel)

## Installation

```bash
# 1. Migrations
php artisan migrate

# 2. Charger les templates par défaut
php artisan email-templates:seed

# 3. Configurer le .env (voir MAIL_CONFIGURATION.md)
MAIL_MAILER=smtp
MAIL_HOST=mail.allotata.fr
MAIL_PORT=465
MAIL_USERNAME=noreply@allotata.fr
MAIL_PASSWORD=Lapino1407--
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@allotata.fr
MAIL_FROM_NAME="Allo Tata"
```

## Avantages

✅ **Gestion centralisée** - Tous les emails au même endroit  
✅ **Modification facile** - Pas besoin de modifier le code  
✅ **Variables dynamiques** - `{nom_client}` remplacé automatiquement  
✅ **Personnalisation** - Chaque template peut être modifié individuellement  
✅ **Prévisualisation** - Voir le rendu avant d'envoyer  
✅ **Sécurité** - Utilisation de hashs pour les liens de réservation  
✅ **Cohérence** - Même layout pour tous les emails

## Notes importantes

- Les templates utilisent le layout `emails.layout` pour un style cohérent
- Les variables sont remplacées de manière sécurisée (htmlspecialchars pour les données utilisateur)
- Les emails sont envoyés via le serveur SMTP configuré
- Les hashs de réservation sont utilisés automatiquement pour plus de sécurité
