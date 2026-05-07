<?php
/**
 * API pour récupérer les statistiques d'un collecteur
 * GET ?collecteur_id={id} - Statistiques complètes
 * GET ?collecteur_id={id}&date_debut={date}&date_fin={date} - Avec filtre de période
 * 
 * À DÉPLOYER SUR: https://api.objetombrepegasus.online/api/planteur/actions/stats_collecteur.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../connexion.php';

// Paramètres
$collecteurId = isset($_GET['collecteur_id']) ? intval($_GET['collecteur_id']) : 0;
$dateDebut = isset($_GET['date_debut']) ? $_GET['date_debut'] : null;
$dateFin = isset($_GET['date_fin']) ? $_GET['date_fin'] : null;

if (!$collecteurId) {
    echo json_encode(['success' => false, 'error' => 'collecteur_id requis']);
    exit;
}

try {
    // Statistiques globales - Superficie
    $sqlSuperficie = "
        SELECT COALESCE(SUM(c.superficie_ha), 0) as superficie_totale
        FROM exploitants ex
        INNER JOIN exploitations ep ON ep.exploitant_id = ex.id
        INNER JOIN cultures c ON c.exploitation_id = ep.id
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    // Statistiques globales - Nombre exploitants
    $sqlExploitants = "
        SELECT COUNT(DISTINCT ex.id) as nombre_exploitants
        FROM exploitants ex
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    // Statistiques globales - Nombre parcelles
    $sqlParcelles = "
        SELECT COUNT(DISTINCT p.id) as nombre_parcelles
        FROM exploitants ex
        INNER JOIN exploitations ep ON ep.exploitant_id = ex.id
        INNER JOIN parcelles p ON p.exploitation_id = ep.id
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    $params = ['collecteur_id' => $collecteurId];
    $paramsWithDates = ['collecteur_id' => $collecteurId];
    
    if ($dateDebut) {
        $sqlSuperficie .= " AND DATE(ex.created_at) >= :date_debut";
        $sqlExploitants .= " AND DATE(ex.created_at) >= :date_debut";
        $sqlParcelles .= " AND DATE(ex.created_at) >= :date_debut";
        $paramsWithDates['date_debut'] = $dateDebut;
    }
    if ($dateFin) {
        $sqlSuperficie .= " AND DATE(ex.created_at) <= :date_fin";
        $sqlExploitants .= " AND DATE(ex.created_at) <= :date_fin";
        $sqlParcelles .= " AND DATE(ex.created_at) <= :date_fin";
        $paramsWithDates['date_fin'] = $dateFin;
    }
    
    // Exécuter les 3 requêtes séparément
    $stmt = $conn->prepare($sqlSuperficie);
    $stmt->execute($paramsWithDates);
    $superficie = $stmt->fetch();
    
    $stmt = $conn->prepare($sqlExploitants);
    $stmt->execute($paramsWithDates);
    $exploitants = $stmt->fetch();
    
    $stmt = $conn->prepare($sqlParcelles);
    $stmt->execute($paramsWithDates);
    $parcelles = $stmt->fetch();
    
    $stats = [
        'nombre_exploitants' => intval($exploitants['nombre_exploitants'] ?? 0),
        'superficie_totale' => floatval($superficie['superficie_totale'] ?? 0),
        'nombre_parcelles' => intval($parcelles['nombre_parcelles'] ?? 0)
    ];
    
    // Répartition par culture
    $sqlCultures = "
        SELECT 
            COALESCE(c.type_culture, 'Non spécifié') as type_culture,
            COUNT(DISTINCT ex.id) as nombre_exploitants,
            COALESCE(SUM(c.superficie_ha), 0) as superficie_totale
        FROM exploitants ex
        INNER JOIN exploitations ep ON ep.exploitant_id = ex.id
        INNER JOIN cultures c ON c.exploitation_id = ep.id
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    if ($dateDebut) {
        $sqlCultures .= " AND DATE(ex.created_at) >= :date_debut";
    }
    if ($dateFin) {
        $sqlCultures .= " AND DATE(ex.created_at) <= :date_fin";
    }
    
    $sqlCultures .= " GROUP BY c.type_culture ORDER BY superficie_totale DESC";
    
    $stmt = $conn->prepare($sqlCultures);
    $stmt->execute($paramsWithDates);
    $statsParCulture = $stmt->fetchAll();
    
    // Évolution mensuelle
    $sqlMensuel = "
        SELECT 
            DATE_FORMAT(ex.created_at, '%Y-%m') as mois,
            COUNT(DISTINCT ex.id) as nombre_exploitants,
            COALESCE(SUM(c.superficie_ha), 0) as superficie_totale
        FROM exploitants ex
        INNER JOIN exploitations ep ON ep.exploitant_id = ex.id
        INNER JOIN cultures c ON c.exploitation_id = ep.id
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    if ($dateDebut) {
        $sqlMensuel .= " AND DATE(ex.created_at) >= :date_debut";
    }
    if ($dateFin) {
        $sqlMensuel .= " AND DATE(ex.created_at) <= :date_fin";
    }
    
    $sqlMensuel .= " GROUP BY DATE_FORMAT(ex.created_at, '%Y-%m') ORDER BY mois ASC";
    
    $stmt = $conn->prepare($sqlMensuel);
    $stmt->execute($paramsWithDates);
    $statsMensuel = $stmt->fetchAll();
    
    // Derniers planteurs (20 derniers)
    $sqlDerniers = "
        SELECT 
            ex.id,
            ex.nom_prenoms,
            ex.telephone,
            DATE(ex.created_at) as date_enregistrement,
            ep.region,
            ep.village,
            COALESCE(SUM(c.superficie_ha), 0) as superficie_totale
        FROM exploitants ex
        INNER JOIN exploitations ep ON ep.exploitant_id = ex.id
        INNER JOIN cultures c ON c.exploitation_id = ep.id
        WHERE ex.collecteur_id = :collecteur_id
    ";
    
    if ($dateDebut) {
        $sqlDerniers .= " AND DATE(ex.created_at) >= :date_debut";
    }
    if ($dateFin) {
        $sqlDerniers .= " AND DATE(ex.created_at) <= :date_fin";
    }
    
    $sqlDerniers .= " GROUP BY ex.id, ex.nom_prenoms, ex.telephone, ex.created_at, ep.region, ep.village
                      ORDER BY ex.created_at DESC LIMIT 20";
    
    $stmt = $conn->prepare($sqlDerniers);
    $stmt->execute($paramsWithDates);
    $derniersPlanteurs = $stmt->fetchAll();
    
    // Réponse
    echo json_encode([
        'success' => true,
        'data' => [
            'collecteur_id' => $collecteurId,
            'filtre' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ],
            'stats' => $stats,
            'repartition_cultures' => $statsParCulture,
            'evolution_mensuelle' => $statsMensuel,
            'derniers_planteurs' => $derniersPlanteurs
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur SQL: ' . $e->getMessage()]);
}
