<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Correction du Bordereau BORD-20251117-185-6675</h2>";

$numero_bordereau = 'BORD-20251117-185-6675';

try {
    $conn->beginTransaction();
    
    // 1. Récupérer les informations actuelles du bordereau
    $stmt = $conn->prepare("
        SELECT 
            id_bordereau,
            numero_bordereau,
            montant_total,
            COALESCE(montant_payer, 0) as montant_payer_actuel,
            COALESCE(montant_reste, 0) as montant_reste_actuel,
            statut_bordereau
        FROM bordereau 
        WHERE numero_bordereau = ?
    ");
    $stmt->execute([$numero_bordereau]);
    $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordereau) {
        throw new Exception("Bordereau non trouvé");
    }
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>État Actuel du Bordereau</h3>";
    echo "<p><strong>Montant total :</strong> " . number_format($bordereau['montant_total'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Montant payé :</strong> " . number_format($bordereau['montant_payer_actuel'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Reste à payer :</strong> " . number_format($bordereau['montant_reste_actuel'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Statut :</strong> " . $bordereau['statut_bordereau'] . "</p>";
    echo "</div>";
    
    // 2. Calculer le total réel payé selon les reçus
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(montant_paye), 0) as total_paye_recus,
            COUNT(*) as nb_recus
        FROM recus_paiements 
        WHERE numero_document = ? AND type_document = 'bordereau'
    ");
    $stmt->execute([$numero_bordereau]);
    $recus_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_paye_reel = $recus_info['total_paye_recus'];
    $nb_recus = $recus_info['nb_recus'];
    $nouveau_reste = $bordereau['montant_total'] - $total_paye_reel;
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Calculs Basés sur les Reçus</h3>";
    echo "<p><strong>Nombre de reçus :</strong> $nb_recus</p>";
    echo "<p><strong>Total payé (reçus) :</strong> " . number_format($total_paye_reel, 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Nouveau reste calculé :</strong> " . number_format($nouveau_reste, 0, ',', ' ') . " FCFA</p>";
    echo "</div>";
    
    // 3. Vérifier s'il y a une différence
    if ($bordereau['montant_payer_actuel'] != $total_paye_reel || $bordereau['montant_reste_actuel'] != $nouveau_reste) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>⚠️ Incohérence Détectée</h3>";
        echo "<p><strong>Différence montant payé :</strong> " . number_format($total_paye_reel - $bordereau['montant_payer_actuel'], 0, ',', ' ') . " FCFA</p>";
        echo "<p><strong>Différence reste :</strong> " . number_format($nouveau_reste - $bordereau['montant_reste_actuel'], 0, ',', ' ') . " FCFA</p>";
        echo "</div>";
        
        // 4. Appliquer la correction
        $nouveau_statut = ($nouveau_reste <= 0) ? 'soldé' : 'non soldé';
        
        $stmt = $conn->prepare("
            UPDATE bordereau 
            SET montant_payer = ?,
                montant_reste = ?,
                statut_bordereau = ?
            WHERE id_bordereau = ?
        ");
        $stmt->execute([$total_paye_reel, $nouveau_reste, $nouveau_statut, $bordereau['id_bordereau']]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ Correction Appliquée</h3>";
        echo "<p><strong>Nouveau montant payé :</strong> " . number_format($total_paye_reel, 0, ',', ' ') . " FCFA</p>";
        echo "<p><strong>Nouveau reste :</strong> " . number_format($nouveau_reste, 0, ',', ' ') . " FCFA</p>";
        echo "<p><strong>Nouveau statut :</strong> $nouveau_statut</p>";
        echo "</div>";
        
        // Log de la correction
        $log_message = "Correction bordereau $numero_bordereau: montant_payer=$total_paye_reel, montant_reste=$nouveau_reste, statut=$nouveau_statut";
        error_log(date('Y-m-d H:i:s') . " - $log_message\n", 3, '../logs/app.log');
        
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ Aucune Correction Nécessaire</h3>";
        echo "<p>Le bordereau est déjà cohérent avec les reçus de paiement.</p>";
        echo "</div>";
    }
    
    $conn->commit();
    
    // 5. Afficher les détails des reçus
    echo "<h3>Détail des Reçus de Paiement</h3>";
    $stmt = $conn->prepare("
        SELECT 
            numero_recu,
            montant_paye,
            source_paiement,
            numero_cheque,
            nom_caissier,
            date_creation
        FROM recus_paiements 
        WHERE numero_document = ? AND type_document = 'bordereau'
        ORDER BY date_creation ASC
    ");
    $stmt->execute([$numero_bordereau]);
    $recus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recus)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>N° Reçu</th>";
        echo "<th style='padding: 8px;'>Montant</th>";
        echo "<th style='padding: 8px;'>Source</th>";
        echo "<th style='padding: 8px;'>N° Chèque</th>";
        echo "<th style='padding: 8px;'>Caissier</th>";
        echo "<th style='padding: 8px;'>Date</th>";
        echo "</tr>";
        
        foreach ($recus as $recu) {
            $source_text = ($recu['source_paiement'] === 'transactions') ? 'Caisse' : 
                          (($recu['source_paiement'] === 'cheque') ? 'Chèque' : 'Financement');
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($recu['numero_recu']) . "</td>";
            echo "<td style='padding: 8px; text-align: right;'>" . number_format($recu['montant_paye'], 0, ',', ' ') . " FCFA</td>";
            echo "<td style='padding: 8px;'>$source_text</td>";
            echo "<td style='padding: 8px;'>" . ($recu['numero_cheque'] ?? '-') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($recu['nom_caissier']) . "</td>";
            echo "<td style='padding: 8px;'>" . date('d/m/Y H:i', strtotime($recu['date_creation'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Instructions</h3>";
    echo "<p>1. Actualisez la page des bordereaux pour voir les changements</p>";
    echo "<p>2. Vérifiez que le reste à payer est maintenant correct</p>";
    echo "<p>3. Le bordereau devrait maintenant refléter les paiements effectués</p>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Erreur</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
