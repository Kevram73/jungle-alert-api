#!/bin/bash

# Script pour corriger la connexion MySQL sur le serveur
# À exécuter directement sur le serveur

set -e

echo "🔧 Correction de la connexion MySQL pour Jungle Alert API"

cd /var/www/html/jungle-alert-api || exit 1

# Vérifier et démarrer MySQL
echo "🔍 Vérification de MySQL..."
systemctl start mysql || systemctl start mariadb || true
systemctl enable mysql || systemctl enable mariadb || true
sleep 2

# Vérifier que MySQL est en cours d'exécution
if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mariadb; then
    echo "⚠️  MySQL n'est pas démarré, tentative de démarrage..."
    systemctl start mysql || systemctl start mariadb || true
    sleep 3
fi

# Vérifier le statut de MySQL
if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
    echo "✅ MySQL est en cours d'exécution"
else
    echo "❌ MySQL n'est pas démarré"
    exit 1
fi

# Configurer le fichier .env
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env
    php artisan key:generate
fi

# Mettre à jour la configuration de la base de données
echo "📝 Configuration de la base de données..."
sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env || echo "DB_PORT=3306" >> .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=junglealert/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=work4connect/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=Work4Connect2024!/' .env

# Vider le cache de configuration
echo "🧹 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Créer la base de données si elle n'existe pas
echo "🗄️  Création de la base de données..."
mysql -u work4connect -p'Work4Connect2024!' -e "CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || echo "⚠️  Impossible de créer la base de données (peut-être qu'elle existe déjà)"

# Vérifier la connexion à la base de données
echo "🔍 Vérification de la connexion à la base de données..."
php artisan db:show || echo "⚠️  Impossible de se connecter à la base de données"

# Exécuter les migrations
echo "🔄 Exécution des migrations..."
php artisan migrate --force || echo "⚠️  Erreur lors des migrations"

# Recréer le cache
echo "💾 Recréation du cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Configuration terminée!"
echo "🧪 Test de la connexion..."
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connexion réussie!';" || echo "❌ Erreur de connexion"

