<?php
/**
 * API de gestion des régions et sous-préfectures
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/api_regions.php
 * 
 * Base de données: recensement_agricole
 * 
 * Actions disponibles:
 * - list: Liste toutes les régions
 * - get: Détails d'une région avec ses sous-préfectures
 * - sous_prefectures: Liste des sous-préfectures (optionnel: filtrer par region_id)
 * - all: Liste des régions avec leurs sous-préfectures imbriquées
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

$action = $_GET['action'] ?? 'list';

// Requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? $action;
}

try {
    switch ($action) {
        case 'list':
            // Liste des régions avec le nombre de sous-préfectures
            $stmt = $conn->prepare("
                SELECT r.id, r.nom, r.district_id,
                       (SELECT COUNT(*) FROM sous_prefectures sp WHERE sp.region_id = r.id) as sous_prefectures_count
                FROM regions r
                ORDER BY r.nom ASC
            ");
            $stmt->execute();
            $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les données
            foreach ($regions as &$region) {
                $region['id'] = (int)$region['id'];
                $region['district_id'] = $region['district_id'] ? (int)$region['district_id'] : null;
                $region['sous_prefectures_count'] = (int)$region['sous_prefectures_count'];
            }
            unset($region);
            
            success($regions);
            break;

        case 'get':
            // Détails d'une région avec ses sous-préfectures
            if (empty($_GET['id'])) {
                error('ID de la région requis');
            }
            
            $regionId = (int)$_GET['id'];
            
            // Récupérer la région
            $stmt = $conn->prepare("SELECT id, nom, district_id FROM regions WHERE id = ?");
            $stmt->execute([$regionId]);
            $region = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$region) {
                error('Région introuvable', 404);
            }
            
            $region['id'] = (int)$region['id'];
            $region['district_id'] = $region['district_id'] ? (int)$region['district_id'] : null;
            
            // Récupérer les sous-préfectures de cette région
            $stmt = $conn->prepare("
                SELECT id, nom, region_id
                FROM sous_prefectures
                WHERE region_id = ?
                ORDER BY nom ASC
            ");
            $stmt->execute([$regionId]);
            $sousPrefectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sousPrefectures as &$sp) {
                $sp['id'] = (int)$sp['id'];
                $sp['region_id'] = (int)$sp['region_id'];
            }
            unset($sp);
            
            $region['sous_prefectures'] = $sousPrefectures;
            
            success($region);
            break;

        case 'sous_prefectures':
            // Liste des sous-préfectures (optionnel: filtrer par region_id)
            $regionId = isset($_GET['region_id']) ? (int)$_GET['region_id'] : null;
            
            if ($regionId) {
                $stmt = $conn->prepare("
                    SELECT sp.id, sp.nom, sp.region_id, r.nom as region_nom
                    FROM sous_prefectures sp
                    LEFT JOIN regions r ON sp.region_id = r.id
                    WHERE sp.region_id = ?
                    ORDER BY sp.nom ASC
                ");
                $stmt->execute([$regionId]);
            } else {
                $stmt = $conn->prepare("
                    SELECT sp.id, sp.nom, sp.region_id, r.nom as region_nom
                    FROM sous_prefectures sp
                    LEFT JOIN regions r ON sp.region_id = r.id
                    ORDER BY r.nom ASC, sp.nom ASC
                ");
                $stmt->execute();
            }
            
            $sousPrefectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sousPrefectures as &$sp) {
                $sp['id'] = (int)$sp['id'];
                $sp['region_id'] = $sp['region_id'] ? (int)$sp['region_id'] : null;
            }
            unset($sp);
            
            success($sousPrefectures);
            break;

        case 'all':
            // Liste des régions avec leurs sous-préfectures imbriquées
            $stmt = $conn->prepare("SELECT id, nom, district_id FROM regions ORDER BY nom ASC");
            $stmt->execute();
            $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer toutes les sous-préfectures
            $stmt = $conn->prepare("SELECT id, nom, region_id FROM sous_prefectures ORDER BY nom ASC");
            $stmt->execute();
            $allSousPrefectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Grouper les sous-préfectures par région
            $spByRegion = [];
            foreach ($allSousPrefectures as $sp) {
                $regionId = $sp['region_id'];
                if (!isset($spByRegion[$regionId])) {
                    $spByRegion[$regionId] = [];
                }
                $spByRegion[$regionId][] = [
                    'id' => (int)$sp['id'],
                    'nom' => $sp['nom'],
                    'region_id' => (int)$sp['region_id']
                ];
            }
            
            // Ajouter les sous-préfectures à chaque région
            foreach ($regions as &$region) {
                $region['id'] = (int)$region['id'];
                $region['district_id'] = $region['district_id'] ? (int)$region['district_id'] : null;
                $region['sous_prefectures'] = $spByRegion[$region['id']] ?? [];
                $region['sous_prefectures_count'] = count($region['sous_prefectures']);
            }
            unset($region);
            
            success($regions);
            break;

        case 'create_sp':
            // Créer une sous-préfecture
            if (empty($data['nom'])) {
                error('Le nom de la sous-préfecture est requis');
            }
            if (empty($data['region_id'])) {
                error('L\'ID de la région est requis');
            }
            
            $regionId = (int)$data['region_id'];
            $nom = trim($data['nom']);
            
            // Vérifier que la région existe
            $stmt = $conn->prepare("SELECT id FROM regions WHERE id = ?");
            $stmt->execute([$regionId]);
            if (!$stmt->fetch()) {
                error('Région introuvable', 404);
            }
            
            // Vérifier si la sous-préfecture existe déjà dans cette région
            $stmt = $conn->prepare("SELECT id FROM sous_prefectures WHERE nom = ? AND region_id = ?");
            $stmt->execute([$nom, $regionId]);
            if ($stmt->fetch()) {
                error('Cette sous-préfecture existe déjà dans cette région', 409);
            }
            
            // Insérer la sous-préfecture
            $stmt = $conn->prepare("INSERT INTO sous_prefectures (nom, region_id) VALUES (?, ?)");
            $stmt->execute([$nom, $regionId]);
            $newId = $conn->lastInsertId();
            
            success([
                'id' => (int)$newId,
                'nom' => $nom,
                'region_id' => $regionId
            ], 'Sous-préfecture créée avec succès');
            break;

        case 'delete_sp':
            // Supprimer une sous-préfecture
            if (empty($data['id'])) {
                error('L\'ID de la sous-préfecture est requis');
            }
            
            $spId = (int)$data['id'];
            
            // Vérifier que la sous-préfecture existe
            $stmt = $conn->prepare("SELECT id FROM sous_prefectures WHERE id = ?");
            $stmt->execute([$spId]);
            if (!$stmt->fetch()) {
                error('Sous-préfecture introuvable', 404);
            }
            
            // Supprimer la sous-préfecture
            $stmt = $conn->prepare("DELETE FROM sous_prefectures WHERE id = ?");
            $stmt->execute([$spId]);
            
            success(null, 'Sous-préfecture supprimée avec succès');
            break;

        case 'update_sp':
            // Modifier une sous-préfecture
            if (empty($data['id']) || empty($data['nom'])) {
                error('ID et nom de la sous-préfecture requis');
            }
            
            $spId = (int)$data['id'];
            $nom = trim($data['nom']);
            
            // Vérifier que la sous-préfecture existe
            $stmt = $conn->prepare("SELECT region_id FROM sous_prefectures WHERE id = ?");
            $stmt->execute([$spId]);
            $sp = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sp) {
                error('Sous-préfecture introuvable', 404);
            }
            
            // Vérifier si le nouveau nom existe déjà dans cette région
            $stmt = $conn->prepare("SELECT id FROM sous_prefectures WHERE nom = ? AND region_id = ? AND id != ?");
            $stmt->execute([$nom, $sp['region_id'], $spId]);
            if ($stmt->fetch()) {
                error('Ce nom existe déjà dans cette région', 409);
            }
            
            // Mettre à jour
            $stmt = $conn->prepare("UPDATE sous_prefectures SET nom = ? WHERE id = ?");
            $stmt->execute([$nom, $spId]);
            
            success(['id' => $spId, 'nom' => $nom], 'Sous-préfecture modifiée avec succès');
            break;

        default:
            error('Action non reconnue. Actions disponibles: list, get, sous_prefectures, all, create_sp, delete_sp, update_sp');
    }
} catch (PDOException $e) {
    error('Erreur base de données: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error($e->getMessage(), 500);
}
