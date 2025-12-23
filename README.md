# Jungle Alert API 🛒

**Jungle Alert** est une application mobile (Android et iOS) qui vous aide à économiser de l'argent lors de vos achats sur Amazon. Partagez simplement un produit Amazon dans l'application, définissez le prix souhaité, et Jungle Alert le suivra automatiquement pour vous. Dès que le prix baisse, vous recevrez une alerte directement sur votre canal choisi.

## 📱 À propos de Jungle Alert

Jungle Alert simplifie le suivi des prix Amazon. Au lieu de vérifier manuellement les prix encore et encore, l'application surveille silencieusement les prix en arrière-plan et vous informe dès qu'ils deviennent plus abordables.

### Fonctionnalités principales

- ✅ **Suivi automatique des prix** : Surveillez vos produits Amazon préférés
- ✅ **Alertes multi-canaux** : Email (gratuit), WhatsApp et Push (Premium)
- ✅ **Support multi-marchés** : Compatible avec tous les principaux marchés Amazon
- ✅ **Interface simple** : Design épuré et convivial
- ✅ **Respect de la vie privée** : Conforme RGPD avec option "supprimer mes données"

## 🎯 Plans et tarification

### Plan Gratuit
- Suivi de **1 produit**
- Alertes par **email uniquement**

### Premium Simple (€10/an)
- Suivi de **1 produit**
- Alertes via **WhatsApp** ou **Push notifications**

### Premium Deluxe (€30/an)
- Suivi **illimité** de produits
- Alertes via **WhatsApp** ou **Push notifications**

## 🌍 Marchés Amazon supportés

L'application fonctionne avec tous les principaux marchés Amazon :
- 🇺🇸 **Amazon.com** (États-Unis)
- 🇩🇪 **Amazon.de** (Allemagne)
- 🇬🇧 **Amazon.co.uk** (Royaume-Uni)
- 🇫🇷 **Amazon.fr** (France)
- 🇮🇹 **Amazon.it** (Italie)
- 🇪🇸 **Amazon.es** (Espagne)
- 🇧🇷 **Amazon.com.br** (Brésil)
- 🇮🇳 **Amazon.in** (Inde)
- 🇨🇦 **Amazon.ca** (Canada)

## 🚀 Installation et démarrage rapide

### Prérequis

- **Docker** et **Docker Compose** installés
- Aucune autre dépendance nécessaire

### Démarrage avec Docker (recommandé)

```bash
# 1. Construire les images
docker-compose build

# 2. Démarrer les services
docker-compose up -d

# 3. Initialiser la base de données
docker-compose exec app python init_db.py

# 4. L'API est disponible sur http://localhost:5000
```

### Commandes utiles

```bash
# Voir les logs
docker-compose logs -f app

# Arrêter les services
docker-compose down

# Redémarrer
docker-compose restart

# Ouvrir un shell dans le conteneur
docker-compose exec app /bin/bash
```

## 📡 API Endpoints

### Authentification
- `POST /api/v1/auth/register` - Créer un compte
- `POST /api/v1/auth/login` - Se connecter
- `GET /api/v1/auth/me` - Informations utilisateur actuel
- `POST /api/v1/auth/logout` - Se déconnecter

### Produits
- `GET /api/v1/products` - Liste des produits de l'utilisateur
- `POST /api/v1/products` - Ajouter un produit à suivre
- `GET /api/v1/products/{id}` - Détails d'un produit
- `PUT /api/v1/products/{id}` - Modifier un produit
- `DELETE /api/v1/products/{id}` - Supprimer un produit
- `POST /api/v1/products/scrape-preview` - Prévisualiser le scraping (public)
- `POST /api/v1/products/{id}/scrape-update` - Mettre à jour les données d'un produit
- `GET /api/v1/products/{id}/price-history` - Historique des prix

### Alertes
- `GET /api/v1/alerts` - Liste des alertes
- `POST /api/v1/alerts` - Créer une alerte
- `GET /api/v1/alerts/{id}` - Détails d'une alerte
- `PUT /api/v1/alerts/{id}` - Modifier une alerte
- `DELETE /api/v1/alerts/{id}` - Supprimer une alerte
- `POST /api/v1/alerts/{id}/toggle` - Activer/désactiver une alerte
- `GET /api/v1/alerts/active` - Alertes actives
- `GET /api/v1/alerts/triggered` - Alertes déclenchées
- `POST /api/v1/products/{id}/check-alerts` - Vérifier les alertes d'un produit

