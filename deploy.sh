#!/bin/bash

# Script de déploiement pour Jungle Alert API
# Usage: ./deploy.sh [start|stop|restart|logs|status|backup|restore]

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
COMPOSE_FILE="docker-compose.prod.yml"
PROJECT_NAME="junglealert"

# Fonctions utilitaires
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier que le fichier .env existe
check_env_file() {
    if [ ! -f .env ]; then
        print_error "Le fichier .env n'existe pas!"
        print_info "Copiez env.example vers .env et configurez les variables:"
        print_info "  cp env.example .env"
        exit 1
    fi
}

# Vérifier que Docker est installé
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé!"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose n'est pas installé!"
        exit 1
    fi
}

# Démarrer les services
start_services() {
    print_info "Démarrage des services..."
    check_env_file
    
    # Build et démarrage
    docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME up -d --build
    
    print_success "Services démarrés!"
    print_info "Attente de la disponibilité des services..."
    sleep 10
    
    # Vérifier le statut
    docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME ps
    
    print_info "Vérification de la santé de l'API..."
    if curl -f http://localhost/api/health &> /dev/null; then
        print_success "API opérationnelle!"
    else
        print_warning "L'API ne répond pas encore. Vérifiez les logs avec: ./deploy.sh logs"
    fi
}

# Arrêter les services
stop_services() {
    print_info "Arrêt des services..."
    docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME down
    print_success "Services arrêtés!"
}

# Redémarrer les services
restart_services() {
    print_info "Redémarrage des services..."
    stop_services
    sleep 2
    start_services
}

# Afficher les logs
show_logs() {
    SERVICE=${1:-}
    if [ -z "$SERVICE" ]; then
        docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME logs -f
    else
        docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME logs -f $SERVICE
    fi
}

# Afficher le statut
show_status() {
    print_info "Statut des services:"
    docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME ps
    
    echo ""
    print_info "Santé des conteneurs:"
    docker ps --filter "name=${PROJECT_NAME}" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    
    echo ""
    print_info "Utilisation des ressources:"
    docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" $(docker ps --filter "name=${PROJECT_NAME}" -q)
}

# Sauvegarder la base de données
backup_database() {
    print_info "Sauvegarde de la base de données..."
    
    # Créer le dossier de backup s'il n'existe pas
    mkdir -p backups
    
    # Nom du fichier de backup
    BACKUP_FILE="backups/junglealert_$(date +%Y%m%d_%H%M%S).sql"
    
    # Charger les variables d'environnement
    source .env
    
    # Effectuer la sauvegarde
    docker exec junglealert-db-prod mysqldump \
        -u root \
        -p${DB_ROOT_PASSWORD} \
        ${DB_NAME} > $BACKUP_FILE
    
    # Compresser la sauvegarde
    gzip $BACKUP_FILE
    
    print_success "Sauvegarde créée: ${BACKUP_FILE}.gz"
    
    # Nettoyer les anciennes sauvegardes (garder les 7 dernières)
    print_info "Nettoyage des anciennes sauvegardes..."
    ls -t backups/*.sql.gz | tail -n +8 | xargs -r rm
    print_success "Anciennes sauvegardes nettoyées!"
}

# Restaurer la base de données
restore_database() {
    BACKUP_FILE=$1
    
    if [ -z "$BACKUP_FILE" ]; then
        print_error "Usage: ./deploy.sh restore <backup_file>"
        print_info "Sauvegardes disponibles:"
        ls -lh backups/*.sql.gz 2>/dev/null || print_warning "Aucune sauvegarde trouvée"
        exit 1
    fi
    
    if [ ! -f "$BACKUP_FILE" ]; then
        print_error "Fichier de sauvegarde introuvable: $BACKUP_FILE"
        exit 1
    fi
    
    print_warning "Cette opération va écraser la base de données actuelle!"
    read -p "Êtes-vous sûr? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Restauration annulée"
        exit 0
    fi
    
    print_info "Restauration de la base de données..."
    
    # Charger les variables d'environnement
    source .env
    
    # Décompresser si nécessaire
    if [[ $BACKUP_FILE == *.gz ]]; then
        gunzip -c $BACKUP_FILE | docker exec -i junglealert-db-prod mysql \
            -u root \
            -p${DB_ROOT_PASSWORD} \
            ${DB_NAME}
    else
        docker exec -i junglealert-db-prod mysql \
            -u root \
            -p${DB_ROOT_PASSWORD} \
            ${DB_NAME} < $BACKUP_FILE
    fi
    
    print_success "Base de données restaurée!"
}

# Mettre à jour l'application
update_app() {
    print_info "Mise à jour de l'application..."
    
    # Pull les dernières modifications
    if [ -d .git ]; then
        print_info "Récupération des dernières modifications..."
        git pull
    fi
    
    # Sauvegarder la base de données avant la mise à jour
    print_info "Sauvegarde préventive de la base de données..."
    backup_database
    
    # Rebuild et redémarrage
    print_info "Reconstruction et redémarrage des services..."
    docker-compose -f $COMPOSE_FILE -p $PROJECT_NAME up -d --build
    
    print_success "Application mise à jour!"
}

# Nettoyer les ressources Docker
cleanup() {
    print_info "Nettoyage des ressources Docker..."
    
    # Supprimer les images non utilisées
    docker image prune -f
    
    # Supprimer les volumes non utilisés
    docker volume prune -f
    
    # Supprimer les réseaux non utilisés
    docker network prune -f
    
    print_success "Nettoyage terminé!"
}

# Afficher les informations système
system_info() {
    print_info "Informations système:"
    echo ""
    echo "Docker version:"
    docker --version
    echo ""
    echo "Docker Compose version:"
    docker-compose --version
    echo ""
    echo "Espace disque:"
    df -h | grep -E '^Filesystem|/$'
    echo ""
    echo "Utilisation Docker:"
    docker system df
}

# Menu d'aide
show_help() {
    cat << EOF
Usage: ./deploy.sh [COMMAND] [OPTIONS]

Commandes disponibles:
  start           Démarrer tous les services
  stop            Arrêter tous les services
  restart         Redémarrer tous les services
  logs [service]  Afficher les logs (optionnel: spécifier un service)
  status          Afficher le statut des services
  backup          Sauvegarder la base de données
  restore <file>  Restaurer la base de données depuis un fichier
  update          Mettre à jour l'application
  cleanup         Nettoyer les ressources Docker inutilisées
  info            Afficher les informations système
  help            Afficher cette aide

Exemples:
  ./deploy.sh start
  ./deploy.sh logs app
  ./deploy.sh backup
  ./deploy.sh restore backups/junglealert_20260118_120000.sql.gz

EOF
}

# Menu principal
main() {
    check_docker
    
    case "${1:-}" in
        start)
            start_services
            ;;
        stop)
            stop_services
            ;;
        restart)
            restart_services
            ;;
        logs)
            show_logs "${2:-}"
            ;;
        status)
            show_status
            ;;
        backup)
            backup_database
            ;;
        restore)
            restore_database "${2:-}"
            ;;
        update)
            update_app
            ;;
        cleanup)
            cleanup
            ;;
        info)
            system_info
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Commande inconnue: ${1:-}"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Point d'entrée
main "$@"


