<?php
/**
 * Récupère tous les planteurs avec leurs données complètes
 * GET ?action=planteurs
 * GET ?action=planteurs&id={id} - Un seul planteur
 * GET ?action=planteurs&collecteur_id={id} - Planteurs d'un collecteur
 * GET ?action=planteurs&since={timestamp} - Planteurs depuis une date
 * 
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/planteurs.php
 */

// Si appelé directement (pas via index.php)
if (!function_exists('success')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    
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
}

global $pdo;

$id = $_GET['id'] ?? null;
$collecteurId = $_GET['collecteur_id'] ?? null;
$since = $_GET['since'] ?? null;

try {
    // Construction de la requête de base avec jointures vers regions et sous_prefectures
    $sql = "
        SELECT 
            e.id,
            e.numero_fiche,
            e.date_enregistrement,
            e.nom_prenoms,
            e.date_naissance,
            e.lieu_naissance,
            e.piece_identite,
            e.telephone,
            e.photo,
            e.situation_matrimoniale,
            e.nombre_enfants,
            e.collecteur_id,
            e.created_at,
            
            -- Collecteur
            u.nom AS collecteur_nom,
            u.prenoms AS collecteur_prenoms,
            u.contact AS collecteur_contact,
            
            -- Exploitation
            ex.id AS exploitation_id,
            ex.region AS exploitation_region,
            ex.sous_prefecture_village,
            ex.village,
            ex.longitude,
            ex.latitude,
            ex.video,
            ex.delegue_id,
            ex.delegue_nom,
            
            -- Région (jointure sur le nom)
            r.id AS region_id,
            r.nom AS region_nom,
            r.district_id,
            
            -- Sous-préfecture (jointure sur le nom et la région)
            sp.id AS sous_prefecture_id,
            sp.nom AS sous_prefecture_nom
            
        FROM exploitants e
        LEFT JOIN utilisateurs u ON e.collecteur_id = u.id
        LEFT JOIN exploitations ex ON ex.exploitant_id = e.id
        LEFT JOIN regions r ON r.nom = ex.region
        LEFT JOIN sous_prefectures sp ON sp.nom = ex.sous_prefecture_village AND sp.region_id = r.id
    ";
    
    $params = [];
    $conditions = [];
    
    // Filtrer par ID
    if ($id) {
        $conditions[] = "e.id = ?";
        $params[] = $id;
    }
    
    // Filtrer par collecteur
    if ($collecteurId) {
        $conditions[] = "e.collecteur_id = ?";
        $params[] = $collecteurId;
    }
    
    // Filtrer par date
    if ($since) {
        $timestamp = date('Y-m-d H:i:s', (int)($since / 1000));
        $conditions[] = "e.created_at >= ?";
        $params[] = $timestamp;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY e.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exploitants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque exploitant, récupérer les données associées
    $result = [];
    foreach ($exploitants as $exploitant) {
        $exploitantId = $exploitant['id'];
        $exploitationId = $exploitant['exploitation_id'];
        
        $cultures = [];
        $informations = null;
        $parcelles = [];
        
        if ($exploitationId) {
            // Récupérer les cultures
            $stmtCult = $pdo->prepare("
                SELECT id, type_culture, autre_culture, superficie_ha, age_culture, mode_culture, production_estimee_kg, created_at
                FROM cultures 
                WHERE exploitation_id = ?
            ");
            $stmtCult->execute([$exploitationId]);
            $culturesRaw = $stmtCult->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les parcelles pour chaque culture
            $stmtParcelle = $pdo->prepare("
                SELECT id, nom, points, superficie_calculee, created_at
                FROM parcelles 
                WHERE culture_id = ?
            ");
            
            foreach ($culturesRaw as $culture) {
                $stmtParcelle->execute([$culture['id']]);
                $parcellesRaw = $stmtParcelle->fetchAll(PDO::FETCH_ASSOC);
                
                $culture['parcelles'] = array_map(function($p) {
                    return [
                        'id' => (int)$p['id'],
                        'nom' => $p['nom'],
                        'points' => json_decode($p['points'], true),
                        'superficie_calculee' => $p['superficie_calculee'] ? (float)$p['superficie_calculee'] : null,
                        'created_at' => $p['created_at'],
                    ];
                }, $parcellesRaw);
                
                // Ajouter les parcelles à la liste globale
                foreach ($culture['parcelles'] as $parc) {
                    $parcelles[] = $parc;
                }
                
                $cultures[] = $culture;
            }
            
            // Récupérer les informations complémentaires
            $stmtInfo = $pdo->prepare("
                SELECT id, type_semences, usage_phytosanitaires, nombre_travailleurs, created_at
                FROM informations_complementaires 
                WHERE exploitation_id = ?
            ");
            $stmtInfo->execute([$exploitationId]);
            $informations = $stmtInfo->fetch(PDO::FETCH_ASSOC);
        }
        
        // URL de base MinIO pour les photos
        $minioBaseUrl = 'http://51.178.49.141:9000/planteurs';
        
        // Construire l'objet complet
        $result[] = [
            'id' => (int)$exploitant['id'],
            'numero_fiche' => $exploitant['numero_fiche'],
            'date_enregistrement' => $exploitant['date_enregistrement'],
            'nom_prenoms' => $exploitant['nom_prenoms'],
            'date_naissance' => $exploitant['date_naissance'],
            'lieu_naissance' => $exploitant['lieu_naissance'],
            'piece_identite' => $exploitant['piece_identite'],
            'telephone' => $exploitant['telephone'],
            'photo' => $exploitant['photo'],
            'photo_url' => $exploitant['photo'] ? $minioBaseUrl . '/' . $exploitant['photo'] : null,
            'situation_matrimoniale' => $exploitant['situation_matrimoniale'],
            'nombre_enfants' => (int)$exploitant['nombre_enfants'],
            'collecteur_id' => $exploitant['collecteur_id'] ? (int)$exploitant['collecteur_id'] : null,
            'collecteur' => $exploitant['collecteur_id'] ? [
                'id' => (int)$exploitant['collecteur_id'],
                'nom' => $exploitant['collecteur_nom'],
                'prenoms' => $exploitant['collecteur_prenoms'],
                'contact' => $exploitant['collecteur_contact'],
            ] : null,
            'created_at' => $exploitant['created_at'],
            
            // Exploitation
            'exploitation' => $exploitationId ? [
                'id' => (int)$exploitationId,
                'region' => $exploitant['exploitation_region'],
                'sous_prefecture_village' => $exploitant['sous_prefecture_village'],
                'village' => $exploitant['village'],
                'longitude' => $exploitant['longitude'] ? (float)$exploitant['longitude'] : null,
                'latitude' => $exploitant['latitude'] ? (float)$exploitant['latitude'] : null,
                'video' => $exploitant['video'],
                'video_url' => $exploitant['video'] ? $minioBaseUrl . '/' . $exploitant['video'] : null,
                'delegue_id' => $exploitant['delegue_id'] ? (int)$exploitant['delegue_id'] : null,
                'delegue_nom' => $exploitant['delegue_nom'],
            ] : null,
            
            // Région avec ID (jointure)
            'region' => $exploitant['region_id'] ? [
                'id' => (int)$exploitant['region_id'],
                'nom' => $exploitant['region_nom'],
                'district_id' => $exploitant['district_id'] ? (int)$exploitant['district_id'] : null,
            ] : null,
            
            // Sous-préfecture/Département avec ID (jointure)
            'sous_prefecture' => $exploitant['sous_prefecture_id'] ? [
                'id' => (int)$exploitant['sous_prefecture_id'],
                'nom' => $exploitant['sous_prefecture_nom'],
                'region_id' => $exploitant['region_id'] ? (int)$exploitant['region_id'] : null,
            ] : null,
            
            // Cultures
            'cultures' => array_map(function($c) {
                return [
                    'id' => (int)$c['id'],
                    'type_culture' => $c['type_culture'],
                    'autre_culture' => $c['autre_culture'],
                    'superficie_ha' => $c['superficie_ha'] ? (float)$c['superficie_ha'] : null,
                    'age_culture' => $c['age_culture'] ? (int)$c['age_culture'] : null,
                    'mode_culture' => $c['mode_culture'],
                    'production_estimee_kg' => $c['production_estimee_kg'] ? (float)$c['production_estimee_kg'] : null,
                    'created_at' => $c['created_at'],
                    'parcelles' => $c['parcelles'] ?? [],
                ];
            }, $cultures),
            
            // Parcelles (liste globale)
            'parcelles' => $parcelles,
            
            // Informations complémentaires
            'informations' => $informations ? [
                'id' => (int)$informations['id'],
                'type_semences' => $informations['type_semences'],
                'usage_phytosanitaires' => (bool)$informations['usage_phytosanitaires'],
                'nombre_travailleurs' => $informations['nombre_travailleurs'] ? (int)$informations['nombre_travailleurs'] : null,
                'created_at' => $informations['created_at'],
            ] : null,
        ];
    }
    
    // Si on demande un seul planteur par ID
    if ($id && count($result) === 1) {
        success($result[0], 'Planteur récupéré avec succès');
    } else {
        success([
            'total' => count($result),
            'planteurs' => $result
        ], 'Liste des planteurs récupérée avec succès');
    }
    
} catch (Exception $e) {
    error('Erreur lors de la récupération des planteurs: ' . $e->getMessage(), 500);
}
