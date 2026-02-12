# Configuration des tâches cron pour Allo Tata

Pour activer les rappels automatiques et les rapports, ajoutez ces lignes à votre crontab :

```bash
# Rappels de réservation (toutes les heures)
0 * * * * cd /path/to/allotata && php artisan reservations:send-reminders --hours=24 >> /dev/null 2>&1
0 * * * * cd /path/to/allotata && php artisan reservations:send-reminders --hours=2 >> /dev/null 2>&1

# Rapports hebdomadaires (chaque lundi à 9h)
0 9 * * 1 cd /path/to/allotata && php artisan reports:send-weekly >> /dev/null 2>&1

# Rapports mensuels (le 1er de chaque mois à 9h)
0 9 1 * * cd /path/to/allotata && php artisan reports:send-monthly >> /dev/null 2>&1
```

Pour éditer le crontab :
```bash
crontab -e
```
