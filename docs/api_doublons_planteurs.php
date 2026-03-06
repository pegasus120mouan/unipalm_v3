<?php
/**
 * API pour récupérer les planteurs en double
 * GET ?action=doublons
 * 
 * Détecte les doublons basés sur:
 * - Même nom_prenoms ET même telephone
 * - Même numero_fiche
 * 
 * À déployer sur: https://api.objetombrepegasus.online/api/planteur/actions/api_doublons_planteurs.php
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

try {
    // Requête pour trouver les planteurs en double
    $sql = "
        SELECT 
            e.id,
            e.numero_fiche,
            e.nom_prenoms,
            e.telephone,
            e.piece_identite,
            e.date_naissance,
            e.photo,
            e.created_at,
            e.collecteur_id,
            
            -- Collecteur
            u.nom AS collecteur_nom,
            u.prenoms AS collecteur_prenoms,
            
            -- Exploitation
            ex.id AS exploitation_id,
            ex.region,
            ex.sous_prefecture_village,
            ex.village,
            ex.latitude,
            ex.longitude
            
        FROM exploitants e
        LEFT JOIN utilisateurs u ON e.collecteur_id = u.id
        LEFT JOIN exploitations ex ON ex.exploitant_id = e.id
        WHERE EXISTS (
            SELECT 1 FROM exploitants e2 
            WHERE e2.id != e.id 
            AND e2.nom_prenoms = e.nom_prenoms 
            AND e2.telephone = e.telephone
            AND e.nom_prenoms IS NOT NULL 
            AND e.nom_prenoms != ''
            AND e.telephone IS NOT NULL 
            AND e.telephone != ''
        )
        ORDER BY e.nom_prenoms, e.telephone, e.created_at ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données comme l'API planteurs
    $planteurs = [];
    foreach ($rows as $row) {
        $planteurs[] = [
            'id' => $row['id'],
            'numero_fiche' => $row['numero_fiche'],
            'nom_prenoms' => $row['nom_prenoms'],
            'telephone' => $row['telephone'],
            'piece_identite' => $row['piece_identite'],
            'date_naissance' => $row['date_naissance'],
            'photo' => $row['photo'],
            'created_at' => $row['created_at'],
            'collecteur' => [
                'nom' => $row['collecteur_nom'],
                'prenoms' => $row['collecteur_prenoms']
            ],
            'exploitation' => [
                'id' => $row['exploitation_id'],
                'region' => $row['region'],
                'sous_prefecture_village' => $row['sous_prefecture_village'],
                'village' => $row['village'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude']
            ]
        ];
    }
    
    // Grouper les doublons par nom_prenoms + telephone
    $groupes = [];
    foreach ($planteurs as $planteur) {
        $key = strtolower(trim($planteur['nom_prenoms'] ?? '')) . '_' . trim($planteur['telephone'] ?? '');
        if (!isset($groupes[$key])) {
            $groupes[$key] = [];
        }
        $groupes[$key][] = $planteur;
    }
    
    // Convertir en tableau indexé
    $groupesArray = array_values($groupes);
    
    success([
        'total_doublons' => count($planteurs),
        'total_groupes' => count($groupes),
        'a_supprimer' => max(0, count($planteurs) - count($groupes)),
        'groupes' => $groupesArray
    ]);
    
} catch (PDOException $e) {
    error('Erreur base de données: ' . $e->getMessage(), 500);
}
