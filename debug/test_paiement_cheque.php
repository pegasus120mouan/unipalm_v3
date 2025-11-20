<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Test du Paiement par Chèque</h2>";

// Vérifier si la colonne numero_cheque existe dans recus_paiements
echo "<h3>1. Vérification de la structure de la table recus_paiements</h3>";
try {
    $stmt = $conn->prepare("DESCRIBE recus_paiements");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_numero_cheque = false;
    $has_source_cheque = false;
    
    echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 5px;'>Colonne</th>";
    echo "<th style='padding: 5px;'>Type</th>";
    echo "<th style='padding: 5px;'>Null</th>";
    echo "<th style='padding: 5px;'>Statut</th>";
    echo "</tr>";
    
    foreach ($columns as $col) {
        $status = '';
        if ($col['Field'] === 'numero_cheque') {
            $has_numero_cheque = true;
            $status = '✅ Colonne numéro chèque OK';
        } elseif ($col['Field'] === 'source_paiement') {
            if (strpos($col['Type'], 'cheque') !== false) {
                $has_source_cheque = true;
                $status = '✅ Option chèque dans ENUM OK';
            } else {
                $status = '❌ Option chèque manquante dans ENUM';
            }
        }
        
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . $col['Field'] . "</td>";
        echo "<td style='padding: 5px;'>" . $col['Type'] . "</td>";
        echo "<td style='padding: 5px;'>" . $col['Null'] . "</td>";
        echo "<td style='padding: 5px;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='margin: 20px 0;'>";
    if ($has_numero_cheque) {
        echo "<p style='color: green;'>✅ <strong>Colonne numero_cheque :</strong> Présente</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Colonne numero_cheque :</strong> Manquante - Exécutez le script SQL add_paiement_cheque.sql</p>";
    }
    
    if ($has_source_cheque) {
        echo "<p style='color: green;'>✅ <strong>Option 'cheque' dans source_paiement :</strong> Présente</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Option 'cheque' dans source_paiement :</strong> Manquante - Exécutez le script SQL add_paiement_cheque.sql</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}

// Vérifier les derniers paiements par chèque
echo "<h3>2. Derniers paiements par chèque</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            numero_recu,
            type_document,
            numero_document,
            montant_paye,
            source_paiement,
            numero_cheque,
            nom_agent,
            date_creation
        FROM recus_paiements 
        WHERE source_paiement = 'cheque'
        ORDER BY date_creation DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $paiements_cheque = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($paiements_cheque)) {
        echo "<p style='color: orange;'>Aucun paiement par chèque trouvé. Testez la fonctionnalité en effectuant un paiement.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; padding: 10px; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>N° Reçu</th>";
        echo "<th style='padding: 10px;'>Type</th>";
        echo "<th style='padding: 10px;'>N° Document</th>";
        echo "<th style='padding: 10px;'>Montant</th>";
        echo "<th style='padding: 10px;'>N° Chèque</th>";
        echo "<th style='padding: 10px;'>Agent</th>";
        echo "<th style='padding: 10px;'>Date</th>";
        echo "<th style='padding: 10px;'>Action</th>";
        echo "</tr>";

        foreach ($paiements_cheque as $paiement) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($paiement['numero_recu']) . "</td>";
            echo "<td style='padding: 10px;'>" . ucfirst($paiement['type_document']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($paiement['numero_document']) . "</td>";
            echo "<td style='padding: 10px; text-align: right;'>" . number_format($paiement['montant_paye'], 0, ',', ' ') . " FCFA</td>";
            echo "<td style='padding: 10px; font-weight: bold; color: #007bff;'>" . htmlspecialchars($paiement['numero_cheque']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($paiement['nom_agent']) . "</td>";
            echo "<td style='padding: 10px;'>" . date('d/m/Y H:i', strtotime($paiement['date_creation'])) . "</td>";
            
            // Bouton pour tester le PDF
            if ($paiement['type_document'] === 'ticket') {
                $pdf_url = "recu_paiement_pdf.php?id_ticket=" . $paiement['numero_document'] . "&reimprimer=1";
            } else {
                $pdf_url = "recu_paiement_pdf.php?id_bordereau=" . $paiement['numero_document'] . "&reimprimer=1";
            }
            
            echo "<td style='padding: 10px;'>";
            echo "<a href='$pdf_url' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;'>Voir PDF</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la récupération des paiements par chèque : " . $e->getMessage() . "</p>";
}

// Instructions de test
echo "<h3>3. Instructions de Test</h3>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Pour tester la fonctionnalité :</h4>";
echo "<ol>";
echo "<li><strong>Exécuter le script SQL :</strong> Si les vérifications ci-dessus montrent des erreurs, exécutez d'abord <code>/sql/add_paiement_cheque.sql</code></li>";
echo "<li><strong>Aller sur la page de paiement :</strong> <a href='../pages/compte_agent_detail.php?id=1' target='_blank'>compte_agent_detail.php</a></li>";
echo "<li><strong>Ouvrir un modal de paiement</strong> pour un ticket ou bordereau</li>";
echo "<li><strong>Sélectionner 'Paiement par chèque'</strong> dans la source de paiement</li>";
echo "<li><strong>Vérifier que le champ 'Numéro de chèque' apparaît</strong></li>";
echo "<li><strong>Saisir un numéro de chèque</strong> (ex: CHQ-001)</li>";
echo "<li><strong>Effectuer le paiement</strong></li>";
echo "<li><strong>Vérifier le reçu PDF</strong> - il doit afficher le numéro de chèque</li>";
echo "</ol>";

echo "<h4>Fonctionnalités à vérifier :</h4>";
echo "<ul>";
echo "<li>✅ Le champ numéro de chèque apparaît/disparaît selon la source</li>";
echo "<li>✅ Le champ est obligatoire pour les paiements par chèque</li>";
echo "<li>✅ Validation : numéro de chèque unique (pas de doublon)</li>";
echo "<li>✅ Validation : minimum 3 caractères</li>";
echo "<li>✅ Pas de limitation par le solde de caisse pour les chèques</li>";
echo "<li>✅ Le reçu PDF affiche 'Source: Chèque' et le numéro</li>";
echo "</ul>";
echo "</div>";

// Vérifier les numéros de chèque en doublon
echo "<h3>4. Vérification des doublons de numéros de chèque</h3>";
try {
    $stmt = $conn->prepare("
        SELECT numero_cheque, COUNT(*) as nb_utilisations
        FROM recus_paiements 
        WHERE numero_cheque IS NOT NULL 
        GROUP BY numero_cheque 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $doublons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($doublons)) {
        echo "<p style='color: green;'>✅ Aucun doublon de numéro de chèque détecté.</p>";
    } else {
        echo "<p style='color: red;'>❌ Doublons détectés :</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Numéro Chèque</th><th>Nb Utilisations</th></tr>";
        foreach ($doublons as $doublon) {
            echo "<tr><td>" . htmlspecialchars($doublon['numero_cheque']) . "</td><td>" . $doublon['nb_utilisations'] . "</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification des doublons : " . $e->getMessage() . "</p>";
}
?>
