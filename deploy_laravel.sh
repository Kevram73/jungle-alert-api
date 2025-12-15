#!/bin/bash

# Script de déploiement pour l'API Laravel Jungle Alert
# Usage: ./deploy_laravel.sh

set -e

echo "🚀 Déploiement de l'API Laravel Jungle Alert"

# Configuration
SERVER_IP="31.97.185.5"
SERVER_USER="root"
SERVER_PASSWORD="Alkashi13@@#"
REMOTE_PATH="/opt/jungle-alert-laravel"
LOCAL_PATH="/home/kevram/Documents/codes/wilfried/junglealert-api"

echo "📦 Création de l'archive..."

# Créer une archive du projet Laravel
cd /home/kevram/Documents/codes/wilfried
tar -czf junglealert-laravel.tar.gz junglealert-api/

echo "📤 Upload vers le serveur..."

# Upload vers le serveur
sshpass -p "$SERVER_PASSWORD" scp -o StrictHostKeyChecking=no junglealert-laravel.tar.gz $SERVER_USER@$SERVER_IP:/tmp/

echo "🔧 Installation sur le serveur..."

# Exécuter les commandes sur le serveur
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    # Arrêter les services existants
    pkill -f "php artisan serve" || true
    systemctl stop jungle-alert-api || true
    
    # Supprimer l'ancienne installation
    rm -rf /opt/jungle-alert-laravel
    
    # Créer le répertoire
    mkdir -p /opt/jungle-alert-laravel
    
    # Extraire l'archive
    cd /opt/jungle-alert-laravel
    tar -xzf /tmp/junglealert-laravel.tar.gz --strip-components=1
    
    # Installer les dépendances PHP
    composer install --no-dev --optimize-autoloader
    
    # Configurer les permissions
    chown -R www-data:www-data /opt/jungle-alert-laravel
    chmod -R 755 /opt/jungle-alert-laravel
    chmod -R 775 /opt/jungle-alert-laravel/storage
    chmod -R 775 /opt/jungle-alert-laravel/bootstrap/cache
    
    # Configurer l'environnement (préserver le .env existant)
    if [ ! -f .env ]; then
        echo "📝 Création du fichier .env..."
        cp .env.example .env
        php artisan key:generate
        
        # Configurer la base de données uniquement si le fichier n'existait pas
        sed -i 's/DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
        sed -i 's/DB_DATABASE=laravel/DB_DATABASE=junglealert/' .env
        sed -i 's/DB_USERNAME=root/DB_USERNAME=work4connect/' .env
        sed -i 's/DB_PASSWORD=/DB_PASSWORD=Work4Connect2024!/' .env
    else
        echo "✅ Fichier .env existant préservé"
    fi
    
    # Exécuter les migrations
    php artisan migrate --force
    
    # Configurer le scheduler (cron job)
    (crontab -l 2>/dev/null | grep -v "artisan schedule:run" ; echo "* * * * * cd /opt/jungle-alert-laravel && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    
    # Nettoyer le cache
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Reconstruire le cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "✅ Installation terminée"
EOF

echo "🌐 Configuration de Nginx..."

# Configurer Nginx
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    cat > /etc/nginx/sites-available/jungle-alert-laravel << 'NGINX_EOF'
server {
    listen 8000;
    server_name _;
    root /opt/jungle-alert-laravel/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX_EOF

    # Activer le site
    ln -sf /etc/nginx/sites-available/jungle-alert-laravel /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Tester la configuration Nginx
    nginx -t
    
    # Redémarrer Nginx
    systemctl reload nginx
    
    echo "✅ Nginx configuré"
EOF

echo "🚀 Démarrage des services..."

# Démarrer l'API Laravel
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    cd /opt/jungle-alert-laravel
    
    # Démarrer l'API en arrière-plan
    nohup php artisan serve --host=0.0.0.0 --port=8000 > /var/log/jungle-alert-laravel.log 2>&1 &
    
    echo "✅ API Laravel démarrée sur le port 8000"
EOF

echo "🧪 Test de l'API..."

# Tester l'API
sleep 5
curl -X GET "http://$SERVER_IP:8000/api/health" || echo "❌ Test de santé échoué"

echo "🎉 Déploiement terminé!"
echo "📍 API disponible sur: http://$SERVER_IP:8000"
echo "🔗 Endpoints:"
echo "  - Health: GET /api/health"
echo "  - Register: POST /api/v1/auth/register"
echo "  - Login: POST /api/v1/auth/login"
echo "  - Dashboard: GET /api/v1/dashboard"
echo "  - Profile: GET /api/v1/users/me"

# Nettoyer
rm -f /home/kevram/Documents/codes/wilfried/junglealert-laravel.tar.gz


