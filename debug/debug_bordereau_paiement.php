<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Debug Paiement Bordereau</h2>";

// Récupérer le bordereau BORD-20251117-185-6675
$numero_bordereau = 'BORD-20251117-185-6675';

$stmt = $conn->prepare("
    SELECT 
        b.*,
        CONCAT(a.nom, ' ', a.prenom) as agent_nom,
        a.contact as agent_contact
    FROM bordereau b
    LEFT JOIN agents a ON b.id_agent = a.id_agent
    WHERE b.numero_bordereau = ?
");
$stmt->execute([$numero_bordereau]);
$bordereau = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bordereau) {
    echo "<p style='color: red;'>Bordereau $numero_bordereau non trouvé.</p>";
    exit;
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Informations du Bordereau</h3>";
echo "<p><strong>Numéro :</strong> " . htmlspecialchars($bordereau['numero_bordereau']) . "</p>";
echo "<p><strong>ID :</strong> " . $bordereau['id_bordereau'] . "</p>";
echo "<p><strong>Agent :</strong> " . htmlspecialchars($bordereau['agent_nom']) . "</p>";
echo "<p><strong>Montant total :</strong> " . number_format($bordereau['montant_total'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Montant payé :</strong> " . number_format($bordereau['montant_payer'] ?? 0, 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Montant reste :</strong> " . number_format($bordereau['montant_reste'] ?? 0, 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Date paie :</strong> " . ($bordereau['date_paie'] ?? 'Non payé') . "</p>";
echo "<p><strong>Statut :</strong> " . $bordereau['statut_bordereau'] . "</p>";
echo "</div>";

// Vérifier les reçus de paiement pour ce bordereau
echo "<h3>Reçus de Paiement pour ce Bordereau</h3>";
$stmt = $conn->prepare("
    SELECT 
        numero_recu,
        montant_paye,
        source_paiement,
        numero_cheque,
        date_creation,
        nom_caissier
    FROM recus_paiements 
    WHERE numero_document = ? AND type_document = 'bordereau'
    ORDER BY date_creation DESC
");
$stmt->execute([$numero_bordereau]);
$recus = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recus)) {
    echo "<p style='color: orange;'>Aucun reçu de paiement trouvé pour ce bordereau.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>N° Reçu</th>";
    echo "<th style='padding: 10px;'>Montant</th>";
    echo "<th style='padding: 10px;'>Source</th>";
    echo "<th style='padding: 10px;'>N° Chèque</th>";
    echo "<th style='padding: 10px;'>Caissier</th>";
    echo "<th style='padding: 10px;'>Date</th>";
    echo "</tr>";
    
    $total_paye_recus = 0;
    foreach ($recus as $recu) {
        $total_paye_recus += $recu['montant_paye'];
        $source_text = ($recu['source_paiement'] === 'transactions') ? 'Caisse' : 
                      (($recu['source_paiement'] === 'cheque') ? 'Chèque' : 'Financement');
        
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($recu['numero_recu']) . "</td>";
        echo "<td style='padding: 10px; text-align: right;'>" . number_format($recu['montant_paye'], 0, ',', ' ') . " FCFA</td>";
        echo "<td style='padding: 10px;'>$source_text</td>";
        echo "<td style='padding: 10px;'>" . ($recu['numero_cheque'] ?? '-') . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($recu['nom_caissier']) . "</td>";
        echo "<td style='padding: 10px;'>" . date('d/m/Y H:i', strtotime($recu['date_creation'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>Totaux des Reçus</h4>";
    echo "<p><strong>Total payé selon les reçus :</strong> " . number_format($total_paye_recus, 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Total payé selon le bordereau :</strong> " . number_format($bordereau['montant_payer'] ?? 0, 0, ',', ' ') . " FCFA</p>";
    
    if ($total_paye_recus != ($bordereau['montant_payer'] ?? 0)) {
        echo "<p style='color: red;'><strong>⚠️ INCOHÉRENCE DÉTECTÉE !</strong></p>";
        echo "<p>Le total des reçus ne correspond pas au montant payé du bordereau.</p>";
    } else {
        echo "<p style='color: green;'><strong>✅ Cohérence OK</strong></p>";
    }
    echo "</div>";
}

// Calculer ce que devrait être le montant restant
$montant_total = $bordereau['montant_total'];
$montant_paye_reel = $total_paye_recus ?? 0;
$reste_calcule = $montant_total - $montant_paye_reel;

echo "<h3>Analyse du Problème</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Calculs Attendus</h4>";
echo "<p><strong>Montant total :</strong> " . number_format($montant_total, 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Total payé (reçus) :</strong> " . number_format($montant_paye_reel, 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Reste calculé :</strong> " . number_format($reste_calcule, 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Reste dans la base :</strong> " . number_format($bordereau['montant_reste'] ?? 0, 0, ',', ' ') . " FCFA</p>";

if ($reste_calcule != ($bordereau['montant_reste'] ?? 0)) {
    echo "<p style='color: red;'><strong>🔥 PROBLÈME IDENTIFIÉ !</strong></p>";
    echo "<p>Le bordereau n'a pas été mis à jour correctement lors du dernier paiement.</p>";
    
    // Proposer une correction
    echo "<h4>Correction Proposée</h4>";
    echo "<p>Exécuter cette requête SQL pour corriger :</p>";
    echo "<code style='background: #f8f9fa; padding: 10px; display: block; margin: 10px 0;'>";
    echo "UPDATE bordereau SET ";
    echo "montant_payer = " . $montant_paye_reel . ", ";
    echo "montant_reste = " . $reste_calcule . " ";
    echo "WHERE id_bordereau = " . $bordereau['id_bordereau'] . ";";
    echo "</code>";
    
    // Bouton pour appliquer la correction
    echo "<form method='post' style='margin: 20px 0;'>";
    echo "<input type='hidden' name='corriger_bordereau' value='1'>";
    echo "<input type='hidden' name='id_bordereau' value='" . $bordereau['id_bordereau'] . "'>";
    echo "<input type='hidden' name='nouveau_montant_payer' value='$montant_paye_reel'>";
    echo "<input type='hidden' name='nouveau_montant_reste' value='$reste_calcule'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "Corriger le Bordereau";
    echo "</button>";
    echo "</form>";
} else {
    echo "<p style='color: green;'><strong>✅ Pas de problème détecté</strong></p>";
}
echo "</div>";

// Traitement de la correction
if (isset($_POST['corriger_bordereau'])) {
    try {
        $id_bordereau = $_POST['id_bordereau'];
        $nouveau_montant_payer = $_POST['nouveau_montant_payer'];
        $nouveau_montant_reste = $_POST['nouveau_montant_reste'];
        
        $stmt = $conn->prepare("
            UPDATE bordereau 
            SET montant_payer = ?, 
                montant_reste = ?,
                statut_bordereau = CASE 
                    WHEN ? <= 0 THEN 'soldé' 
                    ELSE 'non soldé' 
                END
            WHERE id_bordereau = ?
        ");
        $stmt->execute([$nouveau_montant_payer, $nouveau_montant_reste, $nouveau_montant_reste, $id_bordereau]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>✅ Correction Appliquée</h4>";
        echo "<p>Le bordereau a été mis à jour avec succès !</p>";
        echo "<p><a href='" . $_SERVER['PHP_SELF'] . "' style='color: #007bff;'>Actualiser la page pour voir les changements</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>❌ Erreur lors de la correction</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// Vérifier les logs récents
echo "<h3>Logs Récents de Paiement</h3>";
$log_file = '../logs/app.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", $logs);
    $recent_logs = array_slice($lines, -50); // 50 dernières lignes
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;'>";
    foreach ($recent_logs as $line) {
        if (strpos($line, $numero_bordereau) !== false || strpos($line, 'save_paiement_agent') !== false) {
            echo "<div style='color: #007bff; margin: 2px 0;'>" . htmlspecialchars($line) . "</div>";
        }
    }
    echo "</div>";
} else {
    echo "<p style='color: orange;'>Fichier de log non trouvé.</p>";
}
?>
