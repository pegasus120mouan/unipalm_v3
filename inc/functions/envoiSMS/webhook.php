<?php
/**
 * Webhook pour recevoir les callbacks Twilio
 * URL à configurer dans Twilio Console: https://votre-domaine.com/webhook.php
 */

require_once 'vendor/autoload.php';
require_once 'config.php';

use Twilio\TwiML\VoiceResponse;
use Twilio\TwiML\MessagingResponse;

// Headers pour Twilio
header('Content-Type: text/xml');

// Log des requêtes pour debug
function logWebhook($data) {
    $logFile = __DIR__ . '/logs/webhook.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] " . json_encode($data) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Récupération des données Twilio
$twilioData = $_POST;
logWebhook($twilioData);

// Déterminer le type de webhook
$webhookType = '';
if (isset($twilioData['CallSid'])) {
    $webhookType = 'voice';
} elseif (isset($twilioData['MessageSid'])) {
    $webhookType = 'sms';
}

switch ($webhookType) {
    case 'voice':
        handleVoiceWebhook($twilioData);
        break;
        
    case 'sms':
        handleSmsWebhook($twilioData);
        break;
        
    default:
        // Webhook générique
        $response = new VoiceResponse();
        $response->say('Bonjour, merci pour votre appel.', ['language' => 'fr-FR']);
        echo $response;
        break;
}

/**
 * Gestion des webhooks d'appels vocaux
 */
function handleVoiceWebhook($data) {
    $response = new VoiceResponse();
    
    // Récupération des informations de l'appel
    $from = $data['From'] ?? 'Numéro inconnu';
    $to = $data['To'] ?? '';
    $callSid = $data['CallSid'] ?? '';
    
    // Menu vocal interactif
    $gather = $response->gather([
        'numDigits' => 1,
        'action' => '/webhook.php?action=menu',
        'method' => 'POST'
    ]);
    
    $gather->say(
        'Bonjour et bienvenue. Appuyez sur 1 pour les informations, 2 pour laisser un message, ou 0 pour parler à un opérateur.',
        ['language' => 'fr-FR']
    );
    
    // Si pas de réponse, répéter
    $response->say(
        'Nous n\'avons pas reçu votre choix. Au revoir.',
        ['language' => 'fr-FR']
    );
    
    // Enregistrer l'appel dans les logs
    saveCallLog($callSid, $from, $to, 'incoming');
    
    echo $response;
}

/**
 * Gestion des webhooks SMS
 */
function handleSmsWebhook($data) {
    $response = new MessagingResponse();
    
    // Récupération des informations du SMS
    $from = $data['From'] ?? '';
    $to = $data['To'] ?? '';
    $body = $data['Body'] ?? '';
    $messageSid = $data['MessageSid'] ?? '';
    
    // Réponse automatique basée sur le contenu
    $autoReply = generateAutoReply($body);
    
    if ($autoReply) {
        $message = $response->message($autoReply);
    }
    
    // Enregistrer le SMS reçu
    saveSmsLog($messageSid, $from, $to, $body, 'received');
    
    echo $response;
}

/**
 * Génération de réponses automatiques SMS
 */
function generateAutoReply($messageBody) {
    $body = strtolower(trim($messageBody));
    
    $responses = [
        'info' => 'Merci pour votre message. Nos horaires sont de 9h à 18h du lundi au vendredi.',
        'horaires' => 'Nous sommes ouverts de 9h à 18h du lundi au vendredi.',
        'contact' => 'Vous pouvez nous joindre au 01 23 45 67 89 ou par email à contact@exemple.com',
        'aide' => 'Pour obtenir de l\'aide, tapez INFO, HORAIRES ou CONTACT.',
        'stop' => 'Vous avez été désabonné de nos notifications SMS.',
        'start' => 'Vous êtes maintenant abonné à nos notifications SMS.'
    ];
    
    foreach ($responses as $keyword => $reply) {
        if (strpos($body, $keyword) !== false) {
            return $reply;
        }
    }
    
    // Réponse par défaut
    return 'Merci pour votre message. Nous vous répondrons dans les plus brefs délais.';
}

/**
 * Gestion du menu vocal
 */
if (isset($_GET['action']) && $_GET['action'] === 'menu') {
    $response = new VoiceResponse();
    $digits = $_POST['Digits'] ?? '';
    
    switch ($digits) {
        case '1':
            $response->say(
                'Nos horaires sont de 9 heures à 18 heures du lundi au vendredi. Notre adresse est 123 rue de la Paix, Paris.',
                ['language' => 'fr-FR']
            );
            break;
            
        case '2':
            $response->say(
                'Veuillez laisser votre message après le bip sonore.',
                ['language' => 'fr-FR']
            );
            $response->record([
                'maxLength' => 60,
                'action' => '/webhook.php?action=recording'
            ]);
            break;
            
        case '0':
            $response->say(
                'Transfert vers un opérateur en cours.',
                ['language' => 'fr-FR']
            );
            // Ici vous pouvez ajouter un numéro de transfert
            // $response->dial('+33123456789');
            break;
            
        default:
            $response->say(
                'Choix invalide. Au revoir.',
                ['language' => 'fr-FR']
            );
            break;
    }
    
    echo $response;
    exit;
}

/**
 * Gestion des enregistrements vocaux
 */
if (isset($_GET['action']) && $_GET['action'] === 'recording') {
    $response = new VoiceResponse();
    $recordingUrl = $_POST['RecordingUrl'] ?? '';
    
    $response->say(
        'Merci pour votre message. Nous vous recontacterons rapidement. Au revoir.',
        ['language' => 'fr-FR']
    );
    
    // Sauvegarder l'URL de l'enregistrement
    if ($recordingUrl) {
        saveRecording($_POST['CallSid'], $recordingUrl);
    }
    
    echo $response;
    exit;
}

/**
 * Sauvegarde des logs d'appels
 */
function saveCallLog($callSid, $from, $to, $direction) {
    $logFile = __DIR__ . '/logs/calls.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] CallSID: $callSid | From: $from | To: $to | Direction: $direction\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Sauvegarde des logs SMS
 */
function saveSmsLog($messageSid, $from, $to, $body, $direction) {
    $logFile = __DIR__ . '/logs/sms.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] MessageSID: $messageSid | From: $from | To: $to | Direction: $direction | Body: $body\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Sauvegarde des enregistrements
 */
function saveRecording($callSid, $recordingUrl) {
    $logFile = __DIR__ . '/logs/recordings.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] CallSID: $callSid | Recording: $recordingUrl\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
