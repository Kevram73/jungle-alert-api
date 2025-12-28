#!/bin/bash
# Script de démarrage pour Docker

echo "🛒 Jungle Alert - Démarrage avec Docker"
echo "========================================"
echo ""

# Vérifier si Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

# Nettoyer les conteneurs orphelins ou corrompus
echo "🧹 Nettoyage des conteneurs existants..."
docker-compose down -v --remove-orphans 2>/dev/null || true

# Supprimer les conteneurs orphelins par nom
docker ps -aq --filter "name=junglealert" | xargs -r docker rm -f 2>/dev/null || true

# Construire les images si nécessaire
echo "📦 Vérification des images Docker..."
if ! docker images | grep -q "jungle_scrapping-app"; then
    echo "🔨 Construction des images..."
    docker-compose build
fi

# Démarrer les services
echo ""
echo "🚀 Démarrage des services..."
docker-compose up -d --force-recreate

# Attendre que la base de données soit prête
echo ""
echo "⏳ Attente du démarrage de la base de données..."
sleep 10

# Initialiser la base de données
echo ""
echo "🗄️  Initialisation de la base de données..."
docker-compose exec -T app python init_db.py || echo "⚠️  La base de données existe peut-être déjà"

echo ""
echo "✅ Services démarrés!"
echo ""
echo "📚 API disponible sur: http://localhost:5000"
echo "📊 Health check: http://localhost:5000/api/health"
echo "🗄️  MySQL: localhost:3306"
echo ""
echo "Pour voir les logs: docker-compose logs -f"
echo "Pour arrêter: docker-compose down"


