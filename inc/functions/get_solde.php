<?php
function getSoldeCaisse() {
    global $conn;
    
    try {
        // Utiliser le même calcul que la page d'approvisionnement
        $stmt = $conn->prepare("SELECT
            SUM(CASE WHEN type_transaction = 'approvisionnement' THEN montant
                     WHEN type_transaction = 'paiement' THEN -montant
                     ELSE 0 END) AS solde_caisse
        FROM transactions");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($result['solde_caisse'] ?? 0);
    } catch (Exception $e) {
        writeLog("Erreur lors de la récupération du solde : " . $e->getMessage());
        return 0;
    }
}

/**
 * Fonction pour vérifier si un montant peut être payé avec le solde disponible
 */
function canPayAmount($montant) {
    $solde_actuel = getSoldeCaisse();
    return $solde_actuel >= $montant;
}

/**
 * Fonction pour calculer le montant maximum payable selon le solde caisse
 */
function getMaxPayableAmount($montant_demande) {
    $solde_actuel = getSoldeCaisse();
    return min($montant_demande, $solde_actuel);
}
