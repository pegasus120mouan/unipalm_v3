<?php
session_start();
require_once '../inc/functions/connexion.php';

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Installation Paiement par Chèque</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='bg-light'>";
echo "<div class='container mt-5'>";

echo "<div class='row justify-content-center'>";
echo "<div class='col-md-8'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h2 class='mb-0'>💳 Installation du Paiement par Chèque</h2>";
echo "</div>";
echo "<div class='card-body'>";

try {
    $conn->beginTransaction();
    
    echo "<div class='alert alert-info'>";
    echo "<h4>🔧 Modifications de la Base de Données</h4>";
    echo "<p>Installation des colonnes nécessaires pour le paiement par chèque...</p>";
    echo "</div>";
    
    $modifications = [];
    
    // 1. Vérifier si la colonne numero_cheque existe déjà dans recus_paiements
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
        // Ajouter la colonne numero_cheque à recus_paiements
        $conn->exec("ALTER TABLE recus_paiements ADD COLUMN numero_cheque VARCHAR(50) NULL AFTER source_paiement");
        $modifications[] = "✅ Colonne 'numero_cheque' ajoutée à la table 'recus_paiements'";
        
        // Créer l'index
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
    
    // 3. Vérifier et ajouter numero_cheque à la table transactions si nécessaire
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
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Installation Réussie !</h4>";
    echo "<ul class='mb-0'>";
    foreach ($modifications as $modif) {
        echo "<li>$modif</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 4. Vérification finale
    echo "<div class='alert alert-info'>";
    echo "<h4>🔍 Vérification de l'Installation</h4>";
    
    $stmt = $conn->prepare("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'recus_paiements' 
        AND COLUMN_NAME IN ('source_paiement', 'numero_cheque')
        AND TABLE_SCHEMA = DATABASE()
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Colonne</th><th>Type</th><th>Nullable</th></tr></thead>";
    echo "<tbody>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['COLUMN_NAME'] . "</td>";
        echo "<td>" . $col['COLUMN_TYPE'] . "</td>";
        echo "<td>" . ($col['IS_NULLABLE'] === 'YES' ? 'Oui' : 'Non') . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='alert alert-success'>";
    echo "<h4>🎉 Installation Terminée !</h4>";
    echo "<p><strong>Vous pouvez maintenant :</strong></p>";
    echo "<ol>";
    echo "<li>Utiliser le paiement par chèque dans les modals</li>";
    echo "<li>Saisir des numéros de chèque</li>";
    echo "<li>Voir les numéros de chèque dans les reçus PDF</li>";
    echo "</ol>";
    echo "<div class='mt-3'>";
    echo "<a href='bordereaux.php' class='btn btn-primary me-2'>📋 Tester sur les Bordereaux</a>";
    echo "<a href='verifier_bordereau.php' class='btn btn-secondary'>🔍 Vérifier le Bordereau</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur d'Installation</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>"; // card-body
echo "</div>"; // card
echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "</body>";
echo "</html>";
?>
