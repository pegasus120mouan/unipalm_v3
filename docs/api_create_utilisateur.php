<?php
/**
 * Crée un nouvel utilisateur
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/create_utilisateur.php
 * 
 * POST avec JSON body: { nom, prenoms, contact, login, password, role, zone_id (optionnel) }
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

// Validation des champs requis
$required = ['nom', 'prenoms', 'contact', 'login', 'password', 'role'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        error("Le champ '$field' est requis", 400);
    }
}

$nom = trim($data['nom']);
$prenoms = trim($data['prenoms']);
$contact = trim($data['contact']);
$login = trim($data['login']);
$password = $data['password'];
$role = trim($data['role']);
$zone_id = !empty($data['zone_id']) ? (int)$data['zone_id'] : null;

// Valider le rôle
$rolesAutorises = ['collecteur', 'operateur', 'caissiere', 'directeur', 'admin'];
if (!in_array(strtolower($role), $rolesAutorises)) {
    error("Rôle invalide. Rôles autorisés: " . implode(', ', $rolesAutorises), 400);
}

try {
    // Vérifier si le login existe déjà
    $stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
    $stmtCheck->execute([$login]);
    if ($stmtCheck->fetch()) {
        error("Le login '$login' existe déjà", 409);
    }

    // Vérifier si le contact existe déjà
    $stmtCheckContact = $pdo->prepare("SELECT id FROM utilisateurs WHERE contact = ?");
    $stmtCheckContact->execute([$contact]);
    if ($stmtCheckContact->fetch()) {
        error("Le contact '$contact' existe déjà", 409);
    }

    // Vérifier si la zone existe (si spécifiée)
    if ($zone_id !== null) {
        $stmtCheckZone = $pdo->prepare("SELECT id FROM zones WHERE id = ?");
        $stmtCheckZone->execute([$zone_id]);
        if (!$stmtCheckZone->fetch()) {
            error("La zone spécifiée n'existe pas", 400);
        }
    }

    // Hasher le mot de passe en SHA-256
    $hashedPassword = hash('sha256', $password);

    // Insérer l'utilisateur
    $sql = "
        INSERT INTO utilisateurs (nom, prenoms, contact, login, password, role, zone_id, statut_compte, avatar, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'default.jpg', NOW(), NOW())
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $prenoms, $contact, $login, $hashedPassword, $role, $zone_id]);
    
    $newId = $pdo->lastInsertId();

    // Récupérer l'utilisateur créé avec le nom de la zone
    $stmtGet = $pdo->prepare("
        SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.zone_id, u.statut_compte, u.created_at,
               z.nom_zone
        FROM utilisateurs u
        LEFT JOIN zones z ON u.zone_id = z.id
        WHERE u.id = ?
    ");
    $stmtGet->execute([$newId]);
    $newUser = $stmtGet->fetch(PDO::FETCH_ASSOC);

    success([
        'id' => (int)$newUser['id'],
        'nom' => $newUser['nom'],
        'prenoms' => $newUser['prenoms'],
        'nom_complet' => trim($newUser['nom'] . ' ' . $newUser['prenoms']),
        'contact' => $newUser['contact'],
        'login' => $newUser['login'],
        'role' => $newUser['role'],
        'zone_id' => $newUser['zone_id'] ? (int)$newUser['zone_id'] : null,
        'zone_nom' => $newUser['nom_zone'],
        'statut_compte' => (bool)$newUser['statut_compte'],
        'created_at' => $newUser['created_at'],
    ], 'Utilisateur créé avec succès');

} catch (PDOException $e) {
    error('Erreur lors de la création: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error('Erreur: ' . $e->getMessage(), 500);
}
