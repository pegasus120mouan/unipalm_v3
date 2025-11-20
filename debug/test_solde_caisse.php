<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/get_solde.php';

echo "<h2>Test de Cohérence du Solde de Caisse</h2>";

// Méthode 1: Calcul par SUM (page d'approvisionnement)
$stmt1 = $conn->prepare("SELECT
    SUM(CASE WHEN type_transaction = 'approvisionnement' THEN montant
             WHEN type_transaction = 'paiement' THEN -montant
             ELSE 0 END) AS solde_caisse
FROM transactions");
$stmt1->execute();
$result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
$solde_sum = floatval($result1['solde_caisse'] ?? 0);

// Méthode 2: Fonction getSoldeCaisse() (corrigée)
$solde_function = getSoldeCaisse();

// Méthode 3: MAX(solde) (ancienne méthode)
$stmt3 = $conn->prepare("SELECT COALESCE(MAX(solde), 0) as solde FROM transactions");
$stmt3->execute();
$result3 = $stmt3->fetch(PDO::FETCH_ASSOC);
$solde_max = floatval($result3['solde']);

echo "<table border='1' style='border-collapse: collapse; padding: 10px; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Méthode</th>";
echo "<th style='padding: 10px;'>Solde Calculé</th>";
echo "<th style='padding: 10px;'>Statut</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>SUM (Approvisionnement)</strong></td>";
echo "<td style='padding: 10px; text-align: right;'>" . number_format($solde_sum, 0, ',', ' ') . " FCFA</td>";
echo "<td style='padding: 10px; color: green;'>✅ Référence</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>getSoldeCaisse() (Corrigée)</strong></td>";
echo "<td style='padding: 10px; text-align: right;'>" . number_format($solde_function, 0, ',', ' ') . " FCFA</td>";
$status_function = ($solde_function == $solde_sum) ? "✅ Correct" : "❌ Différent";
$color_function = ($solde_function == $solde_sum) ? "green" : "red";
echo "<td style='padding: 10px; color: $color_function;'>$status_function</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>MAX(solde) (Ancienne)</strong></td>";
echo "<td style='padding: 10px; text-align: right;'>" . number_format($solde_max, 0, ',', ' ') . " FCFA</td>";
$status_max = ($solde_max == $solde_sum) ? "✅ Correct" : "❌ Différent";
$color_max = ($solde_max == $solde_sum) ? "green" : "red";
echo "<td style='padding: 10px; color: $color_max;'>$status_max</td>";
echo "</tr>";

echo "</table>";

// Afficher quelques transactions récentes pour debug
echo "<h3>Dernières Transactions (pour debug)</h3>";
$stmt_debug = $conn->prepare("SELECT type_transaction, montant, solde, date_transaction, motifs 
                              FROM transactions 
                              ORDER BY date_transaction DESC 
                              LIMIT 10");
$stmt_debug->execute();
$transactions = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='padding: 5px;'>Date</th>";
echo "<th style='padding: 5px;'>Type</th>";
echo "<th style='padding: 5px;'>Montant</th>";
echo "<th style='padding: 5px;'>Solde Enregistré</th>";
echo "<th style='padding: 5px;'>Motifs</th>";
echo "</tr>";

foreach ($transactions as $t) {
    echo "<tr>";
    echo "<td style='padding: 5px;'>" . date('d/m/Y H:i', strtotime($t['date_transaction'])) . "</td>";
    echo "<td style='padding: 5px;'>" . htmlspecialchars($t['type_transaction']) . "</td>";
    echo "<td style='padding: 5px; text-align: right;'>" . number_format($t['montant'], 0, ',', ' ') . "</td>";
    echo "<td style='padding: 5px; text-align: right;'>" . number_format($t['solde'], 0, ',', ' ') . "</td>";
    echo "<td style='padding: 5px;'>" . htmlspecialchars($t['motifs']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Calcul manuel pour vérification
echo "<h3>Vérification Manuelle</h3>";
$stmt_manual = $conn->prepare("SELECT 
    SUM(CASE WHEN type_transaction = 'approvisionnement' THEN montant ELSE 0 END) as total_appro,
    SUM(CASE WHEN type_transaction = 'paiement' THEN montant ELSE 0 END) as total_paiements,
    COUNT(*) as total_transactions
FROM transactions");
$stmt_manual->execute();
$manual = $stmt_manual->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Total Approvisionnements :</strong> " . number_format($manual['total_appro'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Total Paiements :</strong> " . number_format($manual['total_paiements'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Solde Calculé :</strong> " . number_format($manual['total_appro'] - $manual['total_paiements'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Nombre de Transactions :</strong> " . $manual['total_transactions'] . "</p>";
?>
