<?php
/**
 * API de mise à jour de la photo d'un utilisateur
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/update_photo_utilisateur.php
 * 
 * POST avec multipart/form-data: user_id (int), photo (file)
 * 
 * La photo sera stockée dans MinIO: bucket "planteurs", dossier "utilisateurs"
 * Le nom du fichier sera: utilisateurs/{user_id}_{timestamp}.{extension}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

require_once __DIR__ . '/../connexion.php';
$pdo = $conn;

// Charger la configuration MinIO depuis aws_env.php
$awsEnvCandidates = [
    __DIR__ . '/../../aws_env.php',
    __DIR__ . '/../../../aws_env.php',
    __DIR__ . '/../../../../aws_env.php',
];
foreach ($awsEnvCandidates as $awsEnvFile) {
    if (is_file($awsEnvFile)) {
        include_once $awsEnvFile;
        break;
    }
}

// Configuration MinIO - credentials directs
$minioEndpoint = 'http://51.178.49.141:9000';
$minioAccessKey = 'minioadmin';
$minioSecretKey = 'Azerty@@2020';
$minioBucket = 'planteurs';
$minioRegion = 'us-east-1';

function success($data, $message = 'Succès') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Fonctions pour signature AWS S3 / MinIO
function awsHash(string $str): string {
    return hash('sha256', $str);
}

function awsHmac(string $key, string $data, bool $raw = true): string {
    return hash_hmac('sha256', $data, $key, $raw);
}

function uploadToMinio($filePath, $objectKey, $mimeType, $endpoint, $bucket, $accessKey, $secretKey, $region) {
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        return ['success' => false, 'error' => 'Impossible de lire le fichier'];
    }
    
    $parsed = parse_url($endpoint);
    $scheme = $parsed['scheme'] ?? 'http';
    $host = $parsed['host'];
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $hostHeader = $host . $port;
    
    $service = 's3';
    $now = gmdate('Ymd\THis\Z');
    $date = gmdate('Ymd');
    
    $canonicalUri = '/' . $bucket . '/' . $objectKey;
    $payloadHash = hash('sha256', $fileContent);
    
    $headers = [
        'host' => $hostHeader,
        'x-amz-content-sha256' => $payloadHash,
        'x-amz-date' => $now,
        'content-type' => $mimeType,
        'content-length' => strlen($fileContent),
    ];
    ksort($headers);
    
    $canonicalHeaders = '';
    $signedHeadersList = [];
    foreach ($headers as $k => $v) {
        $canonicalHeaders .= strtolower($k) . ':' . trim($v) . "\n";
        $signedHeadersList[] = strtolower($k);
    }
    $signedHeaders = implode(';', $signedHeadersList);
    
    $canonicalRequest = "PUT\n" . $canonicalUri . "\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
    
    $credentialScope = $date . '/' . $region . '/' . $service . '/aws4_request';
    $stringToSign = "AWS4-HMAC-SHA256\n" . $now . "\n" . $credentialScope . "\n" . awsHash($canonicalRequest);
    
    $kDate = awsHmac('AWS4' . $secretKey, $date, true);
    $kRegion = awsHmac($kDate, $region, true);
    $kService = awsHmac($kRegion, $service, true);
    $kSigning = awsHmac($kService, 'aws4_request', true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $accessKey . '/' . $credentialScope . 
                     ', SignedHeaders=' . $signedHeaders . 
                     ', Signature=' . $signature;
    
    $url = $scheme . '://' . $hostHeader . $canonicalUri;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $hostHeader,
            'Content-Type: ' . $mimeType,
            'Content-Length: ' . strlen($fileContent),
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: ' . $now,
            'Authorization: ' . $authorization,
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'Erreur cURL: ' . $curlError];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'url' => $url];
    }
    
    return ['success' => false, 'error' => 'Erreur MinIO HTTP ' . $httpCode . ': ' . $response];
}

// Vérifier l'ID utilisateur
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if (!$userId) {
    error('ID utilisateur requis', 400);
}

// Vérifier si l'utilisateur existe
try {
    $stmtCheck = $pdo->prepare("SELECT id, avatar FROM utilisateurs WHERE id = ?");
    $stmtCheck->execute([$userId]);
    $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error('Utilisateur introuvable', 404);
    }
} catch (PDOException $e) {
    error('Erreur de base de données: ' . $e->getMessage(), 500);
}

// Vérifier le fichier photo
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier',
    ];
    $errorCode = isset($_FILES['photo']) ? $_FILES['photo']['error'] : UPLOAD_ERR_NO_FILE;
    $errorMsg = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Erreur inconnue lors du téléchargement';
    
    error($errorMsg, 400);
}

$file = $_FILES['photo'];

// Vérifier le type MIME
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    error('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.', 400);
}

// Vérifier la taille (max 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    error('Le fichier ne doit pas dépasser 5 Mo.', 400);
}

// Déterminer l'extension du fichier
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];
$extension = $extensions[$mimeType];

// Générer un nom de fichier unique
$timestamp = time();
$newFileName = $userId . '_' . $timestamp . '.' . $extension;
$objectKey = 'utilisateurs/' . $newFileName;

// Uploader vers MinIO
$uploadResult = uploadToMinio(
    $file['tmp_name'],
    $objectKey,
    $mimeType,
    $minioEndpoint,
    $minioBucket,
    $minioAccessKey,
    $minioSecretKey,
    $minioRegion
);

if (!$uploadResult['success']) {
    error('Erreur lors de l\'upload vers MinIO: ' . $uploadResult['error'], 500);
}

// Mettre à jour la base de données
try {
    // Stocker le chemin complet dans MinIO (utilisateurs/filename.ext)
    $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET avatar = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$objectKey, $userId]);
    
    // Récupérer l'utilisateur mis à jour
    $stmtGet = $pdo->prepare("
        SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.zone_id, 
               u.statut_compte, u.avatar, u.created_at, u.updated_at,
               z.nom_zone as zone_nom
        FROM utilisateurs u
        LEFT JOIN zones z ON u.zone_id = z.id
        WHERE u.id = ?
    ");
    $stmtGet->execute([$userId]);
    $updatedUser = $stmtGet->fetch(PDO::FETCH_ASSOC);
    
    // Construire l'URL de l'avatar (MinIO)
    $avatarUrl = $minioEndpoint . '/' . $minioBucket . '/' . $objectKey;
    
    success([
        'id' => (int)$updatedUser['id'],
        'nom' => $updatedUser['nom'],
        'prenoms' => $updatedUser['prenoms'],
        'nom_complet' => trim($updatedUser['nom'] . ' ' . $updatedUser['prenoms']),
        'avatar' => $objectKey,
        'avatar_url' => $avatarUrl,
        'updated_at' => $updatedUser['updated_at'],
    ], 'Photo mise à jour avec succès');
    
} catch (PDOException $e) {
    error('Erreur lors de la mise à jour en base de données: ' . $e->getMessage(), 500);
}
