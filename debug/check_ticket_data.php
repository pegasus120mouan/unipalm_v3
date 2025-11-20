<?php
require_once '../inc/functions/connexion.php';

$conn = getConnexion();

// Vérifier les données du ticket ID 11581
$sql = "SELECT 
    t.id_ticket,
    t.numero_ticket,
    t.poids,
    t.prix_unitaire,
    t.montant_paie,
    (t.poids * t.prix_unitaire) as calcul_correct,
    t.numero_bordereau,
    t.date_ticket,
    t.vehicule_id,
    v.matricule_vehicule,
    us.nom_usine
FROM tickets t
LEFT JOIN vehicules v ON t.vehicule_id = v.vehicules_id  
LEFT JOIN usines us ON t.id_usine = us.id_usine
WHERE t.id_ticket = 11581";

$stmt = $conn->prepare($sql);
$stmt->execute();
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Données du Ticket ID 11581</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>Champ</th><th>Valeur</th></tr>";

if ($ticket) {
    foreach ($ticket as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>Ticket non trouvé</td></tr>";
}

echo "</table>";

// Vérifier aussi le bordereau associé
if ($ticket && $ticket['numero_bordereau']) {
    echo "<h2>Données du Bordereau " . $ticket['numero_bordereau'] . "</h2>";
    
    $sql_bordereau = "SELECT * FROM bordereau WHERE numero_bordereau = ?";
    $stmt_b = $conn->prepare($sql_bordereau);
    $stmt_b->execute([$ticket['numero_bordereau']]);
    $bordereau = $stmt_b->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
    echo "<tr><th>Champ</th><th>Valeur</th></tr>";
    
    if ($bordereau) {
        foreach ($bordereau as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
    }
    echo "</table>";
    
    // Vérifier tous les tickets du bordereau
    echo "<h2>Tous les Tickets du Bordereau</h2>";
    
    $sql_all = "SELECT 
        id_ticket, numero_ticket, poids, prix_unitaire, 
        (poids * prix_unitaire) as montant_calcule,
        montant_paie
    FROM tickets 
    WHERE numero_bordereau = ?";
    
    $stmt_all = $conn->prepare($sql_all);
    $stmt_all->execute([$ticket['numero_bordereau']]);
    $all_tickets = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
    echo "<tr><th>ID</th><th>N° Ticket</th><th>Poids</th><th>Prix Unit.</th><th>Montant Calculé</th><th>Montant Paie</th></tr>";
    
    $total_calcule = 0;
    foreach ($all_tickets as $t) {
        echo "<tr>";
        echo "<td>" . $t['id_ticket'] . "</td>";
        echo "<td>" . $t['numero_ticket'] . "</td>";
        echo "<td>" . number_format($t['poids'], 2) . "</td>";
        echo "<td>" . number_format($t['prix_unitaire'], 2) . "</td>";
        echo "<td>" . number_format($t['montant_calcule'], 2) . "</td>";
        echo "<td>" . number_format($t['montant_paie'], 2) . "</td>";
        echo "</tr>";
        $total_calcule += $t['montant_calcule'];
    }
    
    echo "<tr style='background: yellow;'>";
    echo "<td colspan='4'><strong>TOTAL</strong></td>";
    echo "<td><strong>" . number_format($total_calcule, 2) . "</strong></td>";
    echo "<td><strong>" . number_format($bordereau['montant_total'], 2) . "</strong></td>";
    echo "</tr>";
    
    echo "</table>";
}
?>
