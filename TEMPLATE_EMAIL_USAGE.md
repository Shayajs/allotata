# Guide d'utilisation des Templates d'Emails

## Installation

1. Exécutez la migration :
```bash
php artisan migrate
```

2. Chargez les templates par défaut :
```bash
php artisan email-templates:seed
```

Ou via le seeder principal :
```bash
php artisan db:seed
```

## Utilisation simple

### Dans vos contrôleurs

Au lieu d'utiliser les classes Mailable directement, utilisez `EmailHelper` :

```php
use App\Helpers\EmailHelper;

// Email de bienvenue
EmailHelper::sendWelcome($user);

// Confirmation de réservation au client
EmailHelper::sendReservationConfirmationClient($reservation);

// Notification au gérant
EmailHelper::sendReservationConfirmationGerant($reservation);

// Rappel de rendez-vous
EmailHelper::sendReservationReminder($reservation, 24); // 24h avant

// Paiement reçu
EmailHelper::sendPaymentReceived($reservation);

// Nouveau message
EmailHelper::sendNewMessage($message, $conversation);

// Annulation de réservation
EmailHelper::sendReservationCancelledClient($reservation, 'client'); // ou 'gerant'

// Rapport hebdomadaire
EmailHelper::sendWeeklyReport($user, $entreprise, $stats);
```

## Personnalisation des templates

### Depuis l'interface admin

1. Connectez-vous en tant qu'administrateur
2. Allez dans `/admin/email-templates`
3. Cliquez sur "Modifier" pour éditer un template
4. Modifiez le sujet et le corps du message
5. Utilisez les variables disponibles (ex: `{nom_client}`, `{nom_entreprise}`)
6. Cliquez sur "Enregistrer"

### Variables disponibles

Tous les templates supportent des variables dynamiques entre accolades :

- `{nom_client}` - Nom du client
- `{nom_entreprise}` - Nom de l'entreprise
- `{nom_gerant}` - Nom du gérant
- `{nom_service}` - Nom du service
- `{date_reservation}` - Date et heure formatée
- `{prix}` - Prix formaté
- `{duree}` - Durée en minutes
- `{url_dashboard}` - Lien vers le dashboard
- `{url_reservation}` - Lien vers une réservation
- Et bien d'autres selon le type de template

### Variables HTML conditionnelles

Certaines variables sont remplacées par du HTML conditionnel :
- `{lieu_html}` - Affiche le lieu si disponible, sinon vide
- `{membre_html}` - Affiche le membre si assigné, sinon vide
- `{notes_html}` - Affiche les notes si présentes, sinon vide
- `{remboursement_html}` - Message de remboursement si applicable

## Exemple de template personnalisé

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

## Création d'un nouveau type de template

1. Créez le template en base de données :
```php
use App\Services\EmailTemplateService;

EmailTemplateService::createOrUpdate('mon_type', [
    'name' => 'Mon template personnalisé',
    'subject' => 'Sujet avec {variable}',
    'body' => '<p>Corps avec {variable}</p>',
    'variables' => ['variable'],
    'is_active' => true,
    'description' => 'Description',
]);
```

2. Utilisez-le :
```php
EmailTemplateService::send('mon_type', 'email@example.com', [
    'variable' => 'Valeur',
]);
```

## Avantages du système de templates

✅ **Gestion centralisée** - Tous les emails au même endroit  
✅ **Modification facile** - Pas besoin de modifier le code  
✅ **Personnalisation** - Chaque entreprise peut avoir ses propres templates  
✅ **Variables dynamiques** - Remplacement automatique des données  
✅ **Prévisualisation** - Voir le rendu avant d'envoyer  
✅ **Versionning** - Historique des modifications en base de données
