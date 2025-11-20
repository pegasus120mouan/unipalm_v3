<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Installation automatique du support des chèques</h2>";

try {
    $conn->beginTransaction();
    
    $modifications = [];
    
    // 1. Vérifier si la colonne numero_cheque existe dans recus_paiements
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count_col 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'recus_paiements' 
        AND COLUMN_NAME = 'numero_cheque'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $col_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count_col'] > 0;
    
    if (!$col_exists) {
        $conn->exec("ALTER TABLE recus_paiements ADD COLUMN numero_cheque VARCHAR(50) NULL AFTER source_paiement");
        $modifications[] = "✅ Colonne 'numero_cheque' ajoutée à la table 'recus_paiements'";
        
        $conn->exec("ALTER TABLE recus_paiements ADD INDEX idx_numero_cheque (numero_cheque)");
        $modifications[] = "✅ Index créé sur 'numero_cheque'";
    } else {
        $modifications[] = "ℹ️ Colonne 'numero_cheque' existe déjà dans 'recus_paiements'";
    }
    
    // 2. Modifier l'ENUM source_paiement pour inclure 'cheque'
    $stmt = $conn->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'recus_paiements' 
        AND COLUMN_NAME = 'source_paiement'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $column_type = $stmt->fetch(PDO::FETCH_ASSOC)['COLUMN_TYPE'];
    
    if (strpos($column_type, 'cheque') === false) {
        $conn->exec("ALTER TABLE recus_paiements MODIFY COLUMN source_paiement ENUM('transactions', 'financement', 'cheque') NOT NULL");
        $modifications[] = "✅ Option 'cheque' ajoutée à l'ENUM 'source_paiement'";
    } else {
        $modifications[] = "ℹ️ Option 'cheque' existe déjà dans 'source_paiement'";
    }
    
    // 3. Vérifier et ajouter numero_cheque à la table transactions
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count_col 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'transactions' 
        AND COLUMN_NAME = 'numero_cheque'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $trans_col_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count_col'] > 0;
    
    if (!$trans_col_exists) {
        $conn->exec("ALTER TABLE transactions ADD COLUMN numero_cheque VARCHAR(50) NULL AFTER type_transaction");
        $modifications[] = "✅ Colonne 'numero_cheque' ajoutée à la table 'transactions'";
    } else {
        $modifications[] = "ℹ️ Colonne 'numero_cheque' existe déjà dans 'transactions'";
    }
    
    $conn->commit();
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #155724;'>✅ Installation Réussie !</h3>";
    echo "<ul>";
    foreach ($modifications as $modif) {
        echo "<li>$modif</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<p><strong>🎉 Le paiement par chèque est maintenant disponible !</strong></p>";
    echo "<p><a href='compte_agent_detail.php?id_agent=185' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Tester le paiement par chèque</a></p>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Erreur d'Installation</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
