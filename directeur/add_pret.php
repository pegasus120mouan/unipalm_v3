<?php
require_once '../inc/functions/connexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['id_agent']) || empty($_POST['montant_initial'])) {
            throw new Exception("Agent et montant sont obligatoires.");
        }

        $id_agent        = (int) $_POST['id_agent'];
        $montant_initial = (float) $_POST['montant_initial'];
        $motif           = isset($_POST['motif']) ? trim($_POST['motif']) : null;

        if ($montant_initial <= 0) {
            throw new Exception("Le montant du prêt doit être supérieur à 0.");
        }

        // date_octroi automatique: CURDATE()
        // montant_restant = montant_initial, statut = 'en_cours'
        $sql = "INSERT INTO prets (
                    id_agent,
                    montant_initial,
                    montant_restant,
                    date_octroi,
                    date_echeance,
                    statut,
                    motif
                ) VALUES (
                    :id_agent,
                    :montant_initial,
                    :montant_restant,
                    CURDATE(),
                    NULL,
                    'en_cours',
                    :motif
                )";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id_agent', $id_agent, PDO::PARAM_INT);
        $stmt->bindValue(':montant_initial', $montant_initial);
        $stmt->bindValue(':montant_restant', $montant_initial);
        $stmt->bindValue(':motif', $motif);

        $stmt->execute();

        $_SESSION['popup'] = true;
        header('Location: prets.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['warning'] = "Erreur lors de l'enregistrement du prêt : " . $e->getMessage();
        header('Location: prets.php');
        exit;
    }
}

$_SESSION['warning'] = "Requête invalide.";
header('Location: prets.php');
exit;
