<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../inc/functions/connexion.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST['nom']);
    $prenoms = trim($_POST['prenoms']);
    $token = isset($_POST['token']) ? trim($_POST['token']) : null;
    $token = empty($token) ? null : $token; // Convertir chaîne vide en NULL
    $id_chef = isset($_POST['id_chef']) ? intval($_POST['id_chef']) : null;

    // Vérification des champs obligatoires
    if (empty($nom) || empty($prenoms)) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Nom et prénoms sont obligatoires.";
        header('Location: chef_equipe.php');
        exit;
    }

    try {
        if ($id_chef) {
            // Mise à jour d'un chef d'équipe
            $query = "UPDATE chef_equipe SET nom = :nom, prenoms = :prenoms, token = :token WHERE id_chef = :id_chef";
            $query_run = $conn->prepare($query);
            $data = [
                ':nom' => $nom,
                ':prenoms' => $prenoms,
                ':token' => $token,
                ':id_chef' => $id_chef,
            ];
            $query_execute = $query_run->execute($data);

            if ($query_execute) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Chef d'équipe mis à jour avec succès.";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la mise à jour des données.";
            }
        } else {
            // Insertion d'un nouveau chef d'équipe
            $query = "INSERT INTO chef_equipe (nom, prenoms, token) VALUES (:nom, :prenoms, :token)";
            $query_run = $conn->prepare($query);
            $data = [
                ':nom' => $nom,
                ':prenoms' => $prenoms,
                ':token' => $token,
            ];
            $query_execute = $query_run->execute($data);

            if ($query_execute) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Chef d'équipe ajouté avec succès.";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de l'ajout du chef d'équipe.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: chef_equipe.php');
    exit;
}

// Code pour la suppression d'un chef d'équipe
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_chef = intval($_GET['id']);

    try {
        // Suppression d'un chef d'équipe
        $query = "DELETE FROM chef_equipe WHERE id_chef = :id_chef";
        $query_run = $conn->prepare($query);
        $query_run->bindParam(':id_chef', $id_chef, PDO::PARAM_INT);

        if ($query_run->execute()) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Chef d'équipe supprimé avec succès.";
        } else {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur lors de la suppression du chef d'équipe.";
        }
    } catch (PDOException $e) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: chef_equipe.php');
    exit;
}

// Code pour la génération du token (une seule fois)
if (isset($_GET['action']) && $_GET['action'] === 'generate_token' && isset($_GET['id'])) {
    $id_chef = intval($_GET['id']);

    try {
        // Vérifier si le token n'existe pas déjà
        $check_query = "SELECT token FROM chef_equipe WHERE id_chef = :id_chef";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':id_chef', $id_chef, PDO::PARAM_INT);
        $check_stmt->execute();
        $chef = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($chef && empty($chef['token'])) {
            // Générer un token unique (8 caractères alphanumériques)
            $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            
            // Mettre à jour le chef avec le nouveau token
            $query = "UPDATE chef_equipe SET token = :token WHERE id_chef = :id_chef";
            $query_run = $conn->prepare($query);
            $query_run->bindParam(':token', $token, PDO::PARAM_STR);
            $query_run->bindParam(':id_chef', $id_chef, PDO::PARAM_INT);

            if ($query_run->execute()) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Token généré avec succès : " . $token;
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la génération du token.";
            }
        } else {
            $_SESSION['delete_pop'] = true;
            $_SESSION['message'] = "Ce chef d'équipe possède déjà un token.";
        }
    } catch (PDOException $e) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: chef_equipe.php');
    exit;
}
?>
