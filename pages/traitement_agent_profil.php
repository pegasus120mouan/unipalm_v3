<?php
require_once '../inc/functions/connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agents.php');
    exit;
}

$id_agent = intval($_POST['id_agent'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id_agent <= 0) {
    $_SESSION['profil_message'] = 'ID agent invalide';
    $_SESSION['profil_type'] = 'danger';
    header('Location: agents.php');
    exit;
}

switch ($action) {
    case 'update_photo':
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['profil_message'] = 'Aucune photo sélectionnée';
            $_SESSION['profil_type'] = 'warning';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['profil_message'] = 'Type de fichier non autorisé (JPG, PNG, GIF, WEBP uniquement)';
            $_SESSION['profil_type'] = 'danger';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        $uploadDir = '../dossiers_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = 'agent_' . $id_agent . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            try {
                $stmt = $conn->prepare("UPDATE agents SET avatar = ?, date_modification = NOW() WHERE id_agent = ?");
                $stmt->execute([$newFileName, $id_agent]);
                
                $_SESSION['profil_message'] = 'Photo mise à jour avec succès !';
                $_SESSION['profil_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['profil_message'] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
                $_SESSION['profil_type'] = 'danger';
            }
        } else {
            $_SESSION['profil_message'] = 'Erreur lors de l\'upload de la photo';
            $_SESSION['profil_type'] = 'danger';
        }
        break;
        
    case 'update_profil':
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($contact)) {
            $_SESSION['profil_message'] = 'Tous les champs sont obligatoires';
            $_SESSION['profil_type'] = 'warning';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE agents SET nom = ?, prenom = ?, contact = ?, date_modification = NOW() WHERE id_agent = ?");
            $stmt->execute([$nom, $prenom, $contact, $id_agent]);
            
            $_SESSION['profil_message'] = 'Profil mis à jour avec succès !';
            $_SESSION['profil_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['profil_message'] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
            $_SESSION['profil_type'] = 'danger';
        }
        break;
        
    case 'update_pin':
        $ancien_pin = $_POST['ancien_pin'] ?? '';
        $nouveau_pin = $_POST['nouveau_pin'] ?? '';
        $confirmer_pin = $_POST['confirmer_pin'] ?? '';
        
        if (empty($ancien_pin) || empty($nouveau_pin) || empty($confirmer_pin)) {
            $_SESSION['profil_message'] = 'Tous les champs sont obligatoires';
            $_SESSION['profil_type'] = 'warning';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        if ($nouveau_pin !== $confirmer_pin) {
            $_SESSION['profil_message'] = 'Les nouveaux codes PIN ne correspondent pas';
            $_SESSION['profil_type'] = 'danger';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        if (!preg_match('/^[0-9]{6}$/', $nouveau_pin)) {
            $_SESSION['profil_message'] = 'Le code PIN doit contenir exactement 6 chiffres';
            $_SESSION['profil_type'] = 'danger';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        // Vérifier l'ancien PIN
        $stmt = $conn->prepare("SELECT code_pin FROM agents WHERE id_agent = ?");
        $stmt->execute([$id_agent]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$agent || $agent['code_pin'] !== $ancien_pin) {
            $_SESSION['profil_message'] = 'L\'ancien code PIN est incorrect';
            $_SESSION['profil_type'] = 'danger';
            header("Location: agent_profil.php?id=$id_agent");
            exit;
        }
        
        try {
            $stmt = $conn->prepare("UPDATE agents SET code_pin = ?, date_modification = NOW() WHERE id_agent = ?");
            $stmt->execute([$nouveau_pin, $id_agent]);
            
            $_SESSION['profil_message'] = 'Code PIN modifié avec succès !';
            $_SESSION['profil_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['profil_message'] = 'Erreur lors de la modification : ' . $e->getMessage();
            $_SESSION['profil_type'] = 'danger';
        }
        break;
        
    default:
        $_SESSION['profil_message'] = 'Action non reconnue';
        $_SESSION['profil_type'] = 'danger';
}

header("Location: agent_profil.php?id=$id_agent");
exit;
