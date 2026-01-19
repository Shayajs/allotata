# Guide des Templates d'Emails

## Vue d'ensemble

Le système de templates d'emails permet de gérer facilement tous les emails envoyés par l'application. Les templates sont stockés en base de données et peuvent être modifiés depuis l'interface d'administration.

## Variables disponibles

Les templates utilisent des variables dynamiques entre accolades `{variable}` qui sont remplacées automatiquement lors de l'envoi.

### Variables communes
- `{nom_client}` - Nom du client
- `{nom_entreprise}` - Nom de l'entreprise
- `{url_dashboard}` - Lien vers le dashboard
- `{url_reservation}` - Lien vers une réservation
- `{url_messagerie}` - Lien vers la messagerie
- `{url_entreprise}` - Lien vers la page publique de l'entreprise

### Variables spécifiques aux réservations
- `{nom_service}` - Nom du service
- `{date_reservation}` - Date et heure de la réservation
- `{duree}` - Durée en minutes
- `{prix}` - Prix formaté
- `{lieu}` - Lieu de la réservation
- `{membre}` - Nom du membre assigné
- `{telephone}` - Téléphone du client
- `{notes}` - Notes de la réservation

### Variables HTML conditionnelles
Certaines variables sont remplacées par du HTML conditionnel :
- `{lieu_html}` - Paragraphe avec le lieu (vide si pas de lieu)
- `{membre_html}` - Paragraphe avec le membre (vide si pas de membre)
- `{notes_html}` - Paragraphe avec les notes (vide si pas de notes)
- `{remboursement_html}` - Message de remboursement si applicable

## Types de templates

### welcome
Email de bienvenue envoyé lors de l'inscription.

**Variables :** `nom_client`, `url_dashboard`

### reservation_confirmation_client
Confirmation de réservation envoyée au client.

**Variables :** `nom_client`, `nom_entreprise`, `nom_service`, `date_reservation`, `duree`, `prix`, `lieu_html`, `membre_html`, `notes_html`, `url_reservation`

### reservation_confirmation_gerant
Notification de nouvelle réservation envoyée au gérant.

**Variables :** `nom_client`, `nom_entreprise`, `nom_service`, `date_reservation`, `duree`, `prix`, `telephone`, `lieu_html`, `notes_html`, `url_reservation`

### reservation_reminder
Rappel de rendez-vous.

**Variables :** `nom_client`, `nom_entreprise`, `nom_service`, `date_reservation`, `duree`, `heures_avant`, `contact_entreprise`, `lieu_html`, `membre_html`, `url_reservation`

### payment_received
Confirmation de paiement reçu.

**Variables :** `nom_client`, `nom_entreprise`, `nom_service`, `date_reservation`, `montant`, `date_paiement`, `url_reservation`

### new_message
Notification de nouveau message.

**Variables :** `nom_client`, `nom_entreprise`, `contenu_message`, `url_messagerie`

### reservation_cancelled_client
Notification d'annulation de réservation (client).

**Variables :** `nom_client`, `nom_entreprise`, `nom_service`, `date_reservation`, `prix`, `message_annulation`, `remboursement_html`, `url_entreprise`

### weekly_report
Rapport hebdomadaire pour les gérants.

**Variables :** `nom_gerant`, `nom_entreprise`, `total_reservations`, `reservations_confirmees`, `reservations_en_attente`, `revenu_total`, `revenu_paye`, `url_dashboard`

## Utilisation dans le code

### Méthode simple avec EmailHelper

```php
use App\Helpers\EmailHelper;

// Email de bienvenue
EmailHelper::sendWelcome($user);

// Confirmation de réservation
EmailHelper::sendReservationConfirmationClient($reservation);

// Rappel
EmailHelper::sendReservationReminder($reservation, 24); // 24 heures avant
```

### Méthode avancée avec EmailTemplateService

```php
use App\Services\EmailTemplateService;

EmailTemplateService::send('welcome', $user->email, [
    'nom_client' => $user->name,
    'url_dashboard' => route('dashboard'),
], [
    'cc' => 'admin@example.com', // Optionnel
    'attachments' => ['/path/to/file.pdf'], // Optionnel
]);
```

## Gestion des templates

### Depuis l'interface admin

1. Accédez à `/admin/email-templates`
2. Cliquez sur "Modifier" pour éditer un template
3. Utilisez "Prévisualiser" pour voir le rendu avec des données d'exemple

### Depuis le code

```php
use App\Services\EmailTemplateService;

// Créer ou mettre à jour un template
EmailTemplateService::createOrUpdate('custom_type', [
    'name' => 'Mon template personnalisé',
    'subject' => 'Sujet avec {variable}',
    'body' => '<p>Corps avec {variable}</p>',
    'variables' => ['variable'],
    'is_active' => true,
    'description' => 'Description du template',
]);
```

## Installation

Pour installer les templates par défaut, exécutez :

```bash
php artisan migrate
php artisan db:seed --class=EmailTemplateSeeder
```

## Personnalisation

Tous les templates utilisent le layout `emails.layout` qui peut être personnalisé dans `resources/views/emails/layout.blade.php`.

Les templates peuvent contenir du HTML complet avec les classes CSS définies dans le layout.
