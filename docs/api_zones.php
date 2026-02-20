<?php
/**
 * API de gestion des zones
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/api_zones.php
 * 
 * Base de données: recensement_agricole
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

$action = $_GET['action'] ?? null;

// Requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? $action;
}

try {
    switch ($action) {
        case 'list':
            // Liste des zones avec le nombre de collecteurs
            $stmt = $conn->prepare("
                SELECT z.*, 
                       (SELECT COUNT(*) FROM utilisateurs u WHERE u.zone_id = z.id) as collecteurs_count
                FROM zones z
                ORDER BY z.nom_zone ASC
            ");
            $stmt->execute();
            $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            success($zones);
            break;

        case 'create':
            if (empty($data['nom_zone'])) {
                error('Le nom de la zone est requis');
            }
            
            $stmt = $conn->prepare("INSERT INTO zones (nom_zone) VALUES (?)");
            $stmt->execute([trim($data['nom_zone'])]);
            $newId = $conn->lastInsertId();
            
            success(['id' => $newId], 'Zone créée avec succès');
            break;

        case 'update':
            if (empty($data['id']) || empty($data['nom_zone'])) {
                error('ID et nom de la zone requis');
            }
            
            $stmt = $conn->prepare("UPDATE zones SET nom_zone = ? WHERE id = ?");
            $stmt->execute([trim($data['nom_zone']), $data['id']]);
            
            success(null, 'Zone modifiée avec succès');
            break;

        case 'delete':
            if (empty($data['id'])) {
                error('ID de la zone requis');
            }
            
            // Désassigner les collecteurs de cette zone
            $stmt = $conn->prepare("UPDATE utilisateurs SET zone_id = NULL WHERE zone_id = ?");
            $stmt->execute([$data['id']]);
            
            // Supprimer la zone
            $stmt = $conn->prepare("DELETE FROM zones WHERE id = ?");
            $stmt->execute([$data['id']]);
            
            success(null, 'Zone supprimée avec succès');
            break;

        case 'assign':
            if (empty($data['collecteur_id'])) {
                error('ID du collecteur requis');
            }
            
            $zoneId = !empty($data['zone_id']) ? $data['zone_id'] : null;
            
            $stmt = $conn->prepare("UPDATE utilisateurs SET zone_id = ? WHERE id = ?");
            $stmt->execute([$zoneId, $data['collecteur_id']]);
            
            success(null, 'Zone assignée avec succès');
            break;

        case 'get':
            if (empty($_GET['id'])) {
                error('ID de la zone requis');
            }
            
            $stmt = $conn->prepare("SELECT * FROM zones WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $zone = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$zone) {
                error('Zone introuvable', 404);
            }
            
            success($zone);
            break;

        case 'collecteurs':
            // Liste des collecteurs avec leurs zones
            $stmt = $conn->prepare("
                SELECT u.id, u.nom, u.prenoms, u.contact, u.login, u.role, u.zone_id,
                       z.nom_zone as zone_nom
                FROM utilisateurs u
                LEFT JOIN zones z ON u.zone_id = z.id
                WHERE u.role = 'collecteur' OR u.role = 'admin'
                ORDER BY u.nom ASC
            ");
            $stmt->execute();
            $collecteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            success($collecteurs);
            break;

        default:
            error('Action non reconnue');
    }
} catch (PDOException $e) {
    error('Erreur base de données: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error($e->getMessage(), 500);
}
