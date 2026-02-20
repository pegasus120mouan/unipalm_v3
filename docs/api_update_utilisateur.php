<?php
/**
 * API de mise à jour d'un utilisateur
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/update_utilisateur.php
 * 
 * POST avec JSON body: { id, nom, prenoms, contact, login, role, statut_compte, zone_id, password (optionnel) }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée. Utilisez POST ou PUT.']);
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

if (!$data || empty($data['id'])) {
    error('ID utilisateur requis', 400);
}

$id = (int)$data['id'];

try {
    // Vérifier si l'utilisateur existe
    $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        error('Utilisateur introuvable', 404);
    }

    // Construire la requête de mise à jour dynamiquement
    $updates = [];
    $params = [];

    $allowedFields = ['nom', 'prenoms', 'contact', 'login', 'role', 'statut_compte', 'zone_id'];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            if ($field === 'zone_id') {
                // zone_id peut être null ou un entier
                $updates[] = "$field = ?";
                $params[] = !empty($data[$field]) ? (int)$data[$field] : null;
            } elseif ($field === 'statut_compte') {
                $updates[] = "$field = ?";
                $params[] = (int)$data[$field];
            } else {
                $updates[] = "$field = ?";
                $params[] = trim($data[$field]);
            }
        }
    }

    // Gestion du mot de passe (optionnel)
    if (!empty($data['password'])) {
        $updates[] = "password = ?";
        $params[] = hash('sha256', $data['password']);
    }

    if (empty($updates)) {
        error('Aucun champ à mettre à jour', 400);
    }

    // Vérifier si le login est déjà utilisé par un autre utilisateur
    if (isset($data['login'])) {
        $stmtCheckLogin = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ? AND id != ?");
        $stmtCheckLogin->execute([trim($data['login']), $id]);
        if ($stmtCheckLogin->fetch()) {
            error("Le login '" . trim($data['login']) . "' est déjà utilisé", 409);
        }
    }

    // Vérifier si le contact est déjà utilisé par un autre utilisateur
    if (isset($data['contact'])) {
        $stmtCheckContact = $pdo->prepare("SELECT id FROM utilisateurs WHERE contact = ? AND id != ?");
        $stmtCheckContact->execute([trim($data['contact']), $id]);
        if ($stmtCheckContact->fetch()) {
            error("Le contact '" . trim($data['contact']) . "' est déjà utilisé", 409);
        }
    }

    // Vérifier si la zone existe (si spécifiée)
    if (isset($data['zone_id']) && !empty($data['zone_id'])) {
        $stmtCheckZone = $pdo->prepare("SELECT id FROM zones WHERE id = ?");
        $stmtCheckZone->execute([(int)$data['zone_id']]);
        if (!$stmtCheckZone->fetch()) {
            error("La zone spécifiée n'existe pas", 400);
        }
    }

    // Ajouter updated_at
    $updates[] = "updated_at = NOW()";

    // Exécuter la mise à jour
    $params[] = $id;
    $sql = "UPDATE utilisateurs SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Récupérer l'utilisateur mis à jour
    $stmtGet = $pdo->prepare("
        SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.zone_id, 
               u.statut_compte, u.avatar, u.created_at, u.updated_at,
               z.nom_zone as zone_nom
        FROM utilisateurs u
        LEFT JOIN zones z ON u.zone_id = z.id
        WHERE u.id = ?
    ");
    $stmtGet->execute([$id]);
    $user = $stmtGet->fetch(PDO::FETCH_ASSOC);

    success([
        'id' => (int)$user['id'],
        'nom' => $user['nom'],
        'prenoms' => $user['prenoms'],
        'nom_complet' => trim($user['nom'] . ' ' . $user['prenoms']),
        'contact' => $user['contact'],
        'login' => $user['login'],
        'role' => $user['role'],
        'zone_id' => $user['zone_id'] ? (int)$user['zone_id'] : null,
        'zone_nom' => $user['zone_nom'],
        'statut_compte' => (bool)$user['statut_compte'],
        'updated_at' => $user['updated_at'],
    ], 'Utilisateur modifié avec succès');

} catch (PDOException $e) {
    error('Erreur lors de la modification: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error('Erreur: ' . $e->getMessage(), 500);
}
