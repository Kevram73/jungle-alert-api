#!/bin/bash

# Script pour exécuter les migrations sur le serveur distant
# Usage: ./run_migrations.sh

set -e

echo "🔄 Exécution des migrations sur le serveur"

# Configuration
SERVER_IP="31.97.185.5"
SERVER_USER="root"
SERVER_PASSWORD="Alkashi13@@#"

# Exécution des migrations sur le serveur
sshpass -p "$SERVER_PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_IP << 'EOF'
    cd /opt/jungle-alert-laravel
    
    echo "🔍 Vérification de la connexion à la base de données..."
    php artisan db:show || echo "⚠️  Impossible de vérifier la base de données"
    
    echo "🔄 Exécution des migrations..."
    php artisan migrate --force
    
    echo "✅ Migrations terminées!"
    
    # Vérifier que les tables importantes existent
    echo "🔍 Vérification des tables créées..."
    php artisan tinker --execute="
        \$tables = ['users', 'personal_access_tokens', 'products', 'alerts', 'price_histories'];
        foreach (\$tables as \$table) {
            try {
                DB::table(\$table)->count();
                echo \"✅ Table '\$table' existe\n\";
            } catch (Exception \$e) {
                echo \"❌ Table '\$table' n'existe pas: \" . \$e->getMessage() . \"\n\";
            }
        }
    " || echo "⚠️  Erreur lors de la vérification"
EOF

echo "✅ Migrations exécutées sur le serveur"

