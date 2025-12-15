#!/bin/bash

# Script to switch database connection to local MySQL
# This fixes the "Connection refused" error when remote DB is unavailable

set -e

echo "🔧 Switching to local MySQL database..."

cd /home/kevram/Documents/codes/wilfried/junglealert-api || exit 1

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Please create .env file first:"
    echo "  cp .env.example .env"
    echo "  php artisan key:generate"
    exit 1
fi

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backed up .env to .env.backup.*"

# Update database configuration to localhost
echo "📝 Updating database configuration..."
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env || echo "DB_PORT=3306" >> .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=junglealert/' .env

# Check if DB_USERNAME and DB_PASSWORD need to be set
if ! grep -q "^DB_USERNAME=" .env; then
    echo "DB_USERNAME=root" >> .env
fi

if ! grep -q "^DB_PASSWORD=" .env; then
    echo "DB_PASSWORD=" >> .env
fi

# Clear config cache
echo "🧹 Clearing config cache..."
php artisan config:clear
php artisan cache:clear

# Test connection
echo "🧪 Testing database connection..."
if php artisan db:show 2>/dev/null; then
    echo "✅ Database connection successful!"
    echo ""
    echo "📋 Current database configuration:"
    php artisan config:show database.connections.mysql | grep -E "(host|database|username)" | head -3
else
    echo "⚠️  Could not connect to database"
    echo ""
    echo "Please ensure:"
    echo "1. MySQL is running: sudo systemctl status mysql"
    echo "2. Database 'junglealert' exists"
    echo "3. User has proper permissions"
    echo ""
    echo "To create database and user:"
    echo "  mysql -u root -p"
    echo "  CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "  CREATE USER IF NOT EXISTS 'work4connect'@'localhost' IDENTIFIED BY 'Work4Connect2024';"
    echo "  GRANT ALL PRIVILEGES ON junglealert.* TO 'work4connect'@'localhost';"
    echo "  FLUSH PRIVILEGES;"
fi

echo ""
echo "✅ Configuration updated!"
echo "💡 To switch back to remote DB, restore from .env.backup.*"

