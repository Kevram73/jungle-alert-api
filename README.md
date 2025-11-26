# 🦁 Jungle Alert API

API REST Laravel pour le suivi de prix de produits Amazon avec système d'alertes et notifications.

## 📋 Table des matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Endpoints API](#endpoints-api)
- [Déploiement](#déploiement)
- [Documentation](#documentation)

## 🎯 À propos

Jungle Alert est une API backend qui permet aux utilisateurs de :
- Suivre les prix de produits Amazon en temps réel
- Créer des alertes pour être notifié lorsque les prix baissent
- Gérer leurs produits et alertes via une interface mobile/web
- Recevoir des notifications push, email et WhatsApp

## ✨ Fonctionnalités

### 🔐 Authentification
- Inscription et connexion utilisateur
- Authentification par token (Laravel Sanctum)
- Gestion de profil utilisateur
- Changement de mot de passe
- Conformité GDPR (export/suppression de données)

### 📦 Gestion de Produits
- Ajout de produits Amazon via URL (support des URLs courtes : `a.co`, `amzn.to`)
- Scraping automatique des informations produit
- Suivi des prix avec historique
- Support multi-marketplace (FR, US, UK, DE, IT, ES, BR, IN, CA, EU)
- Détection automatique de marketplace et devise
- Mise à jour en masse des prix

### 🔔 Système d'Alertes
- Création d'alertes de baisse de prix
- Notifications automatiques (Push, Email, WhatsApp)
- Gestion des alertes actives/triggered
- Alertes par produit
- Opérations en masse sur les alertes

### 💳 Abonnements
- Système de plans d'abonnement
- Limites par plan (produits, alertes)
- Gestion des abonnements utilisateur

### 📧 Newsletter
- Consentement newsletter
- Aperçu du contenu newsletter

### 🔗 Affiliation
- Génération de liens d'affiliation Amazon
- Suivi des clics d'affiliation

## 📦 Prérequis

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js & NPM (optionnel, pour les assets)
- Extension PHP : `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone <repository-url>
cd junglealert-api
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurer la base de données

Éditez le fichier `.env` et configurez vos paramètres de base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=junglealert
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Exécuter les migrations

```bash
php artisan migrate
```

### 6. (Optionnel) Installer les assets frontend

```bash
npm install
npm run build
```

### 7. Démarrer le serveur de développement

```bash
php artisan serve
```

L'API sera accessible sur `http://localhost:8000`

## ⚙️ Configuration

### Variables d'environnement importantes

```env
# Application
APP_NAME="Jungle Alert API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=junglealert
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail (pour les notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# Firebase Cloud Messaging (pour les notifications push)
FCM_SERVER_KEY=your_fcm_server_key

# Queue (pour les notifications en arrière-plan)
QUEUE_CONNECTION=database
```

### Configuration des permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 📖 Utilisation

### Health Check

```bash
curl http://localhost:8000/api/health
```

Réponse :
```json
{
  "status": "healthy",
  "message": "API is running"
}
```

### Authentification

#### Inscription
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

#### Connexion
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

Réponse :
```json
{
  "access_token": "1|...",
  "token_type": "Bearer",
  "user": { ... }
}
```

### Ajouter un produit

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amazon_url": "https://www.amazon.fr/dp/B08XXXXX",
    "target_price": 29.99
  }'
```

## 🔌 Endpoints API

### Authentification
- `POST /api/v1/auth/register` - Inscription
- `POST /api/v1/auth/login` - Connexion
- `POST /api/v1/auth/logout` - Déconnexion (protégé)
- `GET /api/v1/auth/me` - Informations utilisateur (protégé)

### Produits
- `GET /api/v1/products` - Liste des produits (protégé)
- `POST /api/v1/products` - Créer un produit (protégé)
- `GET /api/v1/products/{id}` - Détails d'un produit (protégé)
- `PUT /api/v1/products/{id}` - Mettre à jour un produit (protégé)
- `DELETE /api/v1/products/{id}` - Supprimer un produit (protégé)
- `POST /api/v1/products/scrape-preview` - Aperçu du scraping (public)
- `POST /api/v1/products/{id}/refresh` - Rafraîchir les données (protégé)
- `GET /api/v1/products/{id}/price-history` - Historique des prix (protégé)

### Alertes
- `GET /api/v1/alerts` - Liste des alertes (protégé)
- `POST /api/v1/alerts` - Créer une alerte (protégé)
- `GET /api/v1/alerts/active` - Alertes actives (protégé)
- `GET /api/v1/alerts/triggered` - Alertes déclenchées (protégé)
- `PUT /api/v1/alerts/{id}` - Mettre à jour une alerte (protégé)
- `DELETE /api/v1/alerts/{id}` - Supprimer une alerte (protégé)

### Utilisateurs
- `GET /api/v1/users/me` - Profil utilisateur (protégé)
- `PUT /api/v1/users/me` - Mettre à jour le profil (protégé)
- `POST /api/v1/users/change-password` - Changer le mot de passe (protégé)
- `DELETE /api/v1/users/me` - Supprimer le compte (protégé)

### Dashboard
- `GET /api/v1/dashboard` - Tableau de bord (protégé)

### Abonnements
- `GET /api/v1/subscriptions/plans` - Plans disponibles
- `GET /api/v1/subscriptions/limits` - Limites de l'abonnement actuel
- `POST /api/v1/subscriptions` - Créer un abonnement

### Newsletter
- `GET /api/v1/newsletter/preview` - Aperçu newsletter (protégé)
- `GET /api/v1/newsletter/consent` - Consentement newsletter (protégé)
- `PUT /api/v1/newsletter/consent` - Mettre à jour le consentement (protégé)

### GDPR
- `GET /api/v1/gdpr/export-data` - Exporter les données (protégé)
- `DELETE /api/v1/gdpr/delete-account` - Supprimer le compte (protégé)

## 🚀 Déploiement

Consultez le guide complet de déploiement dans [DEPLOYMENT.md](./DEPLOYMENT.md)

### Déploiement rapide

```bash
chmod +x deploy_laravel.sh
./deploy_laravel.sh
```

## 📚 Documentation

- [Guide de déploiement](./DEPLOYMENT.md)
- [Système d'alertes](./ALERTS_SYSTEM.md)
- [Suivi de prix](./PRICE_TRACKING_SYSTEM.md)
- [Notifications push](./PUSH_NOTIFICATIONS_SETUP.md)
- [Configuration MySQL](./FIX_MYSQL.md)

## 🛠️ Technologies utilisées

- **Framework** : Laravel 10.x
- **Base de données** : MySQL
- **Authentification** : Laravel Sanctum
- **Queue** : Laravel Queue (Database)
- **Notifications** : Firebase Cloud Messaging, Email, WhatsApp

## 📝 Notes importantes

- Les URLs Amazon courtes (`a.co`, `amzn.to`) sont supportées
- Le marketplace et la devise sont automatiquement détectés depuis l'URL
- Les notifications sont envoyées en arrière-plan via des queues
- Le scraping Amazon peut être limité par les politiques d'Amazon

## 🔒 Sécurité

- Authentification par token (Sanctum)
- Validation des données d'entrée
- Protection CSRF
- Conformité GDPR
- Hashage des mots de passe (bcrypt)

## 📄 License

Ce projet est sous licence MIT.

## 👥 Support

Pour toute question ou problème, consultez la documentation ou ouvrez une issue.

---

**Développé avec ❤️ en utilisant Laravel**
