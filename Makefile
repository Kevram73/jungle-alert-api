.PHONY: build up down logs shell test clean init-db help

# Variables
SERVICE_NAME = junglealert-app

help: ## Affiche cette aide
	@echo "🛒 Jungle Alert - Commandes disponibles:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construire les images Docker
	@echo "📦 Construction des images Docker..."
	docker-compose build

up: ## Démarrer tous les services
	@echo "🚀 Démarrage des services..."
	docker-compose up -d
	@echo "✅ Services démarrés!"
	@echo "📚 API: http://localhost:5000"
	@echo "📊 Health: http://localhost:5000/api/health"

down: ## Arrêter tous les services
	@echo "🛑 Arrêt des services..."
	docker-compose down

logs: ## Afficher les logs
	docker-compose logs -f $(SERVICE_NAME)

logs-db: ## Afficher les logs de la base de données
	docker-compose logs -f db

shell: ## Ouvrir un shell dans le conteneur
	@echo "🐚 Ouverture d'un shell..."
	docker-compose exec $(SERVICE_NAME) /bin/bash

shell-db: ## Ouvrir un shell MySQL
	@echo "🐚 Ouverture d'un shell MySQL..."
	docker-compose exec db mysql -u jungleuser -prootpassword junglealert

init-db: ## Initialiser la base de données
	@echo "🗄️  Initialisation de la base de données..."
	docker-compose exec $(SERVICE_NAME) python init_db.py

restart: ## Redémarrer les services
	@echo "🔄 Redémarrage des services..."
	docker-compose restart

clean: ## Nettoyer les conteneurs
	@echo "🧹 Nettoyage..."
	docker-compose down
	docker rmi jungle_scrapping-$(SERVICE_NAME) 2>/dev/null || true

clean-all: clean ## Nettoyer tout (conteneurs, images, volumes)
	@echo "🧹 Nettoyage complet..."
	docker-compose down -v
	docker system prune -f

rebuild: clean build ## Reconstruire les images depuis zéro

status: ## Voir le statut des services
	docker-compose ps









