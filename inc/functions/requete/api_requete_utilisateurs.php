<?php
/**
 * Proxy API pour les utilisateurs
 * Récupère les données depuis l'API distante et génère les URLs presignées pour les avatars
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../../aws_env.php';

$apiBaseUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/utilisateurs.php';

$action = $_GET['action'] ?? 'utilisateurs';
$id = $_GET['id'] ?? null;
$role = $_GET['role'] ?? null;
$statut = $_GET['statut'] ?? null;

$params = [];
if ($id) $params['id'] = $id;
if ($role) $params['role'] = $role;
if ($statut !== null && $statut !== '') $params['statut'] = $statut;

$url = $apiBaseUrl;
if (!empty($params)) {
    $url .= '?' . http_build_query($params);
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur API: ' . ($error ?: "HTTP $httpCode")
    ]);
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['success'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Réponse API invalide'
    ]);
    exit;
}

// Fonction pour générer une URL presignée AWS SigV4
function awsGetPresignedUrl(string $bucket, string $objectKey, int $expires = 3600): ?string {
    $accessKey = getenv('AWS_ACCESS_KEY_ID');
    $secretKey = getenv('AWS_SECRET_ACCESS_KEY');
    $region = getenv('AWS_DEFAULT_REGION') ?: 'us-east-1';
    $endpoint = getenv('AWS_ENDPOINT') ?: 'http://51.178.49.141:9000';

    if (!$accessKey || !$secretKey) return null;

    $parsed = parse_url($endpoint);
    $scheme = $parsed['scheme'] ?? 'http';
    $host = $parsed['host'] ?? '51.178.49.141';
    $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 9000);
    $hostHeader = ($port == 80 || $port == 443) ? $host : "$host:$port";

    $service = 's3';
    $now = time();
    $amzDate = gmdate('Ymd\THis\Z', $now);
    $dateStamp = gmdate('Ymd', $now);
    $credentialScope = "$dateStamp/$region/$service/aws4_request";

    $canonicalUri = '/' . $bucket . '/' . rawurlencode($objectKey);
    $canonicalUri = str_replace('%2F', '/', $canonicalUri);

    $queryParams = [
        'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
        'X-Amz-Credential' => $accessKey . '/' . $credentialScope,
        'X-Amz-Date' => $amzDate,
        'X-Amz-Expires' => $expires,
        'X-Amz-SignedHeaders' => 'host',
    ];
    ksort($queryParams);
    $canonicalQuery = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

    $canonicalHeaders = "host:$hostHeader\n";
    $signedHeaders = 'host';
    $payloadHash = 'UNSIGNED-PAYLOAD';

    $canonicalRequest = "GET\n$canonicalUri\n$canonicalQuery\n$canonicalHeaders\n$signedHeaders\n$payloadHash";
    $stringToSign = "AWS4-HMAC-SHA256\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $canonicalQuery .= '&X-Amz-Signature=' . $signature;

    return $scheme . '://' . $hostHeader . $canonicalUri . '?' . $canonicalQuery;
}

// Enrichir les données avec les URLs presignées pour les avatars
if (isset($data['data']['utilisateurs']) && is_array($data['data']['utilisateurs'])) {
    foreach ($data['data']['utilisateurs'] as &$user) {
        if (!empty($user['avatar']) && $user['avatar'] !== 'default.jpg') {
            $presignedUrl = awsGetPresignedUrl('planteurs', $user['avatar']);
            if ($presignedUrl) {
                $user['avatar_url'] = $presignedUrl;
            }
        }
    }
    unset($user);
} elseif (isset($data['data']['avatar']) && !empty($data['data']['avatar']) && $data['data']['avatar'] !== 'default.jpg') {
    $presignedUrl = awsGetPresignedUrl('planteurs', $data['data']['avatar']);
    if ($presignedUrl) {
        $data['data']['avatar_url'] = $presignedUrl;
    }
}

echo json_encode($data);
exit;
