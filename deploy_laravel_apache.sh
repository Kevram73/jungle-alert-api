#!/bin/bash

# Script de déploiement pour l'API Laravel Jungle Alert avec Apache
# Usage: ./deploy_laravel_apache.sh

set -e

echo "🚀 Déploiement de l'API Laravel Jungle Alert avec Apache"

# Configuration
SERVER_IP="31.97.185.5"
SERVER_USER="root"
SERVER_PASSWORD="Alkashi13@@#"
REMOTE_PATH="/var/www/html/jungle-alert-api"
LOCAL_PATH="/home/kevram/Documents/codes/wilfried/junglealert-api"

echo "📦 Création de l'archive..."

# Créer une archive du projet Laravel
cd /home/kevram/Documents/codes/wilfried
tar -czf junglealert-laravel.tar.gz junglealert-api/

echo "📤 Upload vers le serveur..."

# Upload vers le serveur
if ! sshpass -p "$SERVER_PASSWORD" scp -o StrictHostKeyChecking=no junglealert-laravel.tar.gz $SERVER_USER@$SERVER_IP:/tmp/; then
    echo "❌ Erreur lors de l'upload. Vérifiez vos credentials SSH."
    echo "💡 Alternative: Connectez-vous manuellement au serveur et exécutez les commandes suivantes:"
    echo "   scp junglealert-laravel.tar.gz root@$SERVER_IP:/tmp/"
    exit 1
fi

echo "🔧 Installation sur le serveur..."

# Exécuter les commandes sur le serveur
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    # Arrêter les services existants
    pkill -f "php artisan serve" || true
    systemctl stop jungle-alert-api || true
    
    # Supprimer l'ancienne installation
    rm -rf /var/www/html/jungle-alert-api
    
    # Créer le répertoire
    mkdir -p /var/www/html/jungle-alert-api
    
    # Extraire l'archive
    cd /var/www/html/jungle-alert-api
    tar -xzf /tmp/junglealert-laravel.tar.gz --strip-components=1
    
    # Installer les dépendances PHP
    composer install --no-dev --optimize-autoloader
    
    # Configurer les permissions
    chown -R www-data:www-data /var/www/html/jungle-alert-api
    chmod -R 755 /var/www/html/jungle-alert-api
    chmod -R 775 /var/www/html/jungle-alert-api/storage
    chmod -R 775 /var/www/html/jungle-alert-api/bootstrap/cache
    
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
    
    # Configurer l'environnement
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    php artisan key:generate
    
    # Configurer la base de données
    sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
    sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
    sed -i 's/DB_DATABASE=.*/DB_DATABASE=junglealert/' .env
    sed -i 's/DB_USERNAME=.*/DB_USERNAME=work4connect/' .env
    sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=Work4Connect2024!/' .env
    
    # Vider le cache de configuration avant de tester la connexion
    php artisan config:clear
    php artisan cache:clear
    
    # Vérifier la connexion à la base de données
    echo "🔍 Vérification de la connexion à la base de données..."
    php artisan db:show || echo "⚠️  Impossible de se connecter à la base de données"
    
    # Créer la base de données si elle n'existe pas
    mysql -u work4connect -p'Work4Connect2024!' -e "CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || echo "⚠️  Impossible de créer la base de données (peut-être qu'elle existe déjà)"
    
    # Exécuter les migrations
    php artisan migrate --force || echo "⚠️  Erreur lors des migrations"
    
    # Nettoyer et recréer le cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "✅ Installation terminée"
EOF

echo "🌐 Configuration d'Apache..."

# Configurer Apache
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    # Créer la configuration Apache
    cat > /etc/apache2/sites-available/jungle-alert-api.conf << 'APACHE_EOF'
<VirtualHost *:80>
    ServerName 31.97.185.5
    DocumentRoot /var/www/html/jungle-alert-api/public
    
    <Directory /var/www/html/jungle-alert-api/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/jungle-alert-api_error.log
    CustomLog ${APACHE_LOG_DIR}/jungle-alert-api_access.log combined
</VirtualHost>

<VirtualHost *:8000>
    ServerName 31.97.185.5
    DocumentRoot /var/www/html/jungle-alert-api/public
    
    <Directory /var/www/html/jungle-alert-api/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/jungle-alert-api_error.log
    CustomLog ${APACHE_LOG_DIR}/jungle-alert-api_access.log combined
</VirtualHost>
APACHE_EOF

    # Activer le site
    a2ensite jungle-alert-api.conf
    a2dissite 000-default.conf
    
    # Activer les modules Apache nécessaires
    a2enmod rewrite
    a2enmod headers
    
    # Tester la configuration Apache
    apache2ctl configtest
    
    # Redémarrer Apache
    systemctl restart apache2
    
    echo "✅ Apache configuré"
EOF

echo "🚀 Démarrage des services..."

# Démarrer l'API Laravel
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    cd /var/www/html/jungle-alert-api
    
    # Démarrer l'API en arrière-plan sur le port 8001
    nohup php artisan serve --host=0.0.0.0 --port=8001 > /var/log/jungle-alert-laravel.log 2>&1 &
    
    echo "✅ API Laravel démarrée sur le port 8001"
EOF

echo "🧪 Test de l'API..."

# Tester l'API
sleep 5
curl -X GET "http://$SERVER_IP/api/health" || echo "❌ Test de santé sur port 80 échoué"
curl -X GET "http://$SERVER_IP:8000/api/health" || echo "❌ Test de santé sur port 8000 échoué"
curl -X GET "http://$SERVER_IP:8001/api/health" || echo "❌ Test de santé sur port 8001 échoué"

echo "🎉 Déploiement terminé!"
echo "📍 API disponible sur:"
echo "  - http://$SERVER_IP (port 80)"
echo "  - http://$SERVER_IP:8000 (port 8000)"
echo "  - http://$SERVER_IP:8001 (port 8001)"
echo "🔗 Endpoints:"
echo "  - Health: GET /api/health"
echo "  - Register: POST /api/v1/auth/register"
echo "  - Login: POST /api/v1/auth/login"
echo "  - Dashboard: GET /api/v1/dashboard"
echo "  - Profile: GET /api/v1/users/me"

# Nettoyer
rm -f /home/kevram/Documents/codes/wilfried/junglealert-laravel.tar.gz
