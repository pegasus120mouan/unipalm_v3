<?php
/**
 * Script de configuration pour l'application SMS
 */

echo "=== Configuration de l'application SMS ===\n\n";

// Vérification de PHP
$phpVersion = PHP_VERSION;
echo "Version PHP: $phpVersion\n";
if (version_compare($phpVersion, '7.4.0', '<')) {
    echo "❌ PHP 7.4 ou supérieur requis\n";
    exit(1);
} else {
    echo "✅ Version PHP compatible\n";
}

// Vérification de Composer
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Les dépendances ne sont pas installées\n";
    echo "Exécutez: composer install\n";
    exit(1);
} else {
    echo "✅ Dépendances installées\n";
}

// Vérification du fichier .env
if (!file_exists('.env')) {
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✅ Fichier .env créé à partir de .env.example\n";
    } else {
        echo "❌ Fichier .env.example manquant\n";
        exit(1);
    }
} else {
    echo "✅ Fichier .env existe\n";
}

// Chargement des variables d'environnement
require_once 'vendor/autoload.php';
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✅ Variables d'environnement chargées\n";
} else {
    echo "❌ Dotenv non disponible\n";
    exit(1);
}

// Vérification de la configuration SMS
$provider = $_ENV['SMS_PROVIDER'] ?? 'twilio';
echo "\n=== Vérification de la configuration SMS (Provider: " . ucfirst($provider) . ") ===\n";

$configComplete = true;

if ($provider === 'hsms' || $provider === 'ovl') {
    $smsConfig = [
        'HSMS_CLIENT_ID' => $_ENV['HSMS_CLIENT_ID'] ?? '',
        'HSMS_CLIENT_SECRET' => $_ENV['HSMS_CLIENT_SECRET'] ?? '',
        'HSMS_TOKEN' => $_ENV['HSMS_TOKEN'] ?? ''
    ];
    
    foreach ($smsConfig as $key => $value) {
        if (empty($value)) {
            echo "❌ $key non configuré\n";
            $configComplete = false;
        } else {
            $maskedValue = in_array($key, ['HSMS_CLIENT_SECRET', 'HSMS_TOKEN']) ? 
                str_repeat('*', strlen($value) - 6) . substr($value, -6) : $value;
            echo "✅ $key: $maskedValue\n";
        }
    }
    
    if (!$configComplete) {
        echo "\n⚠️  Configuration HSMS incomplète\n";
        echo "Éditez le fichier .env avec vos identifiants HSMS\n\n";
    }
    
} else {
    $twilioConfig = [
        'TWILIO_ACCOUNT_SID' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
        'TWILIO_AUTH_TOKEN' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
        'TWILIO_PHONE_NUMBER' => $_ENV['TWILIO_PHONE_NUMBER'] ?? ''
    ];
    
    foreach ($twilioConfig as $key => $value) {
        if (empty($value) || $value === 'your_account_sid_here' || $value === 'your_auth_token_here' || $value === 'your_twilio_phone_number_here') {
            echo "❌ $key non configuré\n";
            $configComplete = false;
        } else {
            $maskedValue = $key === 'TWILIO_AUTH_TOKEN' ? str_repeat('*', strlen($value) - 4) . substr($value, -4) : $value;
            echo "✅ $key: $maskedValue\n";
        }
    }
    
    if (!$configComplete) {
        echo "\n⚠️  Configuration Twilio incomplète\n";
        echo "Éditez le fichier .env avec vos identifiants Twilio\n";
        echo "Obtenez-les sur: https://console.twilio.com/\n\n";
    }
    
    // Test de connexion Twilio (si configuré)
    if ($configComplete) {
        echo "\n=== Test de connexion Twilio ===\n";
        try {
            $client = new Twilio\Rest\Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']);
            $account = $client->api->v2010->accounts($_ENV['TWILIO_ACCOUNT_SID'])->fetch();
            echo "✅ Connexion Twilio réussie\n";
            echo "Nom du compte: " . $account->friendlyName . "\n";
            echo "Statut: " . $account->status . "\n";
        } catch (Exception $e) {
            echo "❌ Erreur de connexion Twilio: " . $e->getMessage() . "\n";
        }
    }
}

// Vérification des permissions
echo "\n=== Vérification des permissions ===\n";

$directories = ['assets/js'];
foreach ($directories as $dir) {
    if (is_dir($dir) && is_readable($dir)) {
        echo "✅ $dir/ accessible\n";
    } else {
        echo "❌ $dir/ non accessible\n";
    }
}

// Vérification des extensions PHP
echo "\n=== Extensions PHP requises ===\n";
$requiredExtensions = ['curl', 'json', 'mbstring', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext\n";
    } else {
        echo "❌ $ext (requis)\n";
    }
}

// Résumé
echo "\n=== Résumé ===\n";
if ($configComplete) {
    echo "🎉 Configuration terminée avec succès !\n";
    echo "Vous pouvez maintenant utiliser l'application SMS\n";
    echo "Accédez à: http://localhost:8000 ou votre domaine local\n";
} else {
    echo "⚠️  Configuration incomplète\n";
    echo "Complétez la configuration Twilio dans le fichier .env\n";
}

echo "\n=== Aide ===\n";
echo "Documentation: README.md\n";
echo "Support Twilio: https://support.twilio.com/\n";
echo "Console Twilio: https://console.twilio.com/\n";
?>
