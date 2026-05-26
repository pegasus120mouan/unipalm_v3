<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/get_solde.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: compte_chef_equipe.php');
    exit;
}

$chef_id = isset($_POST['chef_id']) ? intval($_POST['chef_id']) : 0;
$montant_paiement = isset($_POST['montant_paiement']) ? floatval($_POST['montant_paiement']) : 0;
$motif = isset($_POST['motif_paiement']) ? trim($_POST['motif_paiement']) : '';
$user_id = $_SESSION['user_id'] ?? 0;

if ($chef_id <= 0 || $montant_paiement <= 0) {
    $_SESSION['error'] = "Données de paiement invalides.";
    header('Location: compte_chef_equipe.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Récupérer les informations du chef
    $stmt_chef = $conn->prepare("SELECT CONCAT(nom, ' ', prenoms) as nom_chef FROM chef_equipe WHERE id_chef = ?");
    $stmt_chef->execute([$chef_id]);
    $chef = $stmt_chef->fetch(PDO::FETCH_ASSOC);
    $nom_chef = $chef['nom_chef'] ?? 'Chef #' . $chef_id;

    // Récupérer les informations du caissier
    $stmt_caissier = $conn->prepare("SELECT CONCAT(nom, ' ', prenoms) as nom_caissier FROM utilisateurs WHERE id = ?");
    $stmt_caissier->execute([$user_id]);
    $caissier = $stmt_caissier->fetch(PDO::FETCH_ASSOC);
    $nom_caissier = $caissier['nom_caissier'] ?? 'Utilisateur #' . $user_id;

    // Vérifier le solde de caisse
    $stmt_solde = $conn->prepare("SELECT COALESCE(MAX(solde), 0) as solde FROM transactions");
    $stmt_solde->execute();
    $result_solde = $stmt_solde->fetch(PDO::FETCH_ASSOC);
    $solde_actuel = floatval($result_solde['solde']);

    if ($solde_actuel < $montant_paiement) {
        throw new Exception("Solde de caisse insuffisant. Solde actuel : " . number_format($solde_actuel, 0, ',', ' ') . " FCFA");
    }

    // Calculer le nouveau solde
    $nouveau_solde = $solde_actuel - $montant_paiement;

    // Récupérer les tickets non payés des agents de ce chef, triés par date (les plus anciens d'abord)
    $sql = "SELECT t.id_ticket, t.numero_ticket, t.montant_paie, COALESCE(t.montant_payer, 0) as montant_payer,
                   a.id_agent, CONCAT(a.nom, ' ', a.prenom) as nom_agent
            FROM tickets t
            INNER JOIN agents a ON t.id_agent = a.id_agent
            WHERE a.id_chef = :chef_id 
            AND a.date_suppression IS NULL
            AND t.date_paie IS NULL 
            AND t.montant_paie IS NOT NULL
            AND t.montant_paie > COALESCE(t.montant_payer, 0)
            ORDER BY t.date_ticket ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':chef_id' => $chef_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $montant_restant = $montant_paiement;
    $tickets_payes = 0;
    $tickets_partiels = 0;
    $tickets_details = [];

    foreach ($tickets as $ticket) {
        if ($montant_restant <= 0) break;

        $montant_ticket = $ticket['montant_paie'];
        $deja_paye = $ticket['montant_payer'];
        $reste_ticket = $montant_ticket - $deja_paye;

        if ($montant_restant >= $reste_ticket) {
            // Paiement complet du ticket
            $montant_applique = $reste_ticket;
            $nouveau_montant_paye = $montant_ticket;
            $nouveau_reste = 0;
            $date_paie = date('Y-m-d H:i:s');
            $montant_restant -= $reste_ticket;
            $tickets_payes++;
        } else {
            // Paiement partiel du ticket
            $montant_applique = $montant_restant;
            $nouveau_montant_paye = $deja_paye + $montant_restant;
            $nouveau_reste = $montant_ticket - $nouveau_montant_paye;
            $date_paie = null; // Pas encore totalement payé
            $montant_restant = 0;
            $tickets_partiels++;
        }

        // Mettre à jour le ticket
        $update_sql = "UPDATE tickets SET montant_payer = :montant_payer, montant_reste = :montant_reste";
        if ($date_paie !== null) {
            $update_sql .= ", date_paie = :date_paie";
        }
        $update_sql .= " WHERE id_ticket = :id_ticket";

        $update_stmt = $conn->prepare($update_sql);
        $params = [
            ':montant_payer' => $nouveau_montant_paye,
            ':montant_reste' => $nouveau_reste,
            ':id_ticket' => $ticket['id_ticket']
        ];
        if ($date_paie !== null) {
            $params[':date_paie'] = $date_paie;
        }
        $update_stmt->execute($params);

        $tickets_details[] = [
            'numero' => $ticket['numero_ticket'],
            'montant' => $montant_applique
        ];
    }

    // Créer la transaction (sortie de caisse)
    $motifs_transaction = "Paiement chef d'équipe: " . $nom_chef;
    if (!empty($motif)) {
        $motifs_transaction .= " - " . $motif;
    }

    $stmt_transaction = $conn->prepare("
        INSERT INTO transactions (
            type_transaction, 
            montant, 
            date_transaction, 
            motifs, 
            id_utilisateur,
            solde
        ) VALUES (
            'paiement',
            :montant,
            NOW(),
            :motifs,
            :id_utilisateur,
            :solde
        )
    ");
    $stmt_transaction->execute([
        ':montant' => $montant_paiement,
        ':motifs' => $motifs_transaction,
        ':id_utilisateur' => $user_id,
        ':solde' => $nouveau_solde
    ]);
    $id_transaction = $conn->lastInsertId();

    // Générer un numéro de reçu unique
    $numero_recu = 'CHEF-' . date('Ymd') . sprintf("%04d", rand(1, 9999));

    // Calculer le reste à payer global pour ce chef
    $stmt_reste = $conn->prepare("
        SELECT COALESCE(SUM(CASE WHEN t.date_paie IS NULL THEN t.montant_paie - COALESCE(t.montant_payer, 0) ELSE 0 END), 0) as reste
        FROM tickets t
        INNER JOIN agents a ON t.id_agent = a.id_agent
        WHERE a.id_chef = ? AND a.date_suppression IS NULL AND t.montant_paie IS NOT NULL
    ");
    $stmt_reste->execute([$chef_id]);
    $reste_global = $stmt_reste->fetch(PDO::FETCH_ASSOC)['reste'];

    $conn->commit();

    // Stocker les informations pour le modal de succès
    $_SESSION['paiement_success'] = true;
    $_SESSION['success'] = "Paiement de " . number_format($montant_paiement, 0, ',', ' ') . " FCFA effectué avec succès.";
    $_SESSION['paiement_details'] = [
        'numero_recu' => $numero_recu,
        'montant' => $montant_paiement,
        'tickets_soldes' => $tickets_payes,
        'tickets_partiels' => $tickets_partiels,
        'nouveau_solde' => $nouveau_solde,
        'nom_chef' => $nom_chef,
        'reste_a_payer' => $reste_global
    ];

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Erreur lors du paiement : " . $e->getMessage();
}

header('Location: details_compte_chef.php?id=' . $chef_id);
exit;
