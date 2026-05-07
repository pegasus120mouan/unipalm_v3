<?php
/**
 * API pour récupérer les statistiques globales de tous les planteurs
 * GET - Statistiques globales (superficie totale, nombre de planteurs, etc.)
 * 
 * À DÉPLOYER SUR: https://api.objetombrepegasus.online/api/planteur/actions/api_stats_global.php
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

try {
    // Superficie totale de toutes les cultures
    $sqlSuperficie = "
        SELECT COALESCE(SUM(c.superficie_ha), 0) as superficie_totale
        FROM cultures c
    ";
    $stmt = $conn->prepare($sqlSuperficie);
    $stmt->execute();
    $superficie = $stmt->fetch();
    
    // Nombre total de planteurs
    $sqlPlanteurs = "
        SELECT COUNT(DISTINCT id) as nombre_planteurs
        FROM exploitants
    ";
    $stmt = $conn->prepare($sqlPlanteurs);
    $stmt->execute();
    $planteurs = $stmt->fetch();
    
    // Nombre total de parcelles
    $sqlParcelles = "
        SELECT COUNT(DISTINCT id) as nombre_parcelles
        FROM parcelles
    ";
    $stmt = $conn->prepare($sqlParcelles);
    $stmt->execute();
    $parcelles = $stmt->fetch();
    
    // Répartition par culture
    $sqlCultures = "
        SELECT 
            COALESCE(type_culture, 'Non spécifié') as type_culture,
            COUNT(DISTINCT exploitation_id) as nombre_exploitations,
            COALESCE(SUM(superficie_ha), 0) as superficie_totale
        FROM cultures
        GROUP BY type_culture
        ORDER BY superficie_totale DESC
    ";
    $stmt = $conn->prepare($sqlCultures);
    $stmt->execute();
    $repartitionCultures = $stmt->fetchAll();
    
    // Réponse
    echo json_encode([
        'success' => true,
        'data' => [
            'superficie_totale' => floatval($superficie['superficie_totale'] ?? 0),
            'nombre_planteurs' => intval($planteurs['nombre_planteurs'] ?? 0),
            'nombre_parcelles' => intval($parcelles['nombre_parcelles'] ?? 0),
            'repartition_cultures' => $repartitionCultures
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur SQL: ' . $e->getMessage()]);
}
