<?php
session_start();
require_once '../inc/functions/connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $matricule = trim($_POST['matricule'] ?? '');
    $type_vehicule = trim($_POST['type_vehicule'] ?? 'voiture');
    
    if (!empty($id) && !empty($matricule)) {
        try {
            // Vérifier si le matricule existe déjà pour un autre véhicule
            $stmtCheck = $conn->prepare("SELECT vehicules_id FROM vehicules WHERE matricule_vehicule = ? AND vehicules_id != ?");
            $stmtCheck->execute([$matricule, $id]);
            
            if ($stmtCheck->fetch()) {
                $_SESSION['error'] = "Ce matricule existe déjà pour un autre véhicule.";
                header('Location: vehicules.php');
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE vehicules SET matricule_vehicule = ?, type_vehicule = ?, updated_at = NOW() WHERE vehicules_id = ?");
            $stmt->execute([$matricule, $type_vehicule, $id]);
            
            $_SESSION['success'] = "Véhicule modifié avec succès";
            header('Location: vehicules.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la modification du véhicule: " . $e->getMessage();
            header('Location: vehicules.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs";
        header('Location: vehicules.php');
        exit();
    }
}
?>
