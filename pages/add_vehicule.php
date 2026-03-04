<?php
session_start();
require_once '../inc/functions/connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $type_vehicule = trim($_POST['type_vehicule'] ?? 'voiture');
    
    if (!empty($matricule)) {
        try {
            // Vérifier si le matricule existe déjà
            $stmtCheck = $conn->prepare("SELECT vehicules_id FROM vehicules WHERE matricule_vehicule = ?");
            $stmtCheck->execute([$matricule]);
            
            if ($stmtCheck->fetch()) {
                $_SESSION['error'] = "Ce matricule existe déjà. Impossible d'ajouter un doublon.";
                header('Location: vehicules.php');
                exit();
            }
            
            // Insérer le véhicule
            $stmt = $conn->prepare("INSERT INTO vehicules (matricule_vehicule, type_vehicule, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$matricule, $type_vehicule]);
            
            $_SESSION['success'] = "Véhicule ajouté avec succès";
            header('Location: vehicules.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de l'ajout du véhicule: " . $e->getMessage();
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
