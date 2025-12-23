# Jungle Alert API - Flask Version

Réplique de l'application Laravel Jungle Alert en Python Flask avec scraping Amazon via Selenium.

## Installation

1. Installer les dépendances:
```bash
pip install -r requirements.txt
```

2. Configurer l'environnement:
```bash
cp .env.example .env
# Éditer .env avec vos paramètres de base de données
```

3. Initialiser la base de données:
```bash
flask db init
flask db migrate -m "Initial migration"
flask db upgrade
```

4. Lancer l'application:
```bash
python app.py
```

## Configuration

- **Base de données**: MySQL (même base que l'application Laravel)
- **Scraping**: Selenium avec Chrome WebDriver
- **Authentification**: JWT (Flask-JWT-Extended)

## API Endpoints

L'API est compatible avec l'application Laravel et expose les mêmes endpoints:

- `/api/v1/auth/*` - Authentification
- `/api/v1/products/*` - Gestion des produits
- `/api/v1/alerts/*` - Gestion des alertes
- `/api/v1/subscriptions/*` - Gestion des abonnements
- `/api/v1/users/*` - Gestion des utilisateurs
- `/api/v1/dashboard` - Tableau de bord
- `/api/v1/affiliate/*` - Liens d'affiliation
- `/api/v1/newsletter/*` - Newsletter
- `/api/v1/gdpr/*` - RGPD

## Scraping Amazon

Le service de scraping utilise Selenium pour:
- Charger les pages Amazon de manière réaliste
- Éviter la détection de bots
- Extraire toutes les données produit (prix, images, description, etc.)
- Gérer les retries en cas de captcha

## Notes

- L'application partage la même base de données MySQL que l'application Laravel
- Les modèles sont compatibles avec la structure Laravel existante
- Le scraping est plus robuste avec Selenium qu'avec des requêtes HTTP simples

