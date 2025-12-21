# 🐳 Guide Docker - Jungle Alert API

Ce guide explique comment dockeriser l'application Laravel Jungle Alert en utilisant MySQL de la machine hôte.

## 📋 Prérequis

- Docker et Docker Compose installés
- MySQL installé et en cours d'exécution sur la machine hôte
- Port 5200 disponible

## 🚀 Installation

### 1. Configuration de MySQL

Assurez-vous que MySQL est configuré pour accepter les connexions depuis Docker :

**Sur Windows/Mac :**
- MySQL écoute déjà sur `localhost:3306`
- Docker utilisera `host.docker.internal` pour se connecter à MySQL

**Sur Linux :**
- Vous devrez peut-être modifier `docker-compose.yml` pour utiliser l'IP de votre machine hôte
- Ou configurer MySQL pour écouter sur `0.0.0.0` au lieu de `127.0.0.1`

### 2. Configuration de l'environnement

Créez un fichier `.env` à la racine du projet (ou copiez `.env.example`) :

```env
APP_NAME="Jungle Alert API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:5200

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=junglealert
DB_USERNAME=your_username
DB_PASSWORD=your_password

# ... autres variables d'environnement
```

**Important :** `DB_HOST=host.docker.internal` permet au conteneur Docker de se connecter à MySQL sur la machine hôte.

### 3. Construction et démarrage

```bash
# Construire les images
docker-compose build

# Démarrer les conteneurs
docker-compose up -d

# Voir les logs
docker-compose logs -f
```

### 4. Configuration initiale

```bash
# Entrer dans le conteneur
docker-compose exec app bash

# Installer les dépendances (si pas déjà fait)
composer install

# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Créer l'utilisateur admin
php artisan db:seed --class=AdminUserSeeder

# Créer le lien symbolique pour le storage
php artisan storage:link
```

## 🔧 Commandes utiles

### Gestion des conteneurs

```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose down

# Redémarrer
docker-compose restart

# Voir les logs
docker-compose logs -f app
docker-compose logs -f nginx

# Entrer dans le conteneur
docker-compose exec app bash
```

### Commandes Laravel

```bash
# Exécuter une commande Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:list

# Exécuter les tests
docker-compose exec app php artisan test
```

### Composer

```bash
# Installer une dépendance
docker-compose exec app composer require package/name

# Mettre à jour les dépendances
docker-compose exec app composer update
```

## 🌐 Accès à l'application

Une fois démarré, l'application sera accessible sur :
- **API** : http://localhost:5200
- **Admin** : http://localhost:5200/admin

## 🗄️ Configuration MySQL

### Vérifier la connexion depuis Docker

```bash
# Tester la connexion MySQL depuis le conteneur
docker-compose exec app bash -c "nc -z host.docker.internal 3306 && echo 'MySQL is reachable' || echo 'MySQL is not reachable'"
```

### Si MySQL n'est pas accessible

**Sur Linux**, modifiez `docker-compose.yml` :

```yaml
services:
  app:
    extra_hosts:
      - "host.docker.internal:172.17.0.1"  # IP du bridge Docker par défaut
```

Ou utilisez l'IP de votre machine hôte :

```yaml
services:
  app:
    environment:
      - DB_HOST=192.168.1.100  # Remplacez par votre IP
```

### Autoriser les connexions depuis Docker

Si MySQL refuse les connexions, vérifiez la configuration :

```sql
-- Se connecter à MySQL
mysql -u root -p

-- Vérifier les utilisateurs
SELECT user, host FROM mysql.user;

-- Créer un utilisateur pour Docker (si nécessaire)
CREATE USER 'junglealert'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON junglealert.* TO 'junglealert'@'%';
FLUSH PRIVILEGES;
```

## 🔍 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Voir les logs détaillés
docker-compose logs

# Vérifier les conteneurs
docker-compose ps
```

### Erreur de connexion MySQL

1. Vérifiez que MySQL est en cours d'exécution sur la machine hôte
2. Vérifiez que le port 3306 est accessible
3. Vérifiez les identifiants dans `.env`
4. Testez la connexion depuis le conteneur

### Permissions de fichiers

```bash
# Corriger les permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/bootstrap/cache
```

### Reconstruire les conteneurs

```bash
# Reconstruire sans cache
docker-compose build --no-cache

# Redémarrer
docker-compose up -d
```

## 📝 Notes

- Les fichiers du projet sont montés en volume, donc les modifications sont immédiatement visibles
- Le dossier `vendor` est monté depuis la machine hôte (si présent) ou créé dans le conteneur
- Les logs Laravel sont dans `storage/logs/` sur la machine hôte
- Le cache Laravel est dans `storage/framework/cache/` sur la machine hôte

## 🛑 Arrêt propre

```bash
# Arrêter les conteneurs
docker-compose down

# Arrêter et supprimer les volumes (attention !)
docker-compose down -v
```

