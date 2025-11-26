# Information sur la Clé FCM

## Problème Détecté

L'erreur 404 lors de l'envoi de push notifications peut être due à :

1. **Format de clé incorrect** : La clé fournie (`wpYt2lhrQsRkhvU_eADSN2J5YjC8M6I9J7Ye2skfWyA`) fait 43 caractères
   - Une **Server Key FCM** standard fait généralement **~152 caractères**
   - Exemple : `AAAAxxxxxxx:APA91bHxxxxx...` (format long)

2. **Type de clé** : Il est possible que ce soit :
   - Une clé d'API Firebase différente (pas la Server Key)
   - Un token d'accès
   - Une clé de projet

## Comment Obtenir la Vraie Server Key FCM

### Méthode 1 : Firebase Console (Recommandé)

1. Allez sur [Firebase Console](https://console.firebase.google.com/)
2. Sélectionnez votre projet
3. Cliquez sur l'icône ⚙️ (Paramètres) > **Paramètres du projet**
4. Allez dans l'onglet **Cloud Messaging**
5. Dans la section **Cloud Messaging API (Legacy)**, vous verrez :
   - **Clé serveur** (Server Key) - C'est celle qu'il faut !
   - Format : `AAAAxxxxxxx:APA91bHxxxxx...` (longue chaîne)

### Méthode 2 : Google Cloud Console

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Sélectionnez votre projet Firebase
3. Allez dans **APIs & Services** > **Credentials**
4. Cherchez **Server Key** dans la liste
5. Si elle n'existe pas, vous pouvez en créer une

## Vérification

Pour vérifier si votre clé est correcte :

```bash
# La clé devrait ressembler à ça (exemple) :
AAAAxxxxxxx:APA91bHxxxxx... (environ 152 caractères)
```

## Alternative : API v1 avec OAuth2

Si l'API Legacy ne fonctionne pas, vous pouvez migrer vers l'API v1 qui utilise OAuth2 au lieu de la Server Key. Cela nécessite :
- Un compte de service Google Cloud
- Configuration OAuth2
- Utilisation de l'endpoint `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send`

## Test Rapide

Une fois que vous avez la bonne Server Key :

```bash
php artisan push:test jeankiller1@gmail.com --fcm-key=VOTRE_SERVER_KEY
```

Ou ajoutez-la dans `.env` :
```env
FCM_SERVER_KEY=VOTRE_SERVER_KEY_ICI
```

Puis :
```bash
php artisan push:test jeankiller1@gmail.com
```

## Note Importante

⚠️ **Le token FCM de test ne fonctionnera pas** - Il faut un vrai token obtenu depuis l'application mobile avec Firebase Messaging configuré.

Pour obtenir un vrai token :
1. Intégrer Firebase Messaging dans l'app mobile
2. L'utilisateur doit se connecter
3. L'app obtient le token FCM automatiquement
4. L'app envoie le token au backend via `POST /api/v1/users/me/fcm-token`

