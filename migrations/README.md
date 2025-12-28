# Database Migrations

Pour initialiser les migrations Flask-Migrate:

```bash
flask db init
flask db migrate -m "Initial migration"
flask db upgrade
```

Note: Cette application utilise la même base de données MySQL que l'application Laravel, donc les tables existent déjà. Les migrations Flask sont optionnelles mais peuvent être utiles pour gérer les changements de schéma.

