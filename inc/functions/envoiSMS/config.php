<?php

// Chargement des variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    // Configuration par défaut si .env n'existe pas
    $_ENV['APP_NAME'] = 'Envoi SMS';
    $_ENV['APP_DEBUG'] = 'true';
    $_ENV['SMS_PROVIDER'] = 'hsms';
    $_ENV['HSMS_CLIENT_ID'] = '';
    $_ENV['HSMS_CLIENT_SECRET'] = '';
    $_ENV['HSMS_TOKEN'] = '';
    $_ENV['TWILIO_ACCOUNT_SID'] = '';
    $_ENV['TWILIO_AUTH_TOKEN'] = '';
    $_ENV['TWILIO_PHONE_NUMBER'] = '';
}

// Configuration de l'affichage des erreurs
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Paris');

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Vérification de la configuration SMS
function checkSmsConfig() {
    $provider = $_ENV['SMS_PROVIDER'] ?? 'twilio';
    $missing = [];
    
    if ($provider === 'hsms' || $provider === 'ovl') {
        $required = ['HSMS_CLIENT_ID', 'HSMS_CLIENT_SECRET', 'HSMS_TOKEN'];
    } else {
        $required = ['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN', 'TWILIO_PHONE_NUMBER'];
    }
    
    foreach ($required as $key) {
        if (empty($_ENV[$key])) {
            $missing[] = $key;
        }
    }
    
    return $missing;
}

// Fonction pour créer le service SMS approprié
function createSmsService() {
    $provider = $_ENV['SMS_PROVIDER'] ?? 'twilio';
    
    if ($provider === 'hsms' || $provider === 'ovl') {
        require_once __DIR__ . '/src/OvlSmsService.php';
        return new \App\OvlSmsService(
            $_ENV['HSMS_CLIENT_ID'],
            $_ENV['HSMS_CLIENT_SECRET'],
            $_ENV['HSMS_TOKEN']
        );
    } else {
        require_once __DIR__ . '/src/SmsService.php';
        return new \App\SmsService(
            $_ENV['TWILIO_ACCOUNT_SID'],
            $_ENV['TWILIO_AUTH_TOKEN'],
            $_ENV['TWILIO_PHONE_NUMBER']
        );
    }
}
