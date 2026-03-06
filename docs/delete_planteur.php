<?php
/**
 * API de suppression d'un planteur
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/delete_planteur.php
 * 
 * Méthode: POST
 * Content-Type: application/json
 * 
 * Body: { "id": 123 }
 * 
 * La suppression est en cascade grâce aux contraintes FK:
 * - exploitations (ON DELETE CASCADE)
 * - cultures (via exploitation_id, ON DELETE CASCADE)
 * - parcelles (via exploitation_id, ON DELETE CASCADE)
 * - informations_complementaires (via exploitation_id, ON DELETE CASCADE)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST ou DELETE.']);
    exit;
}

require_once __DIR__ . '/../connexion.php';
$pdo = $conn;

function success($data, $message = 'Succès') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    error('Données JSON invalides', 400);
}

// Vérifier l'ID du planteur
$id = isset($data['id']) ? intval($data['id']) : 0;
if ($id <= 0) {
    error('ID du planteur requis', 400);
}

try {
    // Vérifier que le planteur existe
    $stmt = $pdo->prepare("SELECT id, nom_prenoms, numero_fiche FROM exploitants WHERE id = ?");
    $stmt->execute([$id]);
    $planteur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$planteur) {
        error('Planteur introuvable', 404);
    }

    $pdo->beginTransaction();

    // Supprimer le planteur (les tables liées seront supprimées en cascade)
    $stmt = $pdo->prepare("DELETE FROM exploitants WHERE id = ?");
    $stmt->execute([$id]);

    $rowsDeleted = $stmt->rowCount();

    $pdo->commit();

    success([
        'id' => $id,
        'nom_prenoms' => $planteur['nom_prenoms'],
        'numero_fiche' => $planteur['numero_fiche'],
        'deleted' => $rowsDeleted > 0
    ], 'Planteur supprimé avec succès');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error('Erreur lors de la suppression: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error('Erreur: ' . $e->getMessage(), 500);
}
