# Configuration des Webhooks Twilio

Ce guide vous explique comment configurer les webhooks Twilio pour recevoir les appels et SMS entrants.

## 🔗 URLs de Webhook

Votre application dispose des endpoints suivants :

- **Webhook principal :** `https://votre-domaine.com/webhook.php`
- **Administration :** `https://votre-domaine.com/admin.php`

## 📞 Configuration des Appels Entrants

### 1. Dans la Console Twilio

1. Allez sur [Twilio Console](https://console.twilio.com/)
2. Naviguez vers **Phone Numbers** > **Manage** > **Active numbers**
3. Cliquez sur votre numéro Twilio
4. Dans la section **Voice Configuration** :
   - **A call comes in :** Webhook
   - **URL :** `https://votre-domaine.com/webhook.php`
   - **HTTP :** POST
5. Cliquez sur **Save**

### 2. Fonctionnalités Vocales Disponibles

L'application propose un **menu vocal interactif** :

- **Touche 1 :** Informations (horaires, adresse)
- **Touche 2 :** Laisser un message vocal
- **Touche 0 :** Transfert vers un opérateur

## 📱 Configuration des SMS Entrants

### 1. Dans la Console Twilio

1. Dans la même page de configuration du numéro
2. Dans la section **Messaging Configuration** :
   - **A message comes in :** Webhook
   - **URL :** `https://votre-domaine.com/webhook.php`
   - **HTTP :** POST
3. Cliquez sur **Save**

### 2. Réponses Automatiques SMS

L'application répond automatiquement aux mots-clés :

- **INFO** → Informations générales
- **HORAIRES** → Horaires d'ouverture
- **CONTACT** → Coordonnées de contact
- **AIDE** → Liste des commandes
- **STOP** → Désabonnement
- **START** → Réabonnement

## 🛠️ Configuration Avancée

### Variables d'Environnement

Ajoutez dans votre fichier `.env` :

```env
# Configuration Twilio (existant)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=your_phone_number

# Configuration Webhooks (optionnel)
WEBHOOK_AUTH_TOKEN=your_webhook_auth_token
OPERATOR_PHONE=+33123456789
```

### Sécurisation des Webhooks

Pour sécuriser vos webhooks, vous pouvez valider la signature Twilio :

```php
// Dans webhook.php, ajoutez cette validation
use Twilio\Security\RequestValidator;

$validator = new RequestValidator($_ENV['TWILIO_AUTH_TOKEN']);
$signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
$url = 'https://votre-domaine.com/webhook.php';

if (!$validator->validate($signature, $url, $_POST)) {
    http_response_code(403);
    exit('Forbidden');
}
```

## 📊 Monitoring et Logs

### Accès à l'Administration

1. Allez sur `https://votre-domaine.com/admin.php`
2. Mot de passe par défaut : `admin123` (à changer !)
3. Consultez les logs en temps réel

### Types de Logs Disponibles

- **Appels :** Historique des appels entrants/sortants
- **SMS :** Messages reçus et envoyés
- **Webhooks :** Logs bruts des requêtes Twilio
- **Enregistrements :** URLs des messages vocaux

### Fichiers de Logs

Les logs sont stockés dans le dossier `logs/` :

```
logs/
├── calls.log          # Historique des appels
├── sms.log           # Historique des SMS
├── webhook.log       # Logs des webhooks
└── recordings.log    # Enregistrements vocaux
```

## 🔧 Dépannage

### Webhook non appelé

1. **Vérifiez l'URL :** Doit être accessible publiquement (HTTPS recommandé)
2. **Testez manuellement :** `curl -X POST https://votre-domaine.com/webhook.php`
3. **Consultez les logs Twilio :** Console > Monitor > Logs

### Erreurs communes

- **403 Forbidden :** Problème de permissions ou validation signature
- **500 Internal Error :** Erreur PHP, consultez les logs serveur
- **Timeout :** Webhook trop lent, optimisez le code

### Test des Webhooks

Utilisez l'outil de test Twilio :

1. Console Twilio > **Tools** > **Webhook Inspector**
2. Configurez l'URL de test
3. Envoyez des requêtes de test

## 🚀 Déploiement en Production

### Checklist de Sécurité

- [ ] Changer le mot de passe admin
- [ ] Activer la validation des signatures Twilio
- [ ] Utiliser HTTPS
- [ ] Limiter l'accès aux logs
- [ ] Configurer la rotation des logs

### Performance

- Utilisez un serveur web performant (Nginx + PHP-FPM)
- Activez la mise en cache si nécessaire
- Surveillez l'utilisation des ressources

## 📞 Support

- **Documentation Twilio :** https://www.twilio.com/docs/voice/webhooks
- **Console Twilio :** https://console.twilio.com/
- **Support Twilio :** https://support.twilio.com/

---

**Note :** Remplacez `votre-domaine.com` par votre vrai domaine dans toutes les configurations.
