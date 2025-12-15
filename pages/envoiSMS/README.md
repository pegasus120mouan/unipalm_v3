# 📱 Module SMS UNIPALM

Ce module permet l'envoi automatique de SMS aux nouveaux agents lors de leur création, contenant leur code PIN et numéro d'agent.

## 🚀 Fonctionnalités

- **Envoi automatique de SMS** lors de la création d'un nouvel agent
- **Gestion des erreurs** avec système de retry automatique
- **Logging complet** des envois et erreurs
- **Interface de test** pour vérifier le fonctionnement
- **Formatage automatique** des numéros de téléphone

## 📁 Structure des fichiers

```
envoiSMS/
├── config.php          # Configuration API et constantes
├── sms_sender.php       # Classe principale d'envoi SMS
├── agent_sms.php        # Fonctions utilitaires pour agents
├── test_sms.php         # Interface de test
├── README.md           # Cette documentation
└── logs/               # Dossier des logs (créé automatiquement)
    ├── sms_log.txt     # Logs des envois réussis
    └── sms_errors.txt  # Logs des erreurs
```

## ⚙️ Configuration

### Identifiants API
Les identifiants sont configurés dans `config.php` :

```php
define('SMS_CLIENT_ID', 'UNIPALM_HOvuHXr');
define('SMS_CLIENT_SECRET', 'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA');
define('SMS_TOKEN', '0eebac3b6594eb3c37b675f8ab0299629f5d96f9');
```

### Paramètres personnalisables
- `SMS_SENDER_NAME` : Nom de l'expéditeur (UNIPALM)
- `SMS_MAX_RETRIES` : Nombre de tentatives en cas d'échec (3)
- `SMS_TIMEOUT` : Timeout des requêtes API (30s)

## 🔧 Intégration

### Dans le processus de création d'agent

Le module est automatiquement intégré dans `pages/traitement_agents.php`. Lors de la création d'un agent :

1. L'agent est créé en base de données
2. Un SMS est automatiquement envoyé avec :
   - Numéro d'agent généré
   - Code PIN à 6 chiffres
   - Message de bienvenue personnalisé

### Message type envoyé

```
Bienvenue chez UNIPALM !

Bonjour Jean KOUAME,

Votre compte agent a été créé avec succès.

Votre numéro d'agent : AGT-25-KOU-JK01
Votre code PIN : 123456

Gardez ces informations confidentielles.

Cordialement,
Équipe UNIPALM
```

## 🧪 Test du module

### Interface web de test
Accédez à : `http://votre-domaine/unipalm/envoiSMS/test_sms.php`

Cette interface permet de :
- Tester l'envoi de SMS sans créer d'agent
- Visualiser les logs en temps réel
- Vérifier la configuration

### Test programmatique

```php
require_once 'envoiSMS/agent_sms.php';

$resultat = envoyerSMSNouvelAgent(
    '+22507000000',    // Numéro de téléphone
    'KOUAME',          // Nom
    'Jean',            // Prénom
    '123456',          // Code PIN
    'AGT-25-KOU-JK01'  // Numéro d'agent
);

if ($resultat['success']) {
    echo "SMS envoyé avec succès !";
} else {
    echo "Erreur : " . $resultat['message'];
}
```

## 📊 Monitoring et logs

### Logs de succès (`logs/sms_log.txt`)
```json
{"timestamp":"2025-01-01 12:00:00","numero":"+22507000000","status":"SUCCESS","message_length":150,"details":{"status":"sent","message_id":"SMS_1234567890_5678"}}
```

### Logs d'erreurs (`logs/sms_errors.txt`)
```json
{"timestamp":"2025-01-01 12:00:00","numero":"+22507000000","error":"Tentative 1/3 échouée: Timeout","message_length":150}
```

## 🔄 Gestion des erreurs

Le système inclut :
- **Retry automatique** : 3 tentatives par défaut
- **Délai entre tentatives** : 2 secondes
- **Logging détaillé** de toutes les erreurs
- **Fallback gracieux** : l'agent est créé même si le SMS échoue

## 🌐 Passage en production

### Activation de l'API réelle

Actuellement en mode simulation. Pour activer l'envoi réel :

1. **Obtenir l'URL de l'API SMS** auprès du fournisseur
2. **Modifier `config.php`** :
   ```php
   define('SMS_API_BASE_URL', 'https://api.votre-fournisseur-sms.com/v1');
   ```

3. **Modifier `sms_sender.php`** :
   ```php
   // Remplacer dans la méthode envoyerSMS()
   $resultat = $this->appelAPI($data);  // Au lieu de simulerEnvoiSMS()
   ```

### Sécurité

- ✅ Identifiants stockés dans des constantes
- ✅ Validation des numéros de téléphone
- ✅ Logging sécurisé sans données sensibles
- ✅ Gestion des timeouts et erreurs réseau

## 🛠️ Maintenance

### Nettoyage des logs
```bash
# Archiver les anciens logs (recommandé mensuellement)
cd envoiSMS/logs/
mv sms_log.txt sms_log_$(date +%Y%m).txt
mv sms_errors.txt sms_errors_$(date +%Y%m).txt
```

### Surveillance
- Vérifier régulièrement les logs d'erreurs
- Monitorer le taux de succès des envois
- Tester périodiquement avec l'interface de test

## 📞 Support

En cas de problème :
1. Vérifier les logs dans `envoiSMS/logs/`
2. Tester avec l'interface `test_sms.php`
3. Vérifier la configuration dans `config.php`
4. Contacter l'équipe technique avec les logs d'erreur

---

*Module développé pour UNIPALM - Version 1.0*
