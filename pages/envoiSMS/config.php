<?php
/**
 * Configuration pour l'API SMS UNIPALM
 * Identifiants fournis pour l'envoi de SMS
 */

// Identifiants API SMS
define('SMS_CLIENT_ID', 'UNIPALM_HOvuHXr');
define('SMS_CLIENT_SECRET', 'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA');
define('SMS_TOKEN', '0eebac3b6594eb3c37b675f8ab0299629f5d96f9');

// URL de base de l'API SMS (à adapter selon le fournisseur)
define('SMS_API_BASE_URL', 'https://api.sms-provider.com/v1');

// Configuration des messages
define('SMS_SENDER_NAME', 'UNIPALM');
define('SMS_MAX_RETRIES', 3);
define('SMS_TIMEOUT', 30);

// Log des SMS
define('SMS_LOG_FILE', __DIR__ . '/logs/sms_log.txt');
define('SMS_ERROR_LOG_FILE', __DIR__ . '/logs/sms_errors.txt');

// Créer le dossier de logs s'il n'existe pas
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>