### Abonnements
- `GET /api/v1/subscriptions` - Liste des abonnements
- `POST /api/v1/subscriptions` - Créer un abonnement
- `GET /api/v1/subscriptions/plans` - Plans disponibles
- `GET /api/v1/subscriptions/limits` - Limites de l'utilisateur

### Utilisateurs
- `GET /api/v1/users/me` - Profil utilisateur
- `PUT /api/v1/users/me` - Modifier le profil
- `POST /api/v1/users/change-password` - Changer le mot de passe
- `POST /api/v1/users/me/fcm-token` - Enregistrer le token FCM (Push)
- `DELETE /api/v1/users/me` - Supprimer le compte

### Tableau de bord
- `GET /api/v1/dashboard` - Statistiques et activité récente

### Affiliation
- `GET /api/v1/affiliate/products/{id}/buy-link` - Lien d'achat
- `POST /api/v1/affiliate/products/{id}/track-click` - Suivre un clic

### Newsletter
- `GET /api/v1/newsletter/preview` - Aperçu de la newsletter
- `GET /api/v1/newsletter/consent` - Consentement newsletter
- `PUT /api/v1/newsletter/consent` - Mettre à jour le consentement

### RGPD
- `GET /api/v1/gdpr/export-data` - Exporter toutes les données
- `DELETE /api/v1/gdpr/delete-account` - Supprimer le compte et toutes les données

### Health Check
- `GET /api/health` - Vérifier l'état de l'API

## 🧪 Tests

### Tester toutes les routes

```bash
docker-compose exec app python test_all_routes.py
```

### Tester le scraping

```bash
docker-compose exec app python test_scraping.py "https://amzn.eu/d/bvp7pE1"
```

**Résultats des tests** : ✅ 94.1% de réussite (16/17 tests)

## 🔧 Architecture technique

### Stack technologique

- **Backend** : Python Flask
- **Base de données** : MySQL 8.0
- **Scraping** : Selenium avec Chrome WebDriver
- **Authentification** : JWT (Flask-JWT-Extended)
- **Containerisation** : Docker & Docker Compose

### Services

- **API Flask** : Port 5000
- **MySQL** : Port 3308 (externe) → 3306 (interne)

### Scraping Amazon

Le service de scraping utilise **Selenium** avec Chrome pour :
- ✅ Charger les pages Amazon de manière réaliste
- ✅ Éviter la détection de bots (anti-CAPTCHA)
- ✅ Extraire toutes les données produit (prix, images, description, etc.)
- ✅ Gérer les retries automatiques en cas d'erreur
- ✅ Support des URLs raccourcies (amzn.eu, amzn.to)

## 📊 Structure de la base de données

- **users** - Utilisateurs
- **products** - Produits suivis
- **alerts** - Alertes de prix
- **subscriptions** - Abonnements
- **price_histories** - Historique des prix
- **affiliate_clicks** - Clics d'affiliation

## 🔒 Sécurité et confidentialité

- ✅ **Conforme RGPD** : Export et suppression des données
- ✅ **Authentification JWT** : Tokens sécurisés
- ✅ **Validation des données** : Vérification des entrées
- ✅ **CORS configuré** : Protection contre les requêtes non autorisées

## 📝 Exemple d'utilisation

### Créer un compte

```bash
curl -X POST http://localhost:5000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "username": "johndoe",
    "password": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

### Se connecter

```bash
curl -X POST http://localhost:5000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }'
```

### Ajouter un produit à suivre

```bash
curl -X POST http://localhost:5000/api/v1/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amazon_url": "https://amzn.eu/d/bvp7pE1",
    "target_price": 30.0
  }'
```

### Créer une alerte

```bash
curl -X POST http://localhost:5000/api/v1/alerts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "alert_type": "PRICE_DROP",
    "target_price": 25.0
  }'
```

## 🐛 Dépannage

### L'API ne démarre pas

```bash
# Vérifier les logs
docker-compose logs app

# Vérifier le statut
docker-compose ps

# Reconstruire l'image
docker-compose build --no-cache
```

### Erreur de connexion à la base de données

```bash
# Vérifier que MySQL est démarré
docker-compose ps db

# Vérifier les logs MySQL
docker-compose logs db

# Réinitialiser la base de données
docker-compose exec app python init_db.py
```

### Problème de scraping

- Vérifiez que Chrome est installé dans le conteneur
- Augmentez les délais dans la configuration
- Vérifiez les logs : `docker-compose logs app | grep -i scrape`

## 📄 Licence

Ce projet est fourni à titre éducatif. Utilisez-le de manière responsable.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

---

**Jungle Alert** - Suivez vos prix Amazon, économisez intelligemment 💰
