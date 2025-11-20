<?php
session_start();
require_once '../inc/functions/connexion.php';

// Vérifier les permissions (optionnel)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Correction Bordereau</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-10'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h2 class='mb-0'>🔧 Correction du Bordereau BORD-20251117-185-6675</h2>";
echo "</div>";
echo "<div class='card-body'>";

$numero_bordereau = 'BORD-20251117-185-6675';

try {
    $conn->beginTransaction();
    
    // 1. Récupérer l'état actuel du bordereau
    $stmt = $conn->prepare("
        SELECT 
            id_bordereau,
            numero_bordereau,
            montant_total,
            COALESCE(montant_payer, 0) as montant_payer,
            COALESCE(montant_reste, 0) as montant_reste,
            statut_bordereau
        FROM bordereau 
        WHERE numero_bordereau = ?
    ");
    $stmt->execute([$numero_bordereau]);
    $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordereau) {
        throw new Exception("Bordereau non trouvé");
    }
    
    echo "<div class='alert alert-info'>";
    echo "<h4>📊 État Actuel du Bordereau</h4>";
    echo "<p><strong>Montant total :</strong> " . number_format($bordereau['montant_total'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Montant payé (bordereau) :</strong> " . number_format($bordereau['montant_payer'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Reste à payer (bordereau) :</strong> " . number_format($bordereau['montant_reste'], 0, ',', ' ') . " FCFA</p>";
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
    $nouveau_statut = ($nouveau_reste <= 0) ? 'soldé' : 'non soldé';
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>🧮 Calculs Basés sur les Reçus</h4>";
    echo "<p><strong>Nombre de reçus :</strong> $nb_recus</p>";
    echo "<p><strong>Total payé (reçus) :</strong> " . number_format($total_paye_reel, 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Nouveau reste calculé :</strong> " . number_format($nouveau_reste, 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Nouveau statut :</strong> $nouveau_statut</p>";
    echo "</div>";
    
    // 3. Vérifier s'il y a une différence
    $correction_necessaire = ($bordereau['montant_payer'] != $total_paye_reel || $bordereau['montant_reste'] != $nouveau_reste);
    
    if ($correction_necessaire) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>⚠️ Incohérence Détectée</h4>";
        echo "<p><strong>Différence montant payé :</strong> " . number_format($total_paye_reel - $bordereau['montant_payer'], 0, ',', ' ') . " FCFA</p>";
        echo "<p><strong>Différence reste :</strong> " . number_format($nouveau_reste - $bordereau['montant_reste'], 0, ',', ' ') . " FCFA</p>";
        echo "</div>";
        
        // 4. Appliquer la correction
        $stmt = $conn->prepare("
            UPDATE bordereau 
            SET montant_payer = ?,
                montant_reste = ?,
                statut_bordereau = ?,
                date_paie = NOW()
            WHERE id_bordereau = ?
        ");
        $result = $stmt->execute([$total_paye_reel, $nouveau_reste, $nouveau_statut, $bordereau['id_bordereau']]);
        
        if ($result) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Correction Appliquée avec Succès !</h4>";
            echo "<p><strong>Nouveau montant payé :</strong> " . number_format($total_paye_reel, 0, ',', ' ') . " FCFA</p>";
            echo "<p><strong>Nouveau reste :</strong> " . number_format($nouveau_reste, 0, ',', ' ') . " FCFA</p>";
            echo "<p><strong>Nouveau statut :</strong> $nouveau_statut</p>";
            echo "</div>";
            
        } else {
            throw new Exception("Erreur lors de la mise à jour du bordereau");
        }
        
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Aucune Correction Nécessaire</h4>";
        echo "<p>Le bordereau est déjà cohérent avec les reçus de paiement.</p>";
        echo "</div>";
    }
    
    $conn->commit();
    
    // 5. Vérification finale
    $stmt = $conn->prepare("
        SELECT 
            numero_bordereau,
            montant_total,
            COALESCE(montant_payer, 0) as montant_payer,
            COALESCE(montant_reste, 0) as montant_reste,
            statut_bordereau,
            date_paie
        FROM bordereau 
        WHERE numero_bordereau = ?
    ");
    $stmt->execute([$numero_bordereau]);
    $bordereau_final = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>";
    echo "<h4>🔍 État Final du Bordereau</h4>";
    echo "<p><strong>Montant total :</strong> " . number_format($bordereau_final['montant_total'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Montant payé :</strong> " . number_format($bordereau_final['montant_payer'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Reste à payer :</strong> " . number_format($bordereau_final['montant_reste'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Statut :</strong> " . $bordereau_final['statut_bordereau'] . "</p>";
    echo "<p><strong>Date paie :</strong> " . ($bordereau_final['date_paie'] ?? 'Non définie') . "</p>";
    echo "</div>";
    
    // 6. Afficher les reçus pour vérification
    echo "<div class='mt-4'>";
    echo "<h4>📄 Détail des Reçus de Paiement</h4>";
    
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
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-bordered'>";
        echo "<thead class='table-dark'>";
        echo "<tr>";
        echo "<th>N° Reçu</th>";
        echo "<th>Montant</th>";
        echo "<th>Source</th>";
        echo "<th>N° Chèque</th>";
        echo "<th>Caissier</th>";
        echo "<th>Date</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        $total_affiche = 0;
        foreach ($recus as $recu) {
            $total_affiche += $recu['montant_paye'];
            $source_text = ($recu['source_paiement'] === 'transactions') ? 'Caisse' : 
                          (($recu['source_paiement'] === 'cheque') ? 'Chèque' : 'Financement');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($recu['numero_recu']) . "</td>";
            echo "<td class='text-end'>" . number_format($recu['montant_paye'], 0, ',', ' ') . " FCFA</td>";
            echo "<td><span class='badge bg-primary'>$source_text</span></td>";
            echo "<td>" . ($recu['numero_cheque'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($recu['nom_caissier']) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($recu['date_creation'])) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr class='table-warning'>";
        echo "<td><strong>TOTAL</strong></td>";
        echo "<td class='text-end'><strong>" . number_format($total_affiche, 0, ',', ' ') . " FCFA</strong></td>";
        echo "<td colspan='4'></td>";
        echo "</tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>Aucun reçu trouvé.</div>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-success mt-4'>";
    echo "<h4>🎉 Correction Terminée</h4>";
    echo "<p><strong>Instructions :</strong></p>";
    echo "<ol>";
    echo "<li>Actualisez la page des bordereaux</li>";
    echo "<li>Le reste à payer devrait maintenant être correct</li>";
    echo "<li>Le modal de paiement affichera les bonnes valeurs après actualisation</li>";
    echo "</ol>";
    echo "<div class='mt-3'>";
    echo "<a href='bordereaux.php' class='btn btn-primary me-2'>📋 Aller aux Bordereaux</a>";
    echo "<a href='compte_agent_detail.php?id=" . ($bordereau['id_agent'] ?? '1') . "' class='btn btn-secondary'>👤 Compte Agent</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>"; // card-body
echo "</div>"; // card
echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body>";
echo "</html>";
?>
