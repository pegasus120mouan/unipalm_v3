<?php
/**
 * Script de diagnostic SMS pour production
 * Permet de tester l'envoi SMS et identifier les problèmes
 */

// Inclure les dépendances nécessaires
$sms_service_path = __DIR__ . '/../inc/functions/envoiSMS/src/OvlSmsService.php';
require_once $sms_service_path;

// Configuration de test
$test_phone = '0769292989'; // Numéro de test
$test_message = 'Test SMS UNIPALM - ' . date('Y-m-d H:i:s');

// Credentials HSMS
$client_id = 'UNIPALM_HOvuHXr';
$client_secret = 'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA';
$token = '0eebac3b6594eb3c37b675f8ab0299629f5d96f9';

echo "<h1>Diagnostic SMS Production - UNIPALM</h1>\n";
echo "<p>Date/Heure: " . date('Y-m-d H:i:s') . "</p>\n";

// Test 1: Vérifier la classe OvlSmsService
echo "<h2>1. Test de la classe OvlSmsService</h2>\n";
try {
    $smsService = new \App\OvlSmsService($client_id, $client_secret, $token);
    echo "✅ Classe OvlSmsService chargée avec succès<br>\n";
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement de la classe: " . $e->getMessage() . "<br>\n";
    exit;
}

// Test 2: Vérifier la connectivité réseau
echo "<h2>2. Test de connectivité réseau</h2>\n";
$api_url = 'https://hsms.ci';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "❌ Erreur de connectivité: " . $curl_error . "<br>\n";
} else {
    echo "✅ Connectivité OK - Code HTTP: " . $http_code . "<br>\n";
}

// Test 3: Vérifier le solde SMS
echo "<h2>3. Test du solde SMS</h2>\n";
try {
    $balance_result = $smsService->checkSmsBalance();
    if ($balance_result['success']) {
        echo "✅ Solde SMS: " . $balance_result['balance'] . "<br>\n";
        echo "Application: " . $balance_result['application'] . "<br>\n";
    } else {
        echo "❌ Erreur lors de la vérification du solde: " . $balance_result['error'] . "<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Exception lors de la vérification du solde: " . $e->getMessage() . "<br>\n";
}

// Test 4: Test d'envoi SMS
echo "<h2>4. Test d'envoi SMS</h2>\n";
echo "Numéro de test: " . $test_phone . "<br>\n";
echo "Message: " . $test_message . "<br>\n";

try {
    $sms_result = $smsService->sendSms($test_phone, $test_message);
    
    echo "<h3>Résultat de l'envoi:</h3>\n";
    echo "<pre>" . print_r($sms_result, true) . "</pre>\n";
    
    if ($sms_result['success']) {
        echo "✅ SMS envoyé avec succès!<br>\n";
        echo "ID du message: " . ($sms_result['message_sid'] ?? 'N/A') . "<br>\n";
    } else {
        echo "❌ Échec de l'envoi SMS<br>\n";
        echo "Erreur: " . ($sms_result['error'] ?? 'Erreur inconnue') . "<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Exception lors de l'envoi: " . $e->getMessage() . "<br>\n";
}

// Test 5: Vérifier les variables d'environnement
echo "<h2>5. Variables d'environnement</h2>\n";
echo "APP_DEBUG: " . ($_ENV['APP_DEBUG'] ?? 'non défini') . "<br>\n";
echo "SMS_PROVIDER: " . ($_ENV['SMS_PROVIDER'] ?? 'non défini') . "<br>\n";
echo "HSMS_CLIENT_ID: " . (empty($_ENV['HSMS_CLIENT_ID']) ? 'vide' : 'défini') . "<br>\n";
echo "HSMS_CLIENT_SECRET: " . (empty($_ENV['HSMS_CLIENT_SECRET']) ? 'vide' : 'défini') . "<br>\n";
echo "HSMS_TOKEN: " . (empty($_ENV['HSMS_TOKEN']) ? 'vide' : 'défini') . "<br>\n";

// Test 6: Vérifier les logs d'erreur PHP
echo "<h2>6. Logs d'erreur PHP</h2>\n";
$error_log = ini_get('error_log');
echo "Fichier de log d'erreur: " . ($error_log ?: 'non configuré') . "<br>\n";

if ($error_log && file_exists($error_log)) {
    $recent_errors = tail($error_log, 10);
    echo "<h3>Dernières erreurs PHP:</h3>\n";
    echo "<pre>" . htmlspecialchars($recent_errors) . "</pre>\n";
} else {
    echo "Aucun fichier de log d'erreur accessible<br>\n";
}

// Test 7: Informations serveur
echo "<h2>7. Informations serveur</h2>\n";
echo "PHP Version: " . phpversion() . "<br>\n";
echo "cURL activé: " . (extension_loaded('curl') ? 'Oui' : 'Non') . "<br>\n";
echo "OpenSSL activé: " . (extension_loaded('openssl') ? 'Oui' : 'Non') . "<br>\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Activé' : 'Désactivé') . "<br>\n";

// Fonction pour lire les dernières lignes d'un fichier
function tail($filename, $lines = 10) {
    if (!file_exists($filename)) {
        return "Fichier non trouvé";
    }
    
    $file = file($filename);
    if ($file === false) {
        return "Impossible de lire le fichier";
    }
    
    return implode('', array_slice($file, -$lines));
}

echo "<hr>\n";
echo "<p><strong>Diagnostic terminé à " . date('Y-m-d H:i:s') . "</strong></p>\n";
?>
