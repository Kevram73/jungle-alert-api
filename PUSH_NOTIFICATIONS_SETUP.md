# Configuration des Push Notifications FCM

## État Actuel

✅ **Backend prêt** : Le système d'envoi de push notifications est implémenté
⚠️ **FCM Token manquant** : L'utilisateur jeankiller1@gmail.com n'a pas encore de token FCM valide

## Pour Envoyer des Push Notifications

### 1. Configuration FCM Server Key

Ajoutez votre clé serveur Firebase dans le fichier `.env` :

```env
FCM_SERVER_KEY=your_firebase_server_key_here
```

**Comment obtenir la clé FCM :**
1. Allez sur [Firebase Console](https://console.firebase.google.com/)
2. Sélectionnez votre projet (ou créez-en un)
3. Allez dans **Paramètres du projet** (⚙️) > **Cloud Messaging**
4. Copiez la **Clé serveur** (Server Key)

### 2. Obtenir un FCM Token depuis l'Application Mobile

Pour que l'utilisateur reçoive des push notifications, il doit :
1. Ouvrir l'application mobile
2. Se connecter avec son compte (jeankiller1@gmail.com)
3. Accorder les permissions de notification
4. L'application doit enregistrer le FCM token automatiquement

**Note** : L'application mobile doit être mise à jour pour :
- Intégrer `firebase_messaging` package
- Obtenir le FCM token au démarrage
- Envoyer le token au backend via `POST /api/v1/users/me/fcm-token`

### 3. Tester l'Envoi de Push Notifications

#### Option A : Via Commande Artisan (recommandé)

```bash
php artisan push:test jeankiller1@gmail.com
```

#### Option B : Via API (si vous avez un token de test)

```bash
curl -X POST http://31.97.185.5:8001/api/v1/users/me/fcm-token \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"fcm_token": "YOUR_FCM_TOKEN"}'
```

### 4. Commandes Disponibles

#### Définir un token de test (pour développement)
```bash
php artisan fcm:set-test-token jeankiller1@gmail.com "your_test_token"
```

#### Envoyer une notification de test
```bash
php artisan push:test jeankiller1@gmail.com
```

## Prochaines Étapes

### Pour l'Application Mobile

1. **Ajouter firebase_messaging** dans `pubspec.yaml` :
```yaml
dependencies:
  firebase_messaging: ^14.7.9
  firebase_core: ^2.24.2
```

2. **Initialiser Firebase** dans `main.dart` :
```dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(MyApp());
}
```

3. **Obtenir et envoyer le FCM token** :
```dart
final FirebaseMessaging _messaging = FirebaseMessaging.instance;

// Obtenir le token
String? token = await _messaging.getToken();

// Envoyer au backend
await http.post(
  Uri.parse('${Urls.baseUrl}/users/me/fcm-token'),
  headers: {
    'Authorization': 'Bearer $accessToken',
    'Content-Type': 'application/json',
  },
  body: jsonEncode({'fcm_token': token}),
);
```

## Dépannage

### Erreur : "FCM_SERVER_KEY not configured"
➡️ Ajoutez `FCM_SERVER_KEY` dans votre fichier `.env`

### Erreur : "User does not have an FCM token"
➡️ L'utilisateur doit se connecter depuis l'application mobile pour obtenir un token

### Erreur : "InvalidRegistration" ou "NotRegistered"
➡️ Le token FCM est invalide ou expiré. L'utilisateur doit se reconnecter

### Les notifications ne sont pas reçues
➡️ Vérifiez :
1. Le FCM Server Key est correct
2. Le token FCM est valide
3. L'application mobile a les permissions de notification
4. L'application est en cours d'exécution ou en arrière-plan

## Test Rapide

Pour tester rapidement avec un token de test :

```bash
# 1. Définir un token de test
php artisan fcm:set-test-token jeankiller1@gmail.com "test_token_123"

# 2. Essayer d'envoyer (cela échouera car ce n'est pas un vrai token)
php artisan push:test jeankiller1@gmail.com
```

**Note** : Un token de test ne fonctionnera pas avec FCM. Il faut un vrai token obtenu depuis Firebase.

