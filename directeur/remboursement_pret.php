<?php
require_once '../inc/functions/connexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['id_pret']) || empty($_POST['montant_paiement'])) {
            throw new Exception("Identifiant du prêt et montant à payer obligatoires.");
        }

        $id_pret         = (int) $_POST['id_pret'];
        $montant_paiement = (float) $_POST['montant_paiement'];

        if ($montant_paiement <= 0) {
            throw new Exception("Le montant à payer doit être supérieur à 0.");
        }

        // Récupérer le prêt pour connaître le montant restant actuel
        $sqlSelect = "SELECT montant_initial, COALESCE(montant_restant, 0) AS montant_restant
                      FROM prets
                      WHERE id_pret = :id_pret";
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->bindValue(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmtSelect->execute();
        $pret = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if (!$pret) {
            throw new Exception("Prêt introuvable.");
        }

        $montant_restant = (float)$pret['montant_restant'];

        if ($montant_paiement > $montant_restant) {
            throw new Exception("Le montant à payer ne peut pas dépasser le montant restant.");
        }

        $nouveau_restant = $montant_restant - $montant_paiement;

        // Statut : si tout est remboursé, on peut passer à 'solde'
        $statut = $nouveau_restant <= 0 ? 'solde' : 'en_cours';

        $sqlUpdate = "UPDATE prets
                      SET montant_restant = :montant_restant,
                          statut = :statut
                      WHERE id_pret = :id_pret";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':montant_restant', $nouveau_restant);
        $stmtUpdate->bindValue(':statut', $statut);
        $stmtUpdate->bindValue(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $_SESSION['popup'] = true;
        header('Location: prets.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['warning'] = "Erreur lors du remboursement du prêt : " . $e->getMessage();
        header('Location: prets.php');
        exit;
    }
}

$_SESSION['warning'] = "Requête invalide.";
header('Location: prets.php');
exit;
