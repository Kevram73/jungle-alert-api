@echo off
REM Script de démarrage pour Docker (Windows)

echo 🛒 Jungle Alert - Démarrage avec Docker
echo ========================================
echo.

REM Vérifier si Docker est installé
where docker >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Docker n'est pas installé. Veuillez l'installer d'abord.
    exit /b 1
)

where docker-compose >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Docker Compose n'est pas installé. Veuillez l'installer d'abord.
    exit /b 1
)

REM Construire les images si nécessaire
echo 📦 Vérification des images Docker...
docker images | findstr "jungle_scrapping-app" >nul
if %ERRORLEVEL% NEQ 0 (
    echo 🔨 Construction des images...
    docker-compose build
)

REM Démarrer les services
echo.
echo 🚀 Démarrage des services...
docker-compose up -d

REM Attendre que la base de données soit prête
echo.
echo ⏳ Attente du démarrage de la base de données...
timeout /t 10 /nobreak >nul

REM Initialiser la base de données
echo.
echo 🗄️  Initialisation de la base de données...
docker-compose exec -T app python init_db.py

echo.
echo ✅ Services démarrés!
echo.
echo 📚 API disponible sur: http://localhost:5000
echo 📊 Health check: http://localhost:5000/api/health
echo 🗄️  MySQL: localhost:3306
echo.
echo Pour voir les logs: docker-compose logs -f
echo Pour arrêter: docker-compose down
pause


