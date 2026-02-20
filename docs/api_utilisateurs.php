<?php
/**
 * API des utilisateurs avec informations de zone
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/utilisateurs.php
 * 
 * GET: Liste des utilisateurs ou détails d'un utilisateur
 * Paramètres: id, role, statut
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../connexion.php';

function success($data, $message = 'Succès') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

try {
    $id = $_GET['id'] ?? null;
    $role = $_GET['role'] ?? null;
    $statut = $_GET['statut'] ?? null;

    // Détails d'un utilisateur spécifique
    if ($id) {
        $stmt = $conn->prepare("
            SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.avatar,
                   u.statut_compte, u.zone_id, u.created_at, u.updated_at,
                   z.nom_zone as zone_nom
            FROM utilisateurs u
            LEFT JOIN zones z ON u.zone_id = z.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error('Utilisateur introuvable', 404);
        }

        $user['id'] = (int)$user['id'];
        $user['statut_compte'] = (bool)$user['statut_compte'];
        $user['zone_id'] = $user['zone_id'] ? (int)$user['zone_id'] : null;
        $user['nom_complet'] = trim($user['nom'] . ' ' . $user['prenoms']);

        success($user);
    }

    // Liste des utilisateurs
    $sql = "
        SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.avatar,
               u.statut_compte, u.zone_id, u.created_at, u.updated_at,
               z.nom_zone as zone_nom
        FROM utilisateurs u
        LEFT JOIN zones z ON u.zone_id = z.id
        WHERE 1=1
    ";
    $params = [];

    if ($role) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
    }

    if ($statut !== null && $statut !== '') {
        $sql .= " AND u.statut_compte = ?";
        $params[] = (int)$statut;
    }

    $sql .= " ORDER BY u.nom ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données
    foreach ($utilisateurs as &$user) {
        $user['id'] = (int)$user['id'];
        $user['statut_compte'] = (bool)$user['statut_compte'];
        $user['zone_id'] = $user['zone_id'] ? (int)$user['zone_id'] : null;
        $user['nom_complet'] = trim($user['nom'] . ' ' . $user['prenoms']);
    }
    unset($user);

    success(['utilisateurs' => $utilisateurs]);

} catch (PDOException $e) {
    error('Erreur base de données: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error($e->getMessage(), 500);
}
