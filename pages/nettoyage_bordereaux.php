<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Nettoyage des associations de bordereaux incorrectes</h2>";

// 1. Identifier les tickets mal associés
$sql_check = "SELECT 
    t.id_ticket,
    t.numero_ticket,
    t.numero_bordereau,
    t.prix_unitaire,
    t.date_validation_boss,
    u.nom_usine
FROM tickets t
INNER JOIN usines u ON t.id_usine = u.id_usine
WHERE t.numero_bordereau IS NOT NULL 
AND (t.date_validation_boss IS NULL OR t.prix_unitaire <= 0)
ORDER BY t.numero_bordereau, t.numero_ticket";

$stmt_check = $conn->prepare($sql_check);
$stmt_check->execute();
$tickets_incorrects = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

if (count($tickets_incorrects) > 0) {
    echo "<h3>Tickets incorrectement associés trouvés :</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>N° Ticket</th><th>Usine</th><th>N° Bordereau</th><th>Prix Unitaire</th><th>Date Validation</th><th>Problème</th>";
    echo "</tr>";
    
    $tickets_a_nettoyer = [];
    
    foreach ($tickets_incorrects as $ticket) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ticket['numero_ticket']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['nom_usine']) . "</td>";
        echo "<td>" . htmlspecialchars($ticket['numero_bordereau']) . "</td>";
        echo "<td>" . ($ticket['prix_unitaire'] ?? '0.00') . "</td>";
        echo "<td>" . ($ticket['date_validation_boss'] ?? 'NULL') . "</td>";
        
        $problemes = [];
        if ($ticket['date_validation_boss'] === null) {
            $problemes[] = "Non validé";
        }
        if ($ticket['prix_unitaire'] <= 0) {
            $problemes[] = "Prix = 0";
        }
        
        echo "<td style='color: red;'>" . implode(", ", $problemes) . "</td>";
        echo "</tr>";
        
        $tickets_a_nettoyer[] = $ticket['id_ticket'];
    }
    echo "</table>";
    
    echo "<p><strong>Total : " . count($tickets_incorrects) . " ticket(s) à nettoyer</strong></p>";
    
    // Formulaire pour confirmer le nettoyage
    echo "<form method='post'>";
    echo "<input type='hidden' name='tickets_ids' value='" . implode(',', $tickets_a_nettoyer) . "'>";
    echo "<button type='submit' name='nettoyer' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🧹 Nettoyer ces associations incorrectes";
    echo "</button>";
    echo "</form>";
    
} else {
    echo "<p style='color: green;'>✅ Aucun ticket incorrectement associé trouvé !</p>";
}

// Traitement du nettoyage
if (isset($_POST['nettoyer']) && isset($_POST['tickets_ids'])) {
    $tickets_ids = explode(',', $_POST['tickets_ids']);
    
    if (!empty($tickets_ids)) {
        try {
            $conn->beginTransaction();
            
            // Supprimer les associations incorrectes
            $placeholders = str_repeat('?,', count($tickets_ids) - 1) . '?';
            $sql_clean = "UPDATE tickets 
                         SET numero_bordereau = NULL, updated_at = NOW() 
                         WHERE id_ticket IN ($placeholders)
                         AND (date_validation_boss IS NULL OR prix_unitaire <= 0)";
            
            $stmt_clean = $conn->prepare($sql_clean);
            $result = $stmt_clean->execute($tickets_ids);
            
            if ($result) {
                $affected_rows = $stmt_clean->rowCount();
                $conn->commit();
                
                echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>✅ Nettoyage terminé !</h3>";
                echo "<p>$affected_rows ticket(s) ont été dissociés de leurs bordereaux.</p>";
                echo "<p>Ces tickets peuvent maintenant être validés correctement avant d'être réassociés.</p>";
                echo "</div>";
                
                // Recharger la page pour voir les résultats
                echo "<script>setTimeout(function(){ location.reload(); }, 3000);</script>";
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>❌ Erreur lors du nettoyage</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<h3>Statistiques des bordereaux :</h3>";

// Statistiques
$sql_stats = "SELECT 
    COUNT(CASE WHEN numero_bordereau IS NOT NULL THEN 1 END) as tickets_avec_bordereau,
    COUNT(CASE WHEN numero_bordereau IS NOT NULL AND date_validation_boss IS NOT NULL AND prix_unitaire > 0 THEN 1 END) as tickets_corrects,
    COUNT(CASE WHEN numero_bordereau IS NOT NULL AND (date_validation_boss IS NULL OR prix_unitaire <= 0) THEN 1 END) as tickets_incorrects
FROM tickets";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

echo "<ul>";
echo "<li><strong>Tickets avec bordereau :</strong> " . $stats['tickets_avec_bordereau'] . "</li>";
echo "<li><strong>Tickets correctement associés :</strong> " . $stats['tickets_corrects'] . " ✅</li>";
echo "<li><strong>Tickets incorrectement associés :</strong> " . $stats['tickets_incorrects'] . " ❌</li>";
echo "</ul>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
