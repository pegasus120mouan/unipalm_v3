<?php
/**
 * Récupère tous les planteurs avec leurs données complètes
 * GET ?action=planteurs
 * GET ?action=planteurs&id={id} - Un seul planteur
 * GET ?action=planteurs&collecteur_id={id} - Planteurs d'un collecteur
 * GET ?action=planteurs&since={timestamp} - Planteurs depuis une date
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$remoteApiUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/planteurs.php';

$__awsEnvCandidates = [
    __DIR__ . '/../../../../aws_env.php',
    __DIR__ . '/../../../../config/aws_env.php',
    __DIR__ . '/../../../aws_env.php',
    __DIR__ . '/../../../config/aws_env.php',
    __DIR__ . '/../../config/aws_env.php',
];
foreach ($__awsEnvCandidates as $__awsEnvFile) {
    if (is_file($__awsEnvFile)) {
        include_once $__awsEnvFile;
        break;
    }
}

function envValue(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false && $v !== null && $v !== '') {
        return (string)$v;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }
    return $default;
}

function awsEncodePath(string $path): string {
    $parts = explode('/', $path);
    $parts = array_map('rawurlencode', $parts);
    return implode('/', $parts);
}

function awsHash(string $str): string {
    return hash('sha256', $str);
}

function awsHmac(string $key, string $data, bool $raw = true): string {
    return hash_hmac('sha256', $data, $key, $raw);
}

function awsGetPresignedUrl(string $bucket, string $objectKey, int $expires = 3600): ?string {
    $accessKey = envValue('AWS_ACCESS_KEY_ID');
    $secretKey = envValue('AWS_SECRET_ACCESS_KEY');
    $region = envValue('AWS_DEFAULT_REGION', 'us-east-1');
    $endpoint = envValue('AWS_ENDPOINT', 'http://51.178.49.141:9000');

    if ($accessKey === '' || $secretKey === '' || $bucket === '' || $objectKey === '') {
        return null;
    }

    $parsed = parse_url($endpoint);
    if (!is_array($parsed) || empty($parsed['host'])) {
        return null;
    }

    $scheme = $parsed['scheme'] ?? 'http';
    $host = $parsed['host'];
    $port = $parsed['port'] ?? null;
    $hostHeader = $port ? ($host . ':' . $port) : $host;

    $service = 's3';
    $now = gmdate('Ymd\THis\Z');
    $date = gmdate('Ymd');
    $credentialScope = $date . '/' . $region . '/' . $service . '/' . 'aws4_request';
    $credential = $accessKey . '/' . $credentialScope;

    $canonicalUri = '/' . $bucket . '/' . awsEncodePath(ltrim($objectKey, '/'));
    $canonicalHeaders = 'host:' . strtolower($hostHeader) . "\n";
    $signedHeaders = 'host';

    $query = [
        'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
        'X-Amz-Credential' => $credential,
        'X-Amz-Date' => $now,
        'X-Amz-Expires' => (string)max(1, min($expires, 604800)),
        'X-Amz-SignedHeaders' => $signedHeaders,
    ];
    ksort($query);

    $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $payloadHash = 'UNSIGNED-PAYLOAD';
    $canonicalRequest = "GET\n" . $canonicalUri . "\n" . $canonicalQuery . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;

    $stringToSign = "AWS4-HMAC-SHA256\n" . $now . "\n" . $credentialScope . "\n" . awsHash($canonicalRequest);

    $kDate = awsHmac('AWS4' . $secretKey, $date, true);
    $kRegion = awsHmac($kDate, $region, true);
    $kService = awsHmac($kRegion, $service, true);
    $kSigning = awsHmac($kService, 'aws4_request', true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $canonicalQuery .= '&X-Amz-Signature=' . $signature;

    return $scheme . '://' . $hostHeader . $canonicalUri . '?' . $canonicalQuery;
}

function extractPhotoKey(array $p): string {
    $candidates = [
        'photo',
        'photo_planteur',
        'image',
        'image_planteur',
        'avatar',
        'profil_photo',
        'photo_key',
        'image_key',
    ];
    foreach ($candidates as $k) {
        if (!empty($p[$k]) && is_string($p[$k])) {
            return trim($p[$k]);
        }
    }
    return '';
}

function extractObjectKeyFromUrl(string $url, string $bucket): string {
    $u = trim($url);
    if ($u === '' || !preg_match('/^https?:\/\//i', $u)) {
        return '';
    }
    $parts = parse_url($u);
    if (!is_array($parts) || empty($parts['path'])) {
        return '';
    }
    $path = $parts['path'];
    $prefix = '/' . trim($bucket, '/') . '/';
    if (strpos($path, $prefix) !== 0) {
        return '';
    }
    return ltrim(substr($path, strlen($prefix)), '/');
}

function urlHasSignature(string $url): bool {
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['query'])) {
        return false;
    }
    parse_str($parts['query'], $q);
    return isset($q['X-Amz-Signature']);
}

function enrichPlanteur(array $p): array {
    $bucket = envValue('AWS_BUCKET', 'planteurs');

    $photoKey = '';
    if (!empty($p['photo_url']) && is_string($p['photo_url']) && preg_match('/^https?:\/\//i', $p['photo_url']) && !urlHasSignature($p['photo_url'])) {
        $photoKey = extractObjectKeyFromUrl($p['photo_url'], $bucket);
    }
    if ($photoKey === '') {
        $photoKey = extractPhotoKey($p);
    }
    if ($photoKey !== '' && (empty($p['photo_url']) || !is_string($p['photo_url']) || !preg_match('/^https?:\/\//i', $p['photo_url']) || !urlHasSignature((string)$p['photo_url']))) {
        $presigned = awsGetPresignedUrl($bucket, $photoKey, 3600);
        if ($presigned) {
            $p['photo_url'] = $presigned;
        }
    }

    if (isset($p['exploitation']) && is_array($p['exploitation'])) {
        $videoKey = '';
        if (!empty($p['exploitation']['video_url']) && is_string($p['exploitation']['video_url']) && preg_match('/^https?:\/\//i', $p['exploitation']['video_url']) && !urlHasSignature($p['exploitation']['video_url'])) {
            $videoKey = extractObjectKeyFromUrl($p['exploitation']['video_url'], $bucket);
        }
        if ($videoKey === '' && !empty($p['exploitation']['video']) && is_string($p['exploitation']['video'])) {
            $videoKey = trim($p['exploitation']['video']);
        }
        if ($videoKey !== '' && (empty($p['exploitation']['video_url']) || !is_string($p['exploitation']['video_url']) || !preg_match('/^https?:\/\//i', $p['exploitation']['video_url']) || !urlHasSignature((string)$p['exploitation']['video_url']))) {
            $presignedVideo = awsGetPresignedUrl($bucket, $videoKey, 3600);
            if ($presignedVideo) {
                $p['exploitation']['video_url'] = $presignedVideo;
            }
        }
    }

    return $p;
}

function proxyRemote(string $remoteApiUrl, array $queryParams): void {
    $url = $remoteApiUrl;
    if (!empty($queryParams)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        error('Erreur lors de la récupération des planteurs (API distante).', 502);
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        error('Réponse invalide de l\'API distante.', 502);
    }

    if (isset($json['success']) && $json['success'] === true) {
        if (isset($json['data']['planteurs']) && is_array($json['data']['planteurs'])) {
            foreach ($json['data']['planteurs'] as $i => $p) {
                if (!is_array($p)) {
                    continue;
                }
                $json['data']['planteurs'][$i] = enrichPlanteur($p);
            }
        } elseif (isset($json['data']) && is_array($json['data']) && isset($json['data']['id'])) {
            $json['data'] = enrichPlanteur($json['data']);
        }
    }

    echo json_encode($json);
    exit;
}

function success($data, $message = 'Succès') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function proxyPost(string $remoteApiUrl, array $data): void {
    $jsonData = json_encode($data);
    
    $ch = curl_init($remoteApiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($jsonData)
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($raw === false || $curlError) {
        error('Erreur lors de la mise à jour (API distante): ' . $curlError, 502);
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        error('Réponse invalide de l\'API distante: ' . substr($raw, 0, 200), 502);
    }

    http_response_code($httpCode);
    echo json_encode($json);
    exit;
}

// Gestion des requêtes POST (mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!is_array($data)) {
        error('Données JSON invalides.', 400);
    }
    
    $action = $data['action'] ?? '';
    
    if ($action === 'update_planteur') {
        $updateUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/update_planteur.php';
        proxyPost($updateUrl, $data);
    } else {
        error('Action non supportée: ' . $action, 400);
    }
}

// Gestion des requêtes GET
proxyRemote($remoteApiUrl, $_GET);
