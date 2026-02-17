<?php
/**
 * Proxy d'images MinIO
 * Sert une image depuis MinIO via HTTPS pour éviter le mixed-content
 * Usage: proxy_image.php?url=<encoded_url>
 */

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

// Extraire le nom du fichier et déterminer le type MIME
$path = $parsed['path'] ?? '';
$filename = basename(explode('?', $path)[0]) ?: 'image.jpg';
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'bmp' => 'image/bmp',
];
$mime = $mimeTypes[$ext] ?? 'image/jpeg';

// Récupérer l'image
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$content = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $content === false) {
    http_response_code(502);
    echo 'Erreur lors de la récupération de l\'image: ' . ($error ?: "HTTP $httpCode");
    exit;
}

// Envoyer les headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($content));
header('Cache-Control: public, max-age=86400');

echo $content;
exit;
