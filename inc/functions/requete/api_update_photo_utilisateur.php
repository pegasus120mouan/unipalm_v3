<?php
/**
 * API pour mettre à jour la photo d'un utilisateur
 * Reçoit un fichier image et l'envoie à l'API distante
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
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier l'ID utilisateur
$userId = $_POST['user_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID utilisateur requis']);
    exit;
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
    $errorCode = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMsg = $errorMessages[$errorCode] ?? 'Erreur inconnue lors du téléchargement';
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

$file = $_FILES['photo'];

// Vérifier le type MIME
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.']);
    exit;
}

// Vérifier la taille (max 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le fichier ne doit pas dépasser 5 Mo.']);
    exit;
}

// Préparer l'envoi à l'API distante
$apiUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/update_photo_utilisateur.php';

// Créer un CURLFile pour l'envoi
$cfile = new CURLFile($file['tmp_name'], $mimeType, $file['name']);

$postData = [
    'user_id' => $userId,
    'photo' => $cfile
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à l\'API: ' . $error]);
    exit;
}

$result = json_decode($response, true);

if (!is_array($result)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Réponse invalide de l\'API distante']);
    exit;
}

http_response_code($httpCode);
echo json_encode($result);
