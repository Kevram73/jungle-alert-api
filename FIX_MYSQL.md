# Correction de la connexion MySQL

## Problème
L'API Laravel ne peut pas se connecter à MySQL : `Connection refused`

## Solution rapide

### Option 1 : Exécuter le script de correction sur le serveur

1. Connectez-vous au serveur :
```bash
ssh root@31.97.185.5
```

2. Téléchargez et exécutez le script :
```bash
cd /var/www/html/jungle-alert-api
wget https://raw.githubusercontent.com/votre-repo/junglealert-api/main/fix_mysql_connection.sh
# OU copiez le fichier fix_mysql_connection.sh sur le serveur
chmod +x fix_mysql_connection.sh
./fix_mysql_connection.sh
```

### Option 2 : Correction manuelle

1. Connectez-vous au serveur :
```bash
ssh root@31.97.185.5
```

2. Vérifiez et démarrez MySQL :
```bash
systemctl start mysql || systemctl start mariadb
systemctl enable mysql || systemctl enable mariadb
systemctl status mysql || systemctl status mariadb
```

3. Configurez le fichier .env :
```bash
cd /var/www/html/jungle-alert-api
nano .env
```

Assurez-vous que ces lignes sont correctes :
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=junglealert
DB_USERNAME=work4connect
DB_PASSWORD=Work4Connect2024!
```

4. Videz le cache et testez :
```bash
php artisan config:clear
php artisan cache:clear
php artisan db:show
```

5. Créez la base de données si nécessaire :
```bash
mysql -u work4connect -p'Work4Connect2024!' -e "CREATE DATABASE IF NOT EXISTS junglealert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. Exécutez les migrations :
```bash
php artisan migrate --force
```

7. Recréez le cache :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

8. Redémarrez l'API :
```bash
pkill -f "php artisan serve"
cd /var/www/html/jungle-alert-api
nohup php artisan serve --host=0.0.0.0 --port=8001 > /var/log/jungle-alert-laravel.log 2>&1 &
```

## Vérification

Testez la connexion :
```bash
curl http://31.97.185.5:8001/api/health
```

Si vous obtenez une réponse JSON, la connexion fonctionne !

