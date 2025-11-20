<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';

session_start();

// Vérifier si la connexion est établie
$conn = getConnexion();
if (!$conn) {
    $_SESSION['error'] = 'Erreur de connexion à la base de données';
    header('Location: bordereaux.php');
    exit;
}

// Récupérer les données du formulaire
$id_agent = $_POST['id_agent'] ?? '';
$numero_bordereau = $_POST['numero_bordereau'] ?? '';
$selected_tickets = $_POST['tickets'] ?? [];

// Vérifier si les données nécessaires sont présentes
if (empty($numero_bordereau) || empty($selected_tickets) || empty($id_agent)) {
    $_SESSION['error'] = 'Données manquantes pour l\'association des tickets';
    header('Location: bordereaux.php');
    exit;
}

try {
    $conn->beginTransaction();
    
    // Vérifier d'abord que les tickets ne sont pas déjà associés à un bordereau
    $check_stmt = $conn->prepare("SELECT id_ticket, numero_bordereau 
                                 FROM tickets 
                                 WHERE id_ticket IN (" . str_repeat('?,', count($selected_tickets) - 1) . "?) 
                                 AND (numero_bordereau IS NOT NULL AND numero_bordereau != '')");
    $check_stmt->execute($selected_tickets);
    $already_associated = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($already_associated)) {
        $associated_list = [];
        foreach ($already_associated as $ticket) {
            $associated_list[] = "Ticket ID " . $ticket['id_ticket'] . " (bordereau: " . $ticket['numero_bordereau'] . ")";
        }
        throw new Exception("Les tickets suivants sont déjà associés à un bordereau : " . implode(', ', $associated_list));
    }
    
    // Mise à jour des tickets (seulement ceux qui ne sont pas déjà associés)
    $stmt = $conn->prepare("UPDATE tickets 
                           SET numero_bordereau = :numero_bordereau 
                           WHERE id_ticket = :id_ticket 
                           AND id_agent = :id_agent 
                           AND (numero_bordereau IS NULL OR numero_bordereau = '')");
    
    $updated_count = 0;
    foreach ($selected_tickets as $id_ticket) {
        $result = $stmt->execute([
            ':numero_bordereau' => $numero_bordereau,
            ':id_ticket' => $id_ticket,
            ':id_agent' => $id_agent
        ]);
        if ($stmt->rowCount() > 0) {
            $updated_count++;
        }
    }
    
    if ($updated_count == 0) {
        throw new Exception("Aucun ticket n'a pu être associé. Vérifiez que les tickets ne sont pas déjà associés à un bordereau.");
    }

    // Mettre à jour le montant total et le poids total du bordereau
    $sql_update_bordereau = "UPDATE bordereau b 
                           SET b.montant_total = (
                               SELECT COALESCE(CAST(SUM(t.prix_unitaire * t.poids) AS DECIMAL(15,2)), 0)
                               FROM tickets t 
                               WHERE t.numero_bordereau = b.numero_bordereau
                           ),
                           b.poids_total = (
                               SELECT COALESCE(CAST(SUM(t.poids) AS DECIMAL(15,2)), 0)
                               FROM tickets t 
                               WHERE t.numero_bordereau = b.numero_bordereau
                           )
                           WHERE b.numero_bordereau = :numero_bordereau";
    
    $stmt = $conn->prepare($sql_update_bordereau);
    $stmt->execute([':numero_bordereau' => $numero_bordereau]);
    
    $conn->commit();
    $_SESSION['success'] = $updated_count . ' ticket(s) associé(s) avec succès au bordereau ' . $numero_bordereau;
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Erreur dans associer_tickets.php: " . $e->getMessage());
    $_SESSION['error'] = 'Erreur lors de l\'association des tickets : ' . $e->getMessage();
}

header('Location: bordereaux.php');
exit;
?>
