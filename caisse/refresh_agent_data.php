<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_agents.php';
session_start();

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    exit('Requête invalide');
}

// Vérifier l'ID de l'agent
if (!isset($_GET['id_agent']) || !is_numeric($_GET['id_agent'])) {
    http_response_code(400);
    exit('ID agent manquant');
}

$id_agent = (int)$_GET['id_agent'];

try {
    // Récupérer les statistiques financières de l'agent
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN b.date_validation_boss IS NOT NULL THEN b.montant_total ELSE 0 END), 0) as total_montant,
            COALESCE(SUM(CASE WHEN b.date_validation_boss IS NOT NULL THEN COALESCE(b.montant_payer, 0) ELSE 0 END), 0) as montant_paye,
            COALESCE(SUM(CASE WHEN t.date_validation IS NOT NULL THEN t.montant_paie ELSE 0 END), 0) as total_tickets,
            COALESCE(SUM(CASE WHEN t.date_validation IS NOT NULL THEN COALESCE(t.montant_payer, 0) ELSE 0 END), 0) as tickets_payes
        FROM agents a
        LEFT JOIN bordereau b ON a.id_agent = b.id_agent AND b.date_suppression IS NULL
        LEFT JOIN tickets t ON a.id_agent = t.id_agent AND t.date_suppression IS NULL
        WHERE a.id_agent = ? AND a.date_suppression IS NULL
        GROUP BY a.id_agent
    ");
    $stmt->execute([$id_agent]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        http_response_code(404);
        exit('Agent non trouvé');
    }
    
    // Calculer les montants restants
    $total_montant = (float)$stats['total_montant'] + (float)$stats['total_tickets'];
    $montant_paye = (float)$stats['montant_paye'] + (float)$stats['tickets_payes'];
    $reste_a_payer = $total_montant - $montant_paye;
    
    // Récupérer le solde de financement
    $stmt = $conn->prepare("SELECT COALESCE(SUM(montant), 0) as solde_financement FROM financement WHERE id_agent = ? AND montant > 0");
    $stmt->execute([$id_agent]);
    $financement = $stmt->fetch(PDO::FETCH_ASSOC);
    $solde_financement = (float)$financement['solde_financement'];
    
    // Récupérer le solde de caisse
    $stmt = $conn->prepare("SELECT COALESCE(SUM(montant), 0) as solde_caisse FROM transactions WHERE type_transaction = 'entree'");
    $stmt->execute();
    $entrees = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(montant), 0) as sorties_caisse FROM transactions WHERE type_transaction = 'sortie'");
    $stmt->execute();
    $sorties = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $solde_caisse = (float)$entrees['solde_caisse'] - (float)$sorties['sorties_caisse'];
    
    // Retourner les données en JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'total_montant' => $total_montant,
            'montant_paye' => $montant_paye,
            'reste_a_payer' => $reste_a_payer,
            'solde_financement' => $solde_financement,
            'solde_caisse' => $solde_caisse,
            'formatted' => [
                'total_montant' => number_format($total_montant, 0, ',', ' ') . ' FCFA',
                'montant_paye' => number_format($montant_paye, 0, ',', ' ') . ' FCFA',
                'reste_a_payer' => number_format($reste_a_payer, 0, ',', ' ') . ' FCFA',
                'solde_financement' => number_format($solde_financement, 0, ',', ' ') . ' FCFA',
                'solde_caisse' => number_format($solde_caisse, 0, ',', ' ') . ' FCFA'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
