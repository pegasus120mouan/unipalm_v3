<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/get_solde.php';

echo "<h2>Test du Comportement des Paiements par Chèque</h2>";

// Récupérer le solde de caisse actuel
$solde_caisse_avant = getSoldeCaisse();

// Récupérer un agent avec du financement pour les tests
$stmt = $conn->prepare("
    SELECT 
        a.id_agent,
        CONCAT(a.nom, ' ', a.prenom) as nom_complet,
        COALESCE(SUM(f.montant), 0) as solde_financement
    FROM agents a
    LEFT JOIN financement f ON a.id_agent = f.id_agent
    GROUP BY a.id_agent, a.nom, a.prenom
    HAVING solde_financement > 0
    ORDER BY solde_financement DESC
    LIMIT 1
");
$stmt->execute();
$agent_test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent_test) {
    echo "<p style='color: red;'>Aucun agent avec du financement trouvé pour les tests.</p>";
    exit;
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Informations de Test</h3>";
echo "<p><strong>Agent de test :</strong> " . htmlspecialchars($agent_test['nom_complet']) . " (ID: " . $agent_test['id_agent'] . ")</p>";
echo "<p><strong>Solde financement :</strong> " . number_format($agent_test['solde_financement'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Solde caisse avant :</strong> " . number_format($solde_caisse_avant, 0, ',', ' ') . " FCFA</p>";
echo "</div>";

// Récupérer un bordereau non soldé pour cet agent
$stmt = $conn->prepare("
    SELECT 
        id_bordereau,
        numero_bordereau,
        montant_total,
        COALESCE(montant_payer, 0) as montant_payer,
        (montant_total - COALESCE(montant_payer, 0)) as montant_reste
    FROM bordereau 
    WHERE id_agent = ? 
    AND statut_bordereau = 'non soldé'
    AND (montant_total - COALESCE(montant_payer, 0)) > 0
    ORDER BY date_debut DESC
    LIMIT 1
");
$stmt->execute([$agent_test['id_agent']]);
$bordereau_test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bordereau_test) {
    echo "<p style='color: red;'>Aucun bordereau non soldé trouvé pour cet agent.</p>";
    exit;
}

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Bordereau de Test</h3>";
echo "<p><strong>Numéro :</strong> " . htmlspecialchars($bordereau_test['numero_bordereau']) . "</p>";
echo "<p><strong>Montant total :</strong> " . number_format($bordereau_test['montant_total'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Montant payé :</strong> " . number_format($bordereau_test['montant_payer'], 0, ',', ' ') . " FCFA</p>";
echo "<p><strong>Reste à payer :</strong> " . number_format($bordereau_test['montant_reste'], 0, ',', ' ') . " FCFA</p>";
echo "</div>";

// Calculer un montant de test (10% du reste à payer, minimum 1000 FCFA)
$montant_test = max(1000, floor($bordereau_test['montant_reste'] * 0.1));

echo "<h3>Comparaison des Sources de Paiement</h3>";
echo "<p><strong>Montant de test :</strong> " . number_format($montant_test, 0, ',', ' ') . " FCFA</p>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th style='padding: 10px;'>Source de Paiement</th>";
echo "<th style='padding: 10px;'>Limitation</th>";
echo "<th style='padding: 10px;'>Débit Caisse</th>";
echo "<th style='padding: 10px;'>Débit Financement</th>";
echo "<th style='padding: 10px;'>Transaction Créée</th>";
echo "<th style='padding: 10px;'>Reçu PDF</th>";
echo "</tr>";

// Ligne Caisse
echo "<tr>";
echo "<td style='padding: 10px; font-weight: bold; color: #28a745;'>Caisse (transactions)</td>";
echo "<td style='padding: 10px;'>Limitée par solde caisse</td>";
echo "<td style='padding: 10px; color: red;'>✅ OUI</td>";
echo "<td style='padding: 10px;'>❌ NON</td>";
echo "<td style='padding: 10px; color: green;'>✅ OUI</td>";
echo "<td style='padding: 10px;'>Source: Caisse</td>";
echo "</tr>";

// Ligne Financement
echo "<tr>";
echo "<td style='padding: 10px; font-weight: bold; color: #007bff;'>Financement</td>";
echo "<td style='padding: 10px;'>Limitée par solde financement</td>";
echo "<td style='padding: 10px;'>❌ NON</td>";
echo "<td style='padding: 10px; color: red;'>✅ OUI</td>";
echo "<td style='padding: 10px; color: orange;'>⚠️ Transaction avec montant=0</td>";
echo "<td style='padding: 10px;'>Source: Financement</td>";
echo "</tr>";

// Ligne Chèque
echo "<tr style='background: #fff3cd;'>";
echo "<td style='padding: 10px; font-weight: bold; color: #856404;'>Chèque</td>";
echo "<td style='padding: 10px; color: green;'><strong>Aucune limitation</strong></td>";
echo "<td style='padding: 10px; color: green;'>❌ NON</td>";
echo "<td style='padding: 10px; color: green;'>❌ NON</td>";
echo "<td style='padding: 10px; color: green;'>❌ AUCUNE</td>";
echo "<td style='padding: 10px;'><strong>Source: Chèque + N° Chèque</strong></td>";
echo "</tr>";

echo "</table>";

echo "<h3>Avantages du Paiement par Chèque</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<ul>";
echo "<li>✅ <strong>Aucune limitation de montant</strong> - Peut payer jusqu'au montant total restant</li>";
echo "<li>✅ <strong>Pas d'impact sur la caisse</strong> - Le solde de caisse reste inchangé</li>";
echo "<li>✅ <strong>Pas d'impact sur le financement</strong> - Le solde de financement reste inchangé</li>";
echo "<li>✅ <strong>Traçabilité complète</strong> - Numéro de chèque enregistré et affiché sur le reçu</li>";
echo "<li>✅ <strong>Gestion des bordereaux</strong> - Le bordereau est marqué comme payé normalement</li>";
echo "<li>✅ <strong>Calculs corrects</strong> - Le reste à payer est calculé correctement</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Test Pratique</h3>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Pour tester le paiement par chèque :</h4>";
echo "<ol>";
echo "<li>Allez sur la page : <a href='../pages/compte_agent_detail.php?id=" . $agent_test['id_agent'] . "' target='_blank'>Compte de " . htmlspecialchars($agent_test['nom_complet']) . "</a></li>";
echo "<li>Cliquez sur 'Payer' pour le bordereau <strong>" . htmlspecialchars($bordereau_test['numero_bordereau']) . "</strong></li>";
echo "<li>Sélectionnez <strong>'Paiement par chèque'</strong> dans la source de paiement</li>";
echo "<li>Saisissez un numéro de chèque (ex: <code>CHQ-TEST-001</code>)</li>";
echo "<li>Saisissez le montant : <strong>" . number_format($montant_test, 0, ',', ' ') . " FCFA</strong></li>";
echo "<li>Validez le paiement</li>";
echo "</ol>";

echo "<h4>Vérifications après le paiement :</h4>";
echo "<ul>";
echo "<li>🔍 <strong>Solde caisse :</strong> Doit rester à " . number_format($solde_caisse_avant, 0, ',', ' ') . " FCFA</li>";
echo "<li>🔍 <strong>Solde financement :</strong> Doit rester à " . number_format($agent_test['solde_financement'], 0, ',', ' ') . " FCFA</li>";
echo "<li>🔍 <strong>Bordereau :</strong> Montant payé doit augmenter de " . number_format($montant_test, 0, ',', ' ') . " FCFA</li>";
echo "<li>🔍 <strong>Reçu PDF :</strong> Doit afficher 'Source: Chèque' et le numéro de chèque</li>";
echo "<li>🔍 <strong>Table transactions :</strong> Aucune nouvelle transaction créée</li>";
echo "<li>🔍 <strong>Table financement :</strong> Aucune nouvelle ligne créée</li>";
echo "</ul>";
echo "</div>";

// Vérifier les derniers paiements par chèque
echo "<h3>Derniers Paiements par Chèque</h3>";
$stmt = $conn->prepare("
    SELECT 
        numero_recu,
        numero_document,
        montant_paye,
        numero_cheque,
        nom_agent,
        date_creation
    FROM recus_paiements 
    WHERE source_paiement = 'cheque'
    ORDER BY date_creation DESC 
    LIMIT 5
");
$stmt->execute();
$paiements_cheque = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($paiements_cheque)) {
    echo "<p style='color: orange;'>Aucun paiement par chèque effectué pour le moment.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>N° Reçu</th>";
    echo "<th style='padding: 8px;'>Document</th>";
    echo "<th style='padding: 8px;'>Montant</th>";
    echo "<th style='padding: 8px;'>N° Chèque</th>";
    echo "<th style='padding: 8px;'>Agent</th>";
    echo "<th style='padding: 8px;'>Date</th>";
    echo "</tr>";
    
    foreach ($paiements_cheque as $paiement) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($paiement['numero_recu']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($paiement['numero_document']) . "</td>";
        echo "<td style='padding: 8px; text-align: right;'>" . number_format($paiement['montant_paye'], 0, ',', ' ') . " FCFA</td>";
        echo "<td style='padding: 8px; font-weight: bold; color: #007bff;'>" . htmlspecialchars($paiement['numero_cheque']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($paiement['nom_agent']) . "</td>";
        echo "<td style='padding: 8px;'>" . date('d/m/Y H:i', strtotime($paiement['date_creation'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
