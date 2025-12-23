<?php
require_once('inc/functions/connexion.php');
require_once('inc/functions/requete/requete_tickets.php');

// Test de recherche pour le ticket PABOG5202512000002
$conn = getConnexion();
$numero_ticket = 'PABOG5202512000002';

echo "<h2>Debug: Recherche du ticket $numero_ticket</h2>";

// 1. Vérifier si le ticket existe dans la base
echo "<h3>1. Vérification existence du ticket</h3>";
$sql_check = "SELECT id_ticket, numero_ticket, id_utilisateur, date_ticket FROM tickets WHERE numero_ticket = :numero_ticket";
$stmt = $conn->prepare($sql_check);
$stmt->bindParam(':numero_ticket', $numero_ticket);
$stmt->execute();
$ticket_direct = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ticket_direct) {
    echo "✅ Ticket trouvé directement dans la base:<br>";
    echo "- ID: " . $ticket_direct['id_ticket'] . "<br>";
    echo "- Numéro: " . $ticket_direct['numero_ticket'] . "<br>";
    echo "- Utilisateur ID: " . $ticket_direct['id_utilisateur'] . "<br>";
    echo "- Date: " . $ticket_direct['date_ticket'] . "<br><br>";
} else {
    echo "❌ Ticket non trouvé dans la base<br><br>";
}

// 2. Test avec searchTickets (sans filtre utilisateur)
echo "<h3>2. Test searchTickets sans filtre utilisateur</h3>";
$tickets_no_user = searchTickets($conn, null, null, null, null, $numero_ticket, null);
echo "Résultats trouvés: " . count($tickets_no_user) . "<br>";
if (!empty($tickets_no_user)) {
    foreach ($tickets_no_user as $ticket) {
        echo "- Ticket ID: " . $ticket['id_ticket'] . ", Utilisateur: " . $ticket['id_utilisateur'] . "<br>";
    }
}
echo "<br>";

// 3. Test avec différents utilisateurs
echo "<h3>3. Test avec différents utilisateurs</h3>";
$sql_users = "SELECT DISTINCT id_utilisateur FROM tickets WHERE numero_ticket = :numero_ticket";
$stmt = $conn->prepare($sql_users);
$stmt->bindParam(':numero_ticket', $numero_ticket);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $user_id = $user['id_utilisateur'];
    echo "Test avec utilisateur ID: $user_id<br>";
    $tickets_with_user = searchTickets($conn, null, null, null, null, $numero_ticket, $user_id);
    echo "Résultats: " . count($tickets_with_user) . "<br>";
}
echo "<br>";

// 4. Test LIKE search
echo "<h3>4. Test recherche LIKE</h3>";
$partial_number = 'PABOG';
$tickets_like = searchTickets($conn, null, null, null, null, $partial_number, null);
echo "Recherche avec '$partial_number': " . count($tickets_like) . " résultats<br>";

// 5. Vérifier les utilisateurs existants
echo "<h3>5. Utilisateurs dans le système</h3>";
$sql_all_users = "SELECT id, nom, prenoms FROM utilisateurs ORDER BY id";
$stmt = $conn->prepare($sql_all_users);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_users as $user) {
    echo "- ID: " . $user['id'] . ", Nom: " . $user['nom'] . " " . $user['prenoms'] . "<br>";
}

$conn = null;
?>
