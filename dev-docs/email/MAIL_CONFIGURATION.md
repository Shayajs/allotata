# Configuration du serveur de messagerie

## Paramètres SMTP

Pour configurer l'envoi d'emails, ajoutez ces variables dans votre fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.allotata.fr
MAIL_PORT=465
MAIL_USERNAME=noreply@allotata.fr
MAIL_PASSWORD=Lapino1407--
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@allotata.fr
MAIL_FROM_NAME="Allo Tata"
```

## Configuration alternative (port 587)

Si le port 465 ne fonctionne pas, utilisez le port 587 avec TLS :

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.allotata.fr
MAIL_PORT=587
MAIL_USERNAME=noreply@allotata.fr
MAIL_PASSWORD=Lapino1407--
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@allotata.fr
MAIL_FROM_NAME="Allo Tata"
```

## Informations du serveur

- **Serveur SMTP** : mail.allotata.fr
- **Port SMTP SSL** : 465
- **Port SMTP TLS** : 587
- **Email** : noreply@allotata.fr
- **Mot de passe** : Lapino1407--

## Test de la configuration

Pour tester l'envoi d'emails, vous pouvez utiliser la commande Tinker :

```bash
php artisan tinker
```

Puis exécutez :

```php
Mail::raw('Test email', function ($message) {
    $message->to('votre-email@example.com')
            ->subject('Test de configuration email');
});
```

Ou utilisez une route de test temporaire :

```php
Route::get('/test-email', function() {
    try {
        Mail::raw('Test email depuis Allo Tata', function ($message) {
            $message->to('votre-email@example.com')
                    ->subject('Test de configuration email');
        });
        return 'Email envoyé avec succès !';
    } catch (\Exception $e) {
        return 'Erreur : ' . $e->getMessage();
    }
});
```

## Dépannage

### Erreur "Connection refused"
- Vérifiez que le port n'est pas bloqué par le firewall
- Vérifiez que le serveur mail.allotata.fr est accessible

### Erreur "Authentication failed"
- Vérifiez que l'email et le mot de passe sont corrects
- Vérifiez que l'email noreply@allotata.fr est bien configuré sur le serveur

### Erreur SSL/TLS
- Si le port 465 ne fonctionne pas, essayez le port 587 avec TLS
- Vérifiez que le certificat SSL du serveur est valide
- Si nécessaire, modifiez `verify_peer` à `false` dans `config/mail.php` (non recommandé en production)

## Configuration IMAP/POP (pour réception)

Ces paramètres sont pour la réception d'emails (non utilisés par Laravel pour l'envoi) :

- **Serveur IMAP** : mail.allotata.fr
- **Port IMAP SSL** : 993
- **Port IMAP** : 143
- **Serveur POP** : mail.allotata.fr
- **Port POP SSL** : 995
- **Port POP** : 110
