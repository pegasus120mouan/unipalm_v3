<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Test Source de Paiement dans les Reçus</h2>";

// Vérifier les derniers reçus avec leur source de paiement
$stmt = $conn->prepare("
    SELECT 
        numero_recu,
        type_document,
        numero_document,
        montant_paye,
        source_paiement,
        nom_agent,
        date_creation
    FROM recus_paiements 
    ORDER BY date_creation DESC 
    LIMIT 10
");
$stmt->execute();
$recus = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recus)) {
    echo "<p style='color: orange;'>Aucun reçu trouvé dans la base de données.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; padding: 10px; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>N° Reçu</th>";
    echo "<th style='padding: 10px;'>Type</th>";
    echo "<th style='padding: 10px;'>N° Document</th>";
    echo "<th style='padding: 10px;'>Montant</th>";
    echo "<th style='padding: 10px;'>Source Paiement</th>";
    echo "<th style='padding: 10px;'>Agent</th>";
    echo "<th style='padding: 10px;'>Date</th>";
    echo "<th style='padding: 10px;'>Action</th>";
    echo "</tr>";

    foreach ($recus as $recu) {
        $source_text = ($recu['source_paiement'] === 'transactions') ? 'Caisse' : 'Financement';
        $source_color = ($recu['source_paiement'] === 'transactions') ? '#28a745' : '#007bff';
        
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($recu['numero_recu']) . "</td>";
        echo "<td style='padding: 10px;'>" . ucfirst($recu['type_document']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($recu['numero_document']) . "</td>";
        echo "<td style='padding: 10px; text-align: right;'>" . number_format($recu['montant_paye'], 0, ',', ' ') . " FCFA</td>";
        echo "<td style='padding: 10px; color: $source_color; font-weight: bold;'>$source_text</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($recu['nom_agent']) . "</td>";
        echo "<td style='padding: 10px;'>" . date('d/m/Y H:i', strtotime($recu['date_creation'])) . "</td>";
        
        // Bouton pour tester le PDF
        if ($recu['type_document'] === 'ticket') {
            $pdf_url = "recu_paiement_pdf.php?id_ticket=" . $recu['numero_document'] . "&reimprimer=1";
        } else {
            $pdf_url = "recu_paiement_pdf.php?id_bordereau=" . $recu['numero_document'] . "&reimprimer=1";
        }
        
        echo "<td style='padding: 10px;'>";
        echo "<a href='$pdf_url' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;'>Voir PDF</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Vérifier la structure de la table recus_paiements
echo "<h3>Structure de la table recus_paiements</h3>";
$stmt = $conn->prepare("DESCRIBE recus_paiements");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='padding: 5px;'>Colonne</th>";
echo "<th style='padding: 5px;'>Type</th>";
echo "<th style='padding: 5px;'>Null</th>";
echo "<th style='padding: 5px;'>Défaut</th>";
echo "</tr>";

foreach ($columns as $col) {
    echo "<tr>";
    echo "<td style='padding: 5px;'>" . $col['Field'] . "</td>";
    echo "<td style='padding: 5px;'>" . $col['Type'] . "</td>";
    echo "<td style='padding: 5px;'>" . $col['Null'] . "</td>";
    echo "<td style='padding: 5px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Instructions de Test</h3>";
echo "<ol>";
echo "<li>Cliquez sur 'Voir PDF' pour un reçu dans le tableau ci-dessus</li>";
echo "<li>Vérifiez que la source de paiement apparaît après 'Montant payé'</li>";
echo "<li>La source devrait afficher 'Caisse' ou 'Financement' selon la valeur</li>";
echo "</ol>";
?>
