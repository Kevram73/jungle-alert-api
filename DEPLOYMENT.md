# Guide de Déploiement - Jungle Alert API

Ce document explique comment configurer et utiliser le pipeline de déploiement automatique avec GitHub Actions.

## 🚀 Déploiement Automatique avec GitHub Actions

### Prérequis

1. Un repository GitHub pour le code source
2. Un serveur avec accès SSH
3. PHP 8.1+ et Composer installés sur le serveur

### Configuration Initiale

#### 1. Configurer les Secrets GitHub

Allez dans votre repository GitHub :
- **Settings** > **Secrets and variables** > **Actions**
- Cliquez sur **New repository secret**

Ajoutez les secrets suivants :

| Secret | Description | Exemple |
|--------|-------------|---------|
| `SERVER_IP` | Adresse IP du serveur | `31.97.185.5` |
| `SERVER_USER` | Utilisateur SSH | `root` |
| `SERVER_PASSWORD` | Mot de passe SSH | `votre_mot_de_passe` |

#### 2. Structure du Serveur

Le pipeline déploie automatiquement dans :
- **Répertoire** : `/var/www/html/jungle-alert-api`
- **Port API** : `8001`
- **Logs** : `/var/log/jungle-alert-laravel.log`

### Déclenchement du Déploiement

#### Automatique
Le déploiement se déclenche automatiquement lors d'un **push sur la branche `main` ou `master`**.

#### Manuel
1. Allez dans l'onglet **Actions** de votre repository GitHub
2. Sélectionnez le workflow **Deploy Laravel API**
3. Cliquez sur **Run workflow**
4. Sélectionnez la branche et cliquez sur **Run workflow**

### Processus de Déploiement

Le pipeline exécute les étapes suivantes :

1. **📥 Checkout** : Récupération du code source
2. **📦 Archive** : Création d'une archive optimisée (exclut vendor, node_modules, logs, etc.)
3. **📤 Upload** : Transfert vers le serveur via SCP
4. **🔧 Installation** :
   - Extraction de l'archive
   - Installation des dépendances Composer (`composer install --no-dev`)
   - Configuration des permissions
   - Exécution des migrations (ignore les erreurs si tables existent)
   - Mise en cache (config, routes, views)
5. **🚀 Démarrage** : Démarrage de l'API Laravel sur le port 8001
6. **🧪 Test** : Vérification de l'endpoint `/api/health`
7. **🧹 Nettoyage** : Suppression des fichiers temporaires

### Vérification du Déploiement

#### Vérifier que l'API fonctionne

```bash
curl http://31.97.185.5:8001/api/health
```

Réponse attendue :
```json
{"status":"healthy","message":"API is running"}
```

#### Vérifier les logs

```bash
# Sur le serveur
tail -f /var/log/jungle-alert-laravel.log
```

#### Vérifier que le processus tourne

```bash
# Sur le serveur
ps aux | grep "php artisan serve"
```

### Configuration de la Base de Données

Le pipeline préserve le fichier `.env` existant. Si le fichier n'existe pas, il sera créé à partir de `.env.example`.

**Important** : Assurez-vous que le fichier `.env` sur le serveur contient les bonnes informations de base de données.

### Dépannage

#### Le déploiement échoue

1. **Vérifier les secrets GitHub** : Assurez-vous que tous les secrets sont correctement configurés
2. **Vérifier la connectivité** : Le serveur doit être accessible depuis GitHub Actions
3. **Consulter les logs GitHub Actions** : Allez dans **Actions** > Cliquez sur le workflow en échec > Consultez les logs de chaque étape

#### L'API ne démarre pas

1. **Vérifier le port** : Le port 8001 doit être libre
   ```bash
   netstat -tulpn | grep 8001
   ```

2. **Vérifier les permissions** :
   ```bash
   ls -la /var/www/html/jungle-alert-api/storage
   ls -la /var/www/html/jungle-alert-api/bootstrap/cache
   ```

3. **Vérifier Composer** :
   ```bash
   composer --version
   ```

4. **Vérifier PHP** :
   ```bash
   php -v
   ```

#### Les migrations échouent

C'est normal si les tables existent déjà. Le pipeline continue quand même le déploiement.

Pour forcer les migrations :
```bash
cd /var/www/html/jungle-alert-api
php artisan migrate:fresh --force  # ⚠️ Supprime toutes les données
```

### Déploiement Manuel (Alternative)

Si vous préférez déployer manuellement, utilisez le script :

```bash
cd /home/kevram/Documents/codes/wilfried/junglealert-api
chmod +x deploy_laravel_apache.sh
./deploy_laravel_apache.sh
```

### Sécurité

⚠️ **Important** :
- Ne commitez jamais les secrets dans le code
- Utilisez toujours les secrets GitHub Actions
- Le fichier `.env` n'est jamais inclus dans l'archive de déploiement
- Les fichiers sensibles sont exclus automatiquement

### Fichiers Exclus du Déploiement

Les fichiers suivants ne sont **pas** inclus dans l'archive de déploiement :
- `.git/` et `.github/` (sauf les workflows)
- `vendor/` (réinstallé sur le serveur)
- `node_modules/`
- `.env`, `.env.backup`, `.env.production`
- `storage/logs/*`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`
- `*.log`
- `.phpunit.cache`
- `tests/`
- `test_*.php`, `test_*.json`, `test_*.py`
- `deploy_*.sh`

### Endpoints API

Une fois déployé, l'API est disponible sur :
- **Base URL** : `http://31.97.185.5:8001`
- **Health Check** : `GET /api/health`
- **Register** : `POST /api/v1/auth/register`
- **Login** : `POST /api/v1/auth/login`
- **Dashboard** : `GET /api/v1/dashboard`
- **Products** : `GET /api/v1/products`
- **Scrape Preview** : `POST /api/v1/products/scrape-preview`

### Support

Pour toute question ou problème, consultez :
- Les logs GitHub Actions dans l'onglet **Actions**
- Les logs du serveur : `/var/log/jungle-alert-laravel.log`
- La documentation Laravel : https://laravel.com/docs

