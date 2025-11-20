<?php
require_once '../inc/functions/connexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['id_pret'])) {
            throw new Exception("Identifiant du prêt manquant.");
        }

        $id_pret = (int) $_POST['id_pret'];

        // Option : vérifier l'existence du prêt avant suppression
        $checkSql = "SELECT id_pret FROM prets WHERE id_pret = :id_pret";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindValue(':id_pret', $id_pret, PDO::PARAM_INT);
        $checkStmt->execute();
        $pret = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$pret) {
            throw new Exception("Prêt introuvable.");
        }

        $sql = "DELETE FROM prets WHERE id_pret = :id_pret";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['popup'] = true;
        header('Location: prets.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['warning'] = "Erreur lors de la suppression du prêt : " . $e->getMessage();
        header('Location: prets.php');
        exit;
    }
}

$_SESSION['warning'] = "Requête invalide.";
header('Location: prets.php');
exit;
