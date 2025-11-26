# Système d'Envoi des Alerts

Ce document décrit le système complet d'envoi des notifications pour les alerts de prix.

## Architecture

Le système est composé de plusieurs composants :

1. **NotificationService** : Service central qui gère la vérification et l'envoi des notifications
2. **Jobs** : Jobs asynchrones pour l'envoi (Email, Push, WhatsApp)
3. **Commande Artisan** : Commande pour vérifier périodiquement les alerts
4. **Intégration automatique** : Vérification automatique lors de la mise à jour des prix

## Fonctionnalités

### 1. Vérification Automatique

Les alerts sont vérifiés automatiquement dans les cas suivants :
- Lors de la mise à jour manuelle d'un prix (`updatePrice`)
- Lors du scraping et mise à jour d'un produit (`scrapeAndUpdate`)
- Lors de la mise à jour en masse des prix (`bulkUpdatePrices`)
- Via la commande Artisan planifiée (`alerts:check`)

### 2. Types d'Alertes Supportés

- **PRICE_DROP** : Alerte déclenchée quand le prix descend en dessous du prix cible
- **PRICE_INCREASE** : Alerte déclenchée quand le prix dépasse le prix cible
- **STOCK_AVAILABLE** : Alerte déclenchée quand le produit est en stock (à implémenter)

### 3. Canaux de Notification

#### Email
- Utilise le système de mail Laravel (SMTP par défaut)
- Configuration via `.env` :
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.mailgun.org
  MAIL_PORT=587
  MAIL_USERNAME=your_username
  MAIL_PASSWORD=your_password
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=noreply@junglealert.app
  MAIL_FROM_NAME="Jungle Alert"
  ```

#### Push Notifications (FCM)
- Utilise Firebase Cloud Messaging
- Configuration via `.env` :
  ```
  FCM_SERVER_KEY=your_firebase_server_key
  ```
- Les utilisateurs doivent avoir un `fcm_token` dans leur profil

#### WhatsApp
- Configuration via `.env` :
  ```
  WHATSAPP_API_URL=https://api.whatsapp.com/v1/messages
  WHATSAPP_API_KEY=your_whatsapp_api_key
  ```
- Les utilisateurs doivent avoir un `whatsapp_number` dans leur profil
- **Note** : L'intégration WhatsApp nécessite un service tiers (Twilio, WhatsApp Business API, etc.)

## Configuration

### 1. Variables d'Environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@junglealert.app
MAIL_FROM_NAME="Jungle Alert"

# Push Notifications (FCM)
FCM_SERVER_KEY=your_firebase_server_key_here

# WhatsApp (optionnel)
WHATSAPP_API_URL=
WHATSAPP_API_KEY=

# Queue Configuration (pour l'envoi asynchrone)
QUEUE_CONNECTION=database
# ou 'redis', 'sqs', etc. selon votre infrastructure
```

### 2. Configuration de la Queue

Pour utiliser l'envoi asynchrone, configurez la queue :

```bash
# Créer la table des jobs
php artisan queue:table
php artisan migrate

# Démarrer le worker de queue
php artisan queue:work
```

Ou utilisez un supervisor pour gérer automatiquement le worker :

```ini
[program:junglealert-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/jungle-alert-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/jungle-alert-api/storage/logs/queue-worker.log
stopwaitsecs=3600
```

### 3. Configuration du Scheduler

Le scheduler Laravel doit être configuré dans le cron du serveur :

```bash
# Ajouter cette ligne au crontab (crontab -e)
* * * * * cd /var/www/html/jungle-alert-api && php artisan schedule:run >> /dev/null 2>&1
```

## Utilisation

### Commande Artisan

Vérifier manuellement tous les alerts :

```bash
php artisan alerts:check
```

Vérifier les alerts pour un produit spécifique :

```bash
php artisan alerts:check --product-id=123
```

### API Endpoints

#### Vérifier les alerts d'un produit
```
POST /api/v1/products/{product}/check-alerts
```

#### Obtenir les alerts déclenchées
```
GET /api/v1/alerts/triggered
```

## Flux d'Exécution

1. **Déclenchement** : Un prix est mis à jour (manuellement ou via scraping)
2. **Vérification** : `NotificationService::checkAndTriggerAlerts()` est appelé
3. **Déclenchement des alerts** : Les alerts qui répondent aux critères sont marqués comme déclenchés
4. **Envoi des notifications** : Pour chaque alerte déclenchée :
   - Si l'utilisateur a activé les notifications email → `SendEmailNotificationJob` est dispatché
   - Si l'utilisateur a activé les notifications push et a un FCM token → `SendPushNotificationJob` est dispatché
   - Si l'utilisateur a activé les notifications WhatsApp et a un numéro → `SendWhatsAppNotificationJob` est dispatché
5. **Mise à jour** : Les flags `email_sent`, `push_sent`, `whatsapp_sent` sont mis à jour

## Logs

Tous les événements sont loggés dans `storage/logs/laravel.log` :
- Envoi réussi de notifications
- Erreurs lors de l'envoi
- Alerts déclenchés

## Tests

Pour tester le système :

1. Créer un produit avec un prix actuel
2. Créer une alerte avec un prix cible inférieur au prix actuel
3. Mettre à jour le prix du produit pour qu'il soit inférieur au prix cible
4. Vérifier que l'alerte est déclenchée et que les notifications sont envoyées

## Dépannage

### Les notifications ne sont pas envoyées

1. Vérifier que la queue worker est en cours d'exécution : `php artisan queue:work`
2. Vérifier les logs : `tail -f storage/logs/laravel.log`
3. Vérifier la configuration email/push/WhatsApp dans `.env`
4. Vérifier que l'utilisateur a activé les notifications correspondantes dans son profil

### Le scheduler ne s'exécute pas

1. Vérifier que le cron est configuré : `crontab -l`
2. Vérifier les logs du scheduler : `php artisan schedule:list`
3. Tester manuellement : `php artisan schedule:run`

### Les jobs échouent

1. Vérifier la table `failed_jobs` : `php artisan queue:failed`
2. Voir les détails d'un job échoué : `php artisan queue:failed:show {id}`
3. Réessayer un job : `php artisan queue:retry {id}`

