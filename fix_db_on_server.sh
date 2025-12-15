#!/bin/bash

# Script pour corriger la connexion MySQL sur le serveur distant
# Usage: ./fix_db_on_server.sh

set -e

echo "🔧 Correction de la connexion MySQL sur le serveur"

# Configuration
SERVER_IP="31.97.185.5"
SERVER_USER="root"
SERVER_PASSWORD="Alkashi13@@#"

# Upload et exécution du script de correction sur le serveur
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    cd /opt/jungle-alert-laravel
    
    echo "🔍 Vérification de MySQL..."
    systemctl start mysql || systemctl start mariadb || true
    sleep 2
    
    # Vérifier que MySQL est en cours d'exécution
    if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mariadb; then
        echo "⚠️  MySQL n'est pas démarré, tentative de démarrage..."
        systemctl start mysql || systemctl start mariadb || true
        sleep 3
    fi
    
    if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
        echo "✅ MySQL est en cours d'exécution"
    else
        echo "❌ MySQL n'est pas démarré"
        exit 1
    fi
    
    # Sauvegarder le .env existant
    if [ -f .env ]; then
        echo "💾 Sauvegarde du fichier .env..."
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    fi
    
    # Vérifier les identifiants MySQL actuels
    echo "🔍 Vérification des identifiants MySQL actuels..."
    if grep -q "DB_USERNAME=" .env 2>/dev/null; then
        CURRENT_USER=$(grep "DB_USERNAME=" .env | cut -d '=' -f2)
        CURRENT_PASS=$(grep "DB_PASSWORD=" .env | cut -d '=' -f2)
        echo "Utilisateur actuel: $CURRENT_USER"
        
        # Tester la connexion avec les identifiants actuels
        if mysql -u "$CURRENT_USER" -p"$CURRENT_PASS" -e "SELECT 1;" 2>/dev/null; then
            echo "✅ Les identifiants actuels fonctionnent"
            exit 0
        else
            echo "❌ Les identifiants actuels ne fonctionnent pas"
        fi
    fi
    
    # Essayer avec root
    echo "🔍 Test avec l'utilisateur root..."
    if mysql -u root -e "SELECT 1;" 2>/dev/null; then
        echo "✅ Connexion root réussie"
        ROOT_ACCESS=true
    else
        echo "⚠️  Accès root refusé, essai avec mot de passe..."
        # Essayer avec différents mots de passe courants
        for pass in "" "root" "password" "Alkashi13@@#"; do
            if mysql -u root -p"$pass" -e "SELECT 1;" 2>/dev/null 2>&1; then
                echo "✅ Connexion root réussie avec mot de passe"
                ROOT_ACCESS=true
                ROOT_PASS="$pass"
                break
            fi
        done
    fi
    
    if [ "$ROOT_ACCESS" = true ]; then
        # Créer ou mettre à jour l'utilisateur work4connect
        echo "👤 Création/mise à jour de l'utilisateur work4connect..."
        if [ -z "$ROOT_PASS" ]; then
            mysql -u root << SQL
CREATE USER IF NOT EXISTS 'work4connect'@'localhost' IDENTIFIED BY 'Work4Connect2024!';
GRANT ALL PRIVILEGES ON junglealert.* TO 'work4connect'@'localhost';
FLUSH PRIVILEGES;
SQL
        else
            mysql -u root -p"$ROOT_PASS" << SQL
CREATE USER IF NOT EXISTS 'work4connect'@'localhost' IDENTIFIED BY 'Work4Connect2024!';
GRANT ALL PRIVILEGES ON junglealert.* TO 'work4connect'@'localhost';
FLUSH PRIVILEGES;
SQL
        fi
        
        # Créer la base de données si elle n'existe pas
        echo "🗄️  Création de la base de données..."
        if [ -z "$ROOT_PASS" ]; then
            mysql -u root -e "CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        else
            mysql -u root -p"$ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        fi
        
        # Mettre à jour le fichier .env
        echo "📝 Mise à jour du fichier .env..."
        if [ ! -f .env ]; then
            cp .env.example .env
            php artisan key:generate
        fi
        
        sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
        sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env || echo "DB_PORT=3306" >> .env
        sed -i 's/DB_DATABASE=.*/DB_DATABASE=junglealert/' .env
        sed -i 's/DB_USERNAME=.*/DB_USERNAME=work4connect/' .env
        sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=Work4Connect2024!/' .env
        
        # Vider le cache
        echo "🧹 Nettoyage du cache..."
        php artisan config:clear
        php artisan cache:clear
        
        # Tester la connexion
        echo "🧪 Test de la connexion..."
        if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connexion réussie!';" 2>/dev/null; then
            echo "✅ Connexion à la base de données réussie!"
        else
            echo "❌ Erreur de connexion, vérifiez les identifiants"
        fi
        
        # Recréer le cache
        echo "💾 Recréation du cache..."
        php artisan config:cache
        php artisan route:cache
        
        echo "✅ Configuration terminée!"
    else
        echo "❌ Impossible de se connecter à MySQL avec root"
        echo "⚠️  Veuillez vérifier manuellement les identifiants MySQL"
        exit 1
    fi
EOF

echo "✅ Script de correction exécuté sur le serveur"

