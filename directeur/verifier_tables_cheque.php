<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Vérification des tables pour le paiement par chèque</h2>";

try {
    // Vérifier la table recus_paiements
    echo "<h3>Table recus_paiements :</h3>";
    $stmt = $conn->prepare("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'recus_paiements' 
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME IN ('source_paiement', 'numero_cheque')
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "<p style='color: red;'>❌ Aucune colonne trouvée pour source_paiement ou numero_cheque</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Nullable</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['COLUMN_NAME'] . "</td>";
            echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
            echo "<td>" . ($col['IS_NULLABLE'] === 'YES' ? 'Oui' : 'Non') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Vérifier la table transactions
    echo "<h3>Table transactions :</h3>";
    $stmt = $conn->prepare("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'transactions' 
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME = 'numero_cheque'
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute();
    $trans_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($trans_columns)) {
        echo "<p style='color: red;'>❌ Colonne numero_cheque manquante dans la table transactions</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Colonne</th><th>Type</th><th>Nullable</th></tr>";
        foreach ($trans_columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['COLUMN_NAME'] . "</td>";
            echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
            echo "<td>" . ($col['IS_NULLABLE'] === 'YES' ? 'Oui' : 'Non') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Vérifier l'ENUM source_paiement
    echo "<h3>Vérification de l'ENUM source_paiement :</h3>";
    $stmt = $conn->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'recus_paiements' 
        AND COLUMN_NAME = 'source_paiement'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $enum_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($enum_result) {
        echo "<p><strong>Type actuel :</strong> " . $enum_result['COLUMN_TYPE'] . "</p>";
        if (strpos($enum_result['COLUMN_TYPE'], 'cheque') !== false) {
            echo "<p style='color: green;'>✅ L'option 'cheque' est présente dans l'ENUM</p>";
        } else {
            echo "<p style='color: red;'>❌ L'option 'cheque' est manquante dans l'ENUM</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Colonne source_paiement non trouvée</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>
