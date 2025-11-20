<?php
require_once '../inc/functions/connexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['id_pret']) || empty($_POST['montant_initial'])) {
            throw new Exception("Identifiant du prêt et montant obligatoires.");
        }

        $id_pret         = (int) $_POST['id_pret'];
        $montant_initial = (float) $_POST['montant_initial'];
        $motif           = trim($_POST['motif'] ?? '');

        if ($montant_initial <= 0) {
            throw new Exception("Le montant du prêt doit être supérieur à 0.");
        }

        // Mettre à jour le montant initial et le motif
        $sql = "UPDATE prets
                SET montant_initial = :montant_initial,
                    motif = :motif
                WHERE id_pret = :id_pret";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':montant_initial', $montant_initial);
        $stmt->bindValue(':motif', $motif);
        $stmt->bindValue(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "Prêt #" . $id_pret . " modifié avec succès !";
        
        // Redirection personnalisée si spécifiée
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'prets.php';
        header('Location: ' . $redirect_to);
        exit;

    } catch (Exception $e) {
        $_SESSION['warning'] = "Erreur lors de la modification du prêt : " . $e->getMessage();
        header('Location: prets.php');
        exit;
    }
}

$_SESSION['warning'] = "Requête invalide.";
header('Location: prets.php');
exit;
