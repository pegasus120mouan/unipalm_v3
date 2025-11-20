<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Correction de Tous les Bordereaux Incohérents</h2>";

try {
    // 1. Identifier tous les bordereaux avec des incohérences
    $stmt = $conn->prepare("
        SELECT 
            b.id_bordereau,
            b.numero_bordereau,
            b.montant_total,
            COALESCE(b.montant_payer, 0) as montant_payer_bordereau,
            COALESCE(b.montant_reste, 0) as montant_reste_bordereau,
            b.statut_bordereau,
            COALESCE(SUM(r.montant_paye), 0) as total_paye_recus,
            COUNT(r.id) as nb_recus
        FROM bordereau b
        LEFT JOIN recus_paiements r ON b.numero_bordereau = r.numero_document 
                                    AND r.type_document = 'bordereau'
        GROUP BY b.id_bordereau, b.numero_bordereau, b.montant_total, 
                 b.montant_payer, b.montant_reste, b.statut_bordereau
        HAVING (COALESCE(b.montant_payer, 0) != COALESCE(SUM(r.montant_paye), 0))
            OR (COALESCE(b.montant_reste, 0) != (b.montant_total - COALESCE(SUM(r.montant_paye), 0)))
        ORDER BY b.numero_bordereau DESC
    ");
    $stmt->execute();
    $bordereaux_incoherents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bordereaux_incoherents)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ Aucun Problème Détecté</h3>";
        echo "<p>Tous les bordereaux sont cohérents avec leurs reçus de paiement.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>⚠️ Bordereaux Incohérents Détectés</h3>";
        echo "<p><strong>Nombre de bordereaux à corriger :</strong> " . count($bordereaux_incoherents) . "</p>";
        echo "</div>";
        
        // Afficher la liste des bordereaux incohérents
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Numéro Bordereau</th>";
        echo "<th style='padding: 8px;'>Montant Total</th>";
        echo "<th style='padding: 8px;'>Payé (Bordereau)</th>";
        echo "<th style='padding: 8px;'>Payé (Reçus)</th>";
        echo "<th style='padding: 8px;'>Reste (Bordereau)</th>";
        echo "<th style='padding: 8px;'>Reste (Calculé)</th>";
        echo "<th style='padding: 8px;'>Nb Reçus</th>";
        echo "<th style='padding: 8px;'>Action</th>";
        echo "</tr>";
        
        foreach ($bordereaux_incoherents as $b) {
            $reste_calcule = $b['montant_total'] - $b['total_paye_recus'];
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($b['numero_bordereau']) . "</td>";
            echo "<td style='padding: 8px; text-align: right;'>" . number_format($b['montant_total'], 0, ',', ' ') . "</td>";
            echo "<td style='padding: 8px; text-align: right; " . ($b['montant_payer_bordereau'] != $b['total_paye_recus'] ? 'color: red;' : '') . "'>" . number_format($b['montant_payer_bordereau'], 0, ',', ' ') . "</td>";
            echo "<td style='padding: 8px; text-align: right; font-weight: bold;'>" . number_format($b['total_paye_recus'], 0, ',', ' ') . "</td>";
            echo "<td style='padding: 8px; text-align: right; " . ($b['montant_reste_bordereau'] != $reste_calcule ? 'color: red;' : '') . "'>" . number_format($b['montant_reste_bordereau'], 0, ',', ' ') . "</td>";
            echo "<td style='padding: 8px; text-align: right; font-weight: bold;'>" . number_format($reste_calcule, 0, ',', ' ') . "</td>";
            echo "<td style='padding: 8px; text-align: center;'>" . $b['nb_recus'] . "</td>";
            echo "<td style='padding: 8px;'>";
            echo "<button onclick='corrigerBordereau(" . $b['id_bordereau'] . ", " . $b['total_paye_recus'] . ", " . $reste_calcule . ")' ";
            echo "style='background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;'>Corriger</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Bouton pour corriger tous
        echo "<div style='margin: 20px 0;'>";
        echo "<button onclick='corrigerTous()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;'>";
        echo "Corriger Tous les Bordereaux";
        echo "</button>";
        echo "<span id='status' style='margin-left: 10px;'></span>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Erreur</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Traitement AJAX pour les corrections
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'corriger_bordereau') {
            $id_bordereau = (int)$_POST['id_bordereau'];
            $nouveau_montant_payer = (float)$_POST['nouveau_montant_payer'];
            $nouveau_montant_reste = (float)$_POST['nouveau_montant_reste'];
            
            $nouveau_statut = ($nouveau_montant_reste <= 0) ? 'soldé' : 'non soldé';
            
            $stmt = $conn->prepare("
                UPDATE bordereau 
                SET montant_payer = ?,
                    montant_reste = ?,
                    statut_bordereau = ?
                WHERE id_bordereau = ?
            ");
            $stmt->execute([$nouveau_montant_payer, $nouveau_montant_reste, $nouveau_statut, $id_bordereau]);
            
            echo json_encode(['success' => true, 'message' => 'Bordereau corrigé avec succès']);
            
        } elseif ($_POST['action'] === 'corriger_tous') {
            $conn->beginTransaction();
            
            // Récupérer tous les bordereaux incohérents
            $stmt = $conn->prepare("
                SELECT 
                    b.id_bordereau,
                    b.numero_bordereau,
                    b.montant_total,
                    COALESCE(SUM(r.montant_paye), 0) as total_paye_recus
                FROM bordereau b
                LEFT JOIN recus_paiements r ON b.numero_bordereau = r.numero_document 
                                            AND r.type_document = 'bordereau'
                GROUP BY b.id_bordereau, b.numero_bordereau, b.montant_total, b.montant_payer, b.montant_reste
                HAVING (COALESCE(b.montant_payer, 0) != COALESCE(SUM(r.montant_paye), 0))
                    OR (COALESCE(b.montant_reste, 0) != (b.montant_total - COALESCE(SUM(r.montant_paye), 0)))
            ");
            $stmt->execute();
            $bordereaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $corriges = 0;
            foreach ($bordereaux as $b) {
                $nouveau_montant_payer = $b['total_paye_recus'];
                $nouveau_montant_reste = $b['montant_total'] - $b['total_paye_recus'];
                $nouveau_statut = ($nouveau_montant_reste <= 0) ? 'soldé' : 'non soldé';
                
                $stmt = $conn->prepare("
                    UPDATE bordereau 
                    SET montant_payer = ?,
                        montant_reste = ?,
                        statut_bordereau = ?
                    WHERE id_bordereau = ?
                ");
                $stmt->execute([$nouveau_montant_payer, $nouveau_montant_reste, $nouveau_statut, $b['id_bordereau']]);
                $corriges++;
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "$corriges bordereaux corrigés avec succès"]);
        }
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<script>
function corrigerBordereau(idBordereau, nouveauMontantPayer, nouveauMontantReste) {
    if (!confirm('Êtes-vous sûr de vouloir corriger ce bordereau ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'corriger_bordereau');
    formData.append('id_bordereau', idBordereau);
    formData.append('nouveau_montant_payer', nouveauMontantPayer);
    formData.append('nouveau_montant_reste', nouveauMontantReste);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bordereau corrigé avec succès !');
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur de communication : ' + error);
    });
}

function corrigerTous() {
    if (!confirm('Êtes-vous sûr de vouloir corriger TOUS les bordereaux incohérents ?')) {
        return;
    }
    
    document.getElementById('status').innerHTML = '<span style="color: orange;">Correction en cours...</span>';
    
    const formData = new FormData();
    formData.append('action', 'corriger_tous');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('status').innerHTML = '<span style="color: green;">✅ ' + data.message + '</span>';
            setTimeout(() => location.reload(), 2000);
        } else {
            document.getElementById('status').innerHTML = '<span style="color: red;">❌ Erreur : ' + data.message + '</span>';
        }
    })
    .catch(error => {
        document.getElementById('status').innerHTML = '<span style="color: red;">❌ Erreur de communication : ' + error + '</span>';
    });
}
</script>
