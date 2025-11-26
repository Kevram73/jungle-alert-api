# GitHub Actions - Déploiement Automatique

Ce repository utilise GitHub Actions pour automatiser le déploiement de l'API Laravel sur le serveur de production.

## Configuration des Secrets

Pour que le pipeline fonctionne, vous devez configurer les secrets suivants dans votre repository GitHub :

1. Allez dans **Settings** > **Secrets and variables** > **Actions**
2. Cliquez sur **New repository secret**
3. Ajoutez les secrets suivants :

### Secrets requis

- `SERVER_IP` : L'adresse IP du serveur (ex: `31.97.185.5`)
- `SERVER_USER` : Le nom d'utilisateur SSH (ex: `root`)
- `SERVER_PASSWORD` : Le mot de passe SSH du serveur

### Comment ajouter un secret

1. Dans votre repository GitHub, allez dans **Settings**
2. Dans le menu de gauche, cliquez sur **Secrets and variables** > **Actions**
3. Cliquez sur **New repository secret**
4. Entrez le nom du secret (ex: `SERVER_IP`)
5. Entrez la valeur du secret
6. Cliquez sur **Add secret**

## Déclenchement du déploiement

Le pipeline se déclenche automatiquement dans les cas suivants :

1. **Push sur main/master** : Chaque push sur la branche principale déclenche un déploiement
2. **Déclenchement manuel** : Vous pouvez déclencher le déploiement manuellement depuis l'onglet **Actions** de GitHub

## Étapes du déploiement

1. ✅ **Checkout** : Récupération du code source
2. 📦 **Archive** : Création d'une archive du projet (excluant node_modules, vendor, logs, etc.)
3. 📤 **Upload** : Transfert de l'archive vers le serveur
4. 🔧 **Installation** : 
   - Extraction de l'archive
   - Installation des dépendances Composer
   - Configuration des permissions
   - Exécution des migrations
   - Mise en cache de la configuration
5. 🚀 **Démarrage** : Démarrage de l'API Laravel sur le port 8001
6. 🧪 **Test** : Vérification que l'API répond correctement
7. 🧹 **Nettoyage** : Suppression des fichiers temporaires

## Logs et débogage

Les logs de l'API sont disponibles sur le serveur dans :
```
/var/log/jungle-alert-laravel.log
```

Pour voir les logs en temps réel :
```bash
tail -f /var/log/jungle-alert-laravel.log
```

## Dépannage

### Le déploiement échoue

1. Vérifiez que tous les secrets sont correctement configurés
2. Vérifiez que le serveur est accessible depuis GitHub Actions
3. Consultez les logs dans l'onglet **Actions** de GitHub
4. Vérifiez les logs du serveur : `/var/log/jungle-alert-laravel.log`

### L'API ne démarre pas

1. Vérifiez que le port 8001 n'est pas déjà utilisé
2. Vérifiez les permissions des fichiers
3. Vérifiez que Composer est installé sur le serveur
4. Vérifiez la configuration de la base de données dans `.env`

### Les migrations échouent

Les migrations peuvent échouer si les tables existent déjà. C'est normal et le déploiement continue quand même.

## Sécurité

⚠️ **Important** : Ne commitez jamais les secrets dans le code source. Utilisez toujours les secrets GitHub Actions.

Les fichiers suivants sont exclus de l'archive de déploiement :
- `.env` et fichiers de configuration sensibles
- `vendor/` (réinstallé sur le serveur)
- `node_modules/`
- Logs et fichiers de cache
- Fichiers de test

