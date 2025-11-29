<?php
require_once 'inc/functions/connexion.php';
require_once 'inc/functions/requete/requete_prix_unitaires.php';

// Test avec les données du ticket problématique
$date_ticket = '2025-11-29';
$nom_usine = 'SEHP';

echo "<h2>Debug Prix Unitaire</h2>";
echo "<p><strong>Date du ticket:</strong> $date_ticket</p>";
echo "<p><strong>Usine:</strong> $nom_usine</p>";

// 1. Récupérer l'ID de l'usine SEHP
$sql_usine = "SELECT id_usine, nom_usine FROM usines WHERE nom_usine = :nom_usine";
$stmt_usine = $conn->prepare($sql_usine);
$stmt_usine->bindParam(':nom_usine', $nom_usine);
$stmt_usine->execute();
$usine = $stmt_usine->fetch(PDO::FETCH_ASSOC);

if ($usine) {
    echo "<p><strong>ID Usine trouvé:</strong> " . $usine['id_usine'] . "</p>";
    $id_usine = $usine['id_usine'];
    
    // 2. Vérifier tous les prix unitaires pour cette usine
    echo "<h3>Tous les prix unitaires pour cette usine:</h3>";
    $sql_all = "SELECT * FROM prix_unitaires WHERE id_usine = :id_usine ORDER BY date_debut DESC";
    $stmt_all = $conn->prepare($sql_all);
    $stmt_all->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
    $stmt_all->execute();
    $all_prix = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_prix) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Prix</th><th>Date Début</th><th>Date Fin</th></tr>";
        foreach ($all_prix as $prix) {
            echo "<tr>";
            echo "<td>" . $prix['id'] . "</td>";
            echo "<td>" . $prix['prix'] . "</td>";
            echo "<td>" . $prix['date_debut'] . "</td>";
            echo "<td>" . ($prix['date_fin'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Aucun prix unitaire trouvé pour cette usine !</p>";
    }
    
    // 3. Tester la nouvelle logique de la fonction
    echo "<h3>Test de la nouvelle logique:</h3>";
    $sql_test = "SELECT prix, date_debut, date_fin,
                        CASE 
                            WHEN date_debut <= ? AND (date_fin IS NULL OR date_fin >= ?) THEN 'MATCH'
                            ELSE 'NO MATCH'
                        END as match_result
                 FROM prix_unitaires 
                 WHERE id_usine = ?";
    
    $stmt_test = $conn->prepare($sql_test);
    $stmt_test->execute([$date_ticket, $date_ticket, $id_usine]);
    $test_results = $stmt_test->fetchAll(PDO::FETCH_ASSOC);
    
    if ($test_results) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Prix</th><th>Date Début</th><th>Date Fin</th><th>Match</th></tr>";
        foreach ($test_results as $result) {
            echo "<tr>";
            echo "<td>" . $result['prix'] . "</td>";
            echo "<td>" . $result['date_debut'] . "</td>";
            echo "<td>" . ($result['date_fin'] ?? 'NULL') . "</td>";
            echo "<td style='color: " . ($result['match_result'] == 'MATCH' ? 'green' : 'red') . ";'>" . $result['match_result'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Tester la fonction getPrixUnitaireByDateAndUsine
    echo "<h3>Résultat de la fonction getPrixUnitaireByDateAndUsine:</h3>";
    $prix_info = getPrixUnitaireByDateAndUsine($conn, $date_ticket, $id_usine);
    echo "<p><strong>Prix:</strong> " . $prix_info['prix'] . "</p>";
    echo "<p><strong>Is Default:</strong> " . ($prix_info['is_default'] ? 'true' : 'false') . "</p>";
    
} else {
    echo "<p style='color: red;'>Usine '$nom_usine' non trouvée !</p>";
}
?>
