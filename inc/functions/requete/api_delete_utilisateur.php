<?php
/**
 * API Proxy pour supprimer un utilisateur
 * Envoie la requête POST à l'API distante
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id = $data['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID utilisateur requis']);
    exit;
}

// Envoyer à l'API distante avec POST
$apiUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/delete_utilisateur.php';

$postData = json_encode(['id' => (int)$id]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData),
        'Accept: application/json'
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
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

// Debug: si la réponse n'est pas du JSON valide
if ($result === null && $response !== '') {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Réponse API invalide: ' . substr($response, 0, 200)]);
    exit;
}

if ($httpCode >= 400 || (isset($result['success']) && !$result['success'])) {
    http_response_code($httpCode >= 400 ? $httpCode : 400);
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? $result['message'] ?? 'Erreur lors de la suppression (HTTP ' . $httpCode . ')'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => $result['message'] ?? 'Utilisateur supprimé avec succès',
    'data' => $result['data'] ?? null
]);
exit;
