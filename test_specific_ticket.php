<?php
require_once('inc/functions/connexion.php');
require_once('inc/functions/requete/requete_tickets.php');

$conn = getConnexion();
$numero_ticket = 'PABOG5202512000002';

echo "<h2>Test spécifique pour le ticket: $numero_ticket</h2>";

// Test direct de la fonction searchTickets avec LIKE
echo "<h3>Test searchTickets avec LIKE (sans filtre utilisateur)</h3>";
$tickets = searchTickets($conn, null, null, null, null, $numero_ticket, null);
echo "Nombre de résultats: " . count($tickets) . "<br>";

if (!empty($tickets)) {
    foreach ($tickets as $ticket) {
        echo "<strong>Ticket trouvé:</strong><br>";
        echo "- ID: " . $ticket['id_ticket'] . "<br>";
        echo "- Numéro: " . $ticket['numero_ticket'] . "<br>";
        echo "- Date: " . $ticket['date_ticket'] . "<br>";
        echo "- Utilisateur: " . $ticket['id_utilisateur'] . "<br>";
        echo "- Agent: " . $ticket['nom_complet_agent'] . "<br>";
        echo "- Usine: " . $ticket['nom_usine'] . "<br>";
        echo "<br>";
    }
} else {
    echo "❌ Aucun ticket trouvé avec searchTickets<br>";
}

// Test avec recherche partielle
echo "<h3>Test recherche partielle 'PABOG'</h3>";
$tickets_partial = searchTickets($conn, null, null, null, null, 'PABOG', null);
echo "Nombre de résultats avec 'PABOG': " . count($tickets_partial) . "<br>";

// Test direct SQL avec LIKE
echo "<h3>Test SQL direct avec LIKE</h3>";
$sql = "SELECT t.*, 
        CONCAT(a.nom, ' ', a.prenom) AS nom_complet_agent,
        us.nom_usine
        FROM tickets t
        LEFT JOIN agents a ON t.id_agent = a.id_agent
        LEFT JOIN usines us ON t.id_usine = us.id_usine
        WHERE t.numero_ticket LIKE :numero_ticket";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':numero_ticket', '%' . $numero_ticket . '%');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Résultats SQL direct: " . count($results) . "<br>";
if (!empty($results)) {
    foreach ($results as $result) {
        echo "- Ticket: " . $result['numero_ticket'] . " (ID: " . $result['id_ticket'] . ")<br>";
    }
}

$conn = null;
?>
