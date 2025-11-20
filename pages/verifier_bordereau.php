<?php
session_start();
require_once '../inc/functions/connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Vérification Bordereau</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-10'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-warning text-dark'>";
echo "<h2 class='mb-0'>🔍 Vérification État Bordereau</h2>";
echo "</div>";
echo "<div class='card-body'>";

$numero_bordereau = 'BORD-20251117-185-6675';

try {
    // 1. Vérifier l'état actuel du bordereau en base
    echo "<h4>📊 État en Base de Données</h4>";
    $stmt = $conn->prepare("
        SELECT 
            id_bordereau,
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
    $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordereau) {
        throw new Exception("Bordereau non trouvé");
    }
    
    echo "<div class='alert alert-info'>";
    echo "<p><strong>Montant total :</strong> " . number_format($bordereau['montant_total'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Montant payé :</strong> " . number_format($bordereau['montant_payer'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Reste à payer :</strong> " . number_format($bordereau['montant_reste'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Statut :</strong> " . $bordereau['statut_bordereau'] . "</p>";
    echo "<p><strong>Date paie :</strong> " . ($bordereau['date_paie'] ?? 'NULL') . "</p>";
    echo "</div>";
    
    // 2. Vérifier les reçus
    echo "<h4>📄 Reçus de Paiement</h4>";
    $stmt = $conn->prepare("
        SELECT 
            numero_recu,
            montant_paye,
            source_paiement,
            numero_cheque,
            date_creation
        FROM recus_paiements 
        WHERE numero_document = ? AND type_document = 'bordereau'
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$numero_bordereau]);
    $recus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_recus = 0;
    if (!empty($recus)) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>N° Reçu</th><th>Montant</th><th>Source</th><th>N° Chèque</th><th>Date</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($recus as $recu) {
            $total_recus += $recu['montant_paye'];
            $source_text = ($recu['source_paiement'] === 'transactions') ? 'Caisse' : 
                          (($recu['source_paiement'] === 'cheque') ? 'Chèque' : 'Financement');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($recu['numero_recu']) . "</td>";
            echo "<td>" . number_format($recu['montant_paye'], 0, ',', ' ') . " FCFA</td>";
            echo "<td>$source_text</td>";
            echo "<td>" . ($recu['numero_cheque'] ?? '-') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($recu['date_creation'])) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr class='table-warning'>";
        echo "<td><strong>TOTAL</strong></td>";
        echo "<td><strong>" . number_format($total_recus, 0, ',', ' ') . " FCFA</strong></td>";
        echo "<td colspan='3'></td>";
        echo "</tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
    
    // 3. Comparaison et correction si nécessaire
    $reste_calcule = $bordereau['montant_total'] - $total_recus;
    
    echo "<h4>🔄 Comparaison</h4>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='alert alert-secondary'>";
    echo "<h5>Selon le Bordereau</h5>";
    echo "<p>Montant payé : " . number_format($bordereau['montant_payer'], 0, ',', ' ') . " FCFA</p>";
    echo "<p>Reste : " . number_format($bordereau['montant_reste'], 0, ',', ' ') . " FCFA</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='alert alert-primary'>";
    echo "<h5>Selon les Reçus</h5>";
    echo "<p>Montant payé : " . number_format($total_recus, 0, ',', ' ') . " FCFA</p>";
    echo "<p>Reste : " . number_format($reste_calcule, 0, ',', ' ') . " FCFA</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // 4. Correction forcée si nécessaire
    if ($bordereau['montant_payer'] != $total_recus || $bordereau['montant_reste'] != $reste_calcule) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Incohérence Détectée !</h4>";
        echo "<p>Le bordereau n'est pas synchronisé avec les reçus.</p>";
        echo "</div>";
        
        if (isset($_POST['forcer_correction'])) {
            $nouveau_statut = ($reste_calcule <= 0) ? 'soldé' : 'non soldé';
            
            $stmt = $conn->prepare("
                UPDATE bordereau 
                SET montant_payer = ?,
                    montant_reste = ?,
                    statut_bordereau = ?,
                    date_paie = NOW()
                WHERE numero_bordereau = ?
            ");
            $result = $stmt->execute([$total_recus, $reste_calcule, $nouveau_statut, $numero_bordereau]);
            
            if ($result) {
                echo "<div class='alert alert-success'>";
                echo "<h4>✅ Correction Forcée Appliquée !</h4>";
                echo "<p>Le bordereau a été mis à jour avec les valeurs des reçus.</p>";
                echo "<p><a href='bordereaux.php' class='btn btn-primary'>Vérifier sur la page des bordereaux</a></p>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger'>Erreur lors de la correction.</div>";
            }
        } else {
            echo "<form method='post'>";
            echo "<button type='submit' name='forcer_correction' class='btn btn-danger'>🔧 Forcer la Correction</button>";
            echo "</form>";
        }
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Données Cohérentes</h4>";
        echo "<p>Le bordereau est synchronisé avec les reçus.</p>";
        echo "</div>";
    }
    
    // 5. Vérifier la requête utilisée par la page bordereaux
    echo "<h4>🔍 Test de la Requête Bordereaux</h4>";
    $stmt = $conn->prepare("
        SELECT 
            b.numero_bordereau,
            b.montant_total,
            COALESCE(b.montant_payer, 0) as montant_payer_bdd,
            COALESCE(b.montant_reste, 0) as montant_reste_bdd,
            b.statut_bordereau,
            (SELECT COALESCE(SUM(r.montant_paye), 0) 
             FROM recus_paiements r 
             WHERE r.numero_document = b.numero_bordereau 
             AND r.type_document = 'bordereau') as total_recus_calcule
        FROM bordereau b
        WHERE b.numero_bordereau = ?
    ");
    $stmt->execute([$numero_bordereau]);
    $test_requete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'>";
    echo "<p><strong>Montant payé (BDD) :</strong> " . number_format($test_requete['montant_payer_bdd'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Total reçus (calculé) :</strong> " . number_format($test_requete['total_recus_calcule'], 0, ',', ' ') . " FCFA</p>";
    echo "<p><strong>Reste (BDD) :</strong> " . number_format($test_requete['montant_reste_bdd'], 0, ',', ' ') . " FCFA</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='mt-3'>";
echo "<a href='bordereaux.php' class='btn btn-primary me-2'>📋 Page Bordereaux</a>";
echo "<a href='corriger_bordereau.php' class='btn btn-secondary'>🔧 Script Correction</a>";
echo "</div>";

echo "</div>"; // card-body
echo "</div>"; // card
echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "</body>";
echo "</html>";
?>
