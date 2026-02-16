<?php
/**
 * Proxy de téléchargement vidéo
 * Force le téléchargement d'une vidéo depuis MinIO
 * Usage: download_video.php?url=<encoded_url>
 */

header('Access-Control-Allow-Origin: *');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (!$url) {
    http_response_code(400);
    echo 'URL manquante';
    exit;
}

// Valider que c'est bien une URL MinIO autorisée
$minioHost = '51.178.49.141:9000';
$parsed = parse_url($url);
if (!$parsed || !isset($parsed['host']) || strpos($parsed['host'] . ':' . ($parsed['port'] ?? '9000'), $minioHost) === false) {
    http_response_code(403);
    echo 'URL non autorisée';
    exit;
}

// Extraire le nom du fichier
$path = $parsed['path'] ?? '';
$filename = basename(explode('?', $path)[0]) ?: 'video.mp4';

// Déterminer le type MIME
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'ogv' => 'video/ogg',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// Récupérer la vidéo
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$content = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $content === false) {
    http_response_code(502);
    echo 'Erreur lors du téléchargement: ' . ($error ?: "HTTP $httpCode");
    exit;
}

// Envoyer les headers pour forcer le téléchargement
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo $content;
exit;
