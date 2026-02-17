<?php
/**
 * API de mise à jour d'un planteur
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/update_planteur.php
 * 
 * Méthode: POST
 * Content-Type: application/json
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST.']);
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
    // Vérifier que l'exploitant existe et récupérer l'exploitation_id
    $stmt = $pdo->prepare("
        SELECT e.id, ex.id as exploitation_id 
        FROM exploitants e 
        LEFT JOIN exploitations ex ON ex.exploitant_id = e.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $exploitant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exploitant) {
        error('Planteur introuvable', 404);
    }

    $pdo->beginTransaction();

    // Mise à jour de l'exploitant
    $updateFields = [];
    $updateValues = [];

    $allowedFields = [
        'nom_prenoms',
        'telephone',
        'piece_identite',
        'date_naissance',
        'lieu_naissance',
        'situation_matrimoniale',
        'nombre_enfants'
    ];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $value = $data[$field];
            
            // Convertir les valeurs vides en NULL pour certains champs
            if ($value === '' && in_array($field, ['date_naissance', 'collecteur_id', 'nombre_enfants'])) {
                $value = null;
            }
            
            // Convertir nombre_enfants en entier
            if ($field === 'nombre_enfants' && $value !== null && $value !== '') {
                $value = intval($value);
            }
            
            // Convertir collecteur_id en entier
            if ($field === 'collecteur_id' && $value !== null && $value !== '') {
                $value = intval($value);
            }
            
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
        }
    }

    if (!empty($updateFields)) {
        $updateValues[] = $id;
        $sql = "UPDATE exploitants SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
    }

    // Mise à jour de l'exploitation si fournie
    if (isset($data['exploitation']) && is_array($data['exploitation']) && $exploitant['exploitation_id']) {
        $explFields = [];
        $explValues = [];

        $allowedExplFields = [
            'region',
            'sous_prefecture_village',
            'latitude',
            'longitude'
        ];

        foreach ($allowedExplFields as $field) {
            if (array_key_exists($field, $data['exploitation'])) {
                $value = $data['exploitation'][$field];
                
                // Convertir les coordonnées en float
                if (in_array($field, ['latitude', 'longitude']) && $value !== null && $value !== '') {
                    $value = floatval($value);
                } elseif (in_array($field, ['latitude', 'longitude']) && ($value === null || $value === '')) {
                    $value = null;
                }
                
                $explFields[] = "$field = ?";
                $explValues[] = $value;
            }
        }

        if (!empty($explFields)) {
            $explValues[] = $exploitant['exploitation_id'];
            $sql = "UPDATE exploitations SET " . implode(', ', $explFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($explValues);
        }
    }

    $pdo->commit();

    // Récupérer l'exploitant mis à jour
    $stmt = $pdo->prepare("
        SELECT e.*, 
               ex.region, ex.sous_prefecture_village, ex.latitude, ex.longitude,
               u.nom as collecteur_nom, u.prenoms as collecteur_prenoms
        FROM exploitants e
        LEFT JOIN exploitations ex ON ex.exploitant_id = e.id
        LEFT JOIN utilisateurs u ON e.collecteur_id = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $updatedExploitant = $stmt->fetch(PDO::FETCH_ASSOC);

    success($updatedExploitant, 'Planteur mis à jour avec succès');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error('Erreur: ' . $e->getMessage(), 500);
}
