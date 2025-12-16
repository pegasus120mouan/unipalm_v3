<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

echo "=== Debug HSMS API ===\n\n";

// Vos identifiants
$clientId = $_ENV['HSMS_CLIENT_ID'];
$clientSecret = $_ENV['HSMS_CLIENT_SECRET'];
$token = $_ENV['HSMS_TOKEN'];

echo "Client ID: $clientId\n";
echo "Client Secret: " . substr($clientSecret, 0, 10) . "...\n";
echo "Token: " . substr($token, 0, 10) . "...\n\n";

// Test 1: Vérification du solde avec cURL simple
echo "=== Test 1: Vérification du solde ===\n";

$url = 'https://hsms.ci/api/check-sms';
$data = [
    'clientid' => $clientId,
    'clientsecret' => $clientSecret
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data, // Multipart/form-data automatique
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true // Pour voir les détails
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}
curl_close($ch);

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Envoi SMS
echo "=== Test 2: Envoi SMS ===\n";

$url = 'https://hsms.ci/api/envoi-sms/';
$data = [
    'clientid' => $clientId,
    'clientsecret' => $clientSecret,
    'telephone' => '2250787703000',
    'message' => 'Test debug HSMS'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}
curl_close($ch);

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Test avec http_build_query (form-urlencoded)
echo "=== Test 3: Avec form-urlencoded ===\n";

$url = 'https://hsms.ci/api/check-sms';
$data = http_build_query([
    'clientid' => $clientId,
    'clientsecret' => $clientSecret
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "URL: $url\n";
echo "Data: $data\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}
curl_close($ch);

echo "\n=== Fin du debug ===\n";
?>
