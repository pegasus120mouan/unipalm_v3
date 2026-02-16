<?php
/**
 * Proxy de streaming vidéo
 * Sert une vidéo depuis MinIO via HTTPS pour éviter le mixed-content
 * Usage: stream_video.php?url=<encoded_url>
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
$filename = basename(explode('?', $path)[0]) ?: 'video.mp4';
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'ogv' => 'video/ogg',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
];
$mime = $mimeTypes[$ext] ?? 'video/mp4';

// Récupérer les headers de la vidéo distante pour connaître la taille
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_NOBODY => true,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$headResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(502);
    echo 'Erreur lors de la récupération de la vidéo';
    exit;
}

// Gérer les requêtes Range pour le seeking vidéo
$start = 0;
$end = $contentLength - 1;
$length = $contentLength;

if (isset($_SERVER['HTTP_RANGE'])) {
    // Requête partielle (seeking)
    preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
    $start = intval($matches[1]);
    if (!empty($matches[2])) {
        $end = intval($matches[2]);
    }
    $length = $end - $start + 1;
    
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$contentLength");
} else {
    http_response_code(200);
}

// Headers pour le streaming
header('Content-Type: ' . $mime);
header('Content-Length: ' . $length);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Streamer la vidéo
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_RANGE => "$start-$end",
    CURLOPT_WRITEFUNCTION => function($ch, $data) {
        echo $data;
        flush();
        return strlen($data);
    },
]);
curl_exec($ch);
curl_close($ch);
exit;
