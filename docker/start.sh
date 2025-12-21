#!/bin/bash

echo "🚀 Starting Jungle Alert Application..."

# Attendre que MySQL soit prêt (sur la machine hôte)
echo "⏳ Waiting for MySQL to be ready..."
until nc -z host.docker.internal 3306; do
  echo "MySQL is unavailable - sleeping"
  sleep 2
done

echo "✅ MySQL is ready!"

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Générer la clé d'application si nécessaire
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Exécuter les migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Créer les liens symboliques
echo "🔗 Creating storage link..."
php artisan storage:link

# Optimiser l'application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application is ready!"

# Démarrer PHP-FPM
exec php-fpm

