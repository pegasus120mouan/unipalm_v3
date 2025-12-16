# Application d'Envoi de SMS

Une application web moderne en PHP pour envoyer des SMS via l'API Twilio.

## 🚀 Fonctionnalités

### Envoi de SMS
- ✅ Interface utilisateur moderne et responsive
- ✅ Validation en temps réel des formulaires
- ✅ Formatage automatique des numéros de téléphone
- ✅ Compteur de caractères pour les messages
- ✅ Gestion d'erreurs complète
- ✅ Notifications toast
- ✅ Sécurité renforcée
- ✅ Support international des numéros

### Réception et Webhooks
- ✅ Webhooks Twilio pour appels et SMS entrants
- ✅ Menu vocal interactif (IVR)
- ✅ Réponses automatiques SMS
- ✅ Enregistrement des messages vocaux
- ✅ Interface d'administration complète
- ✅ Logs en temps réel
- ✅ Statistiques d'utilisation

## 📋 Prérequis

- PHP 7.4 ou supérieur
- Composer
- Compte Twilio (gratuit pour les tests)
- Serveur web (Apache/Nginx) ou Laragon/XAMPP

## 🛠️ Installation

### 1. Cloner ou télécharger le projet

```bash
git clone <votre-repo>
cd envoiSMS
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration

1. Copiez le fichier `.env.example` vers `.env`:
```bash
cp .env.example .env
```

2. Éditez le fichier `.env` avec vos informations Twilio:
```env
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=your_twilio_phone_number_here
```

### 4. Obtenir les identifiants Twilio

1. Créez un compte sur [Twilio](https://www.twilio.com/)
2. Allez dans la Console Twilio
3. Récupérez votre `Account SID` et `Auth Token`
4. Achetez un numéro de téléphone Twilio ou utilisez un numéro de test

### 5. Démarrer l'application

Si vous utilisez Laragon, placez le projet dans `C:\laragon\www\envoiSMS` et accédez à `http://envoisms.test`

Ou démarrez le serveur PHP intégré:
```bash
php -S localhost:8000
```

## 📱 Utilisation

1. Ouvrez l'application dans votre navigateur
2. Saisissez le numéro de téléphone au format international (+33123456789)
3. Tapez votre message (max 1600 caractères)
4. Cliquez sur "Envoyer SMS"

## 🏗️ Structure du projet

```
envoiSMS/
├── src/
│   └── SmsService.php      # Service d'envoi SMS
├── assets/
│   └── js/
│       └── app.js          # JavaScript pour l'interface
├── logs/                   # Logs des webhooks et activités
│   ├── calls.log          # Historique des appels
│   ├── sms.log           # Historique des SMS
│   ├── webhook.log       # Logs des webhooks
│   └── recordings.log    # Enregistrements vocaux
├── vendor/                 # Dépendances Composer
├── index.php              # Page principale
├── admin.php              # Interface d'administration
├── webhook.php            # Gestionnaire de webhooks Twilio
├── config.php             # Configuration de l'application
├── setup.php              # Script de configuration
├── composer.json          # Dépendances PHP
├── .env.example           # Exemple de configuration
├── .gitignore            # Fichiers à ignorer
├── README.md             # Documentation
└── WEBHOOK_SETUP.md      # Guide de configuration webhooks
```

## 🔧 Configuration avancée

### Variables d'environnement

- `TWILIO_ACCOUNT_SID`: Votre SID de compte Twilio
- `TWILIO_AUTH_TOKEN`: Votre token d'authentification Twilio
- `TWILIO_PHONE_NUMBER`: Votre numéro Twilio (format +1234567890)
- `APP_NAME`: Nom de l'application (par défaut: "Envoi SMS")
- `APP_DEBUG`: Mode debug (true/false)

### Personnalisation

Vous pouvez personnaliser l'apparence en modifiant:
- Les classes Tailwind CSS dans `index.php`
- Les styles personnalisés dans la section `<style>`
- Le JavaScript dans `assets/js/app.js`

## 🛡️ Sécurité

L'application inclut plusieurs mesures de sécurité:
- Headers de sécurité HTTP
- Validation côté serveur et client
- Échappement des données utilisateur
- Protection contre les injections
- Limitation de la longueur des messages

## 🐛 Dépannage

### Erreur "Class not found"
```bash
composer dump-autoload
```

### Erreur Twilio "Authentication failed"
Vérifiez vos identifiants dans le fichier `.env`

### Erreur "Invalid phone number"
Assurez-vous d'utiliser le format international (+33123456789)

### Messages non reçus
- Vérifiez que le numéro destinataire est vérifié (compte Twilio gratuit)
- Vérifiez les logs Twilio dans votre console

## 📞 Support

Pour toute question ou problème:
1. Vérifiez la documentation Twilio
2. Consultez les logs d'erreur PHP
3. Vérifiez la console développeur du navigateur

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou proposer une pull request.

---

Développé avec ❤️ en PHP
