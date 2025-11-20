<?php
require_once '../inc/functions/connexion.php';
session_start();

/**
 * Génère un numéro d'agent unique au format AGT-25-CHEF-INITIALES+SEQUENCE
 * @param PDO $conn Connexion à la base de données
 * @param int $id_chef ID du chef d'équipe
 * @param string $nom_agent Nom de l'agent
 * @param string $prenom_agent Prénom de l'agent
 * @return string Numéro d'agent généré
 */
function genererNumeroAgent($conn, $id_chef, $nom_agent, $prenom_agent) {
    $annee_courte = date('y'); // Année sur 2 chiffres (25 pour 2025)
    
    // Récupérer le nom du chef et créer un code
    $stmt = $conn->prepare("SELECT nom, prenoms FROM chef_equipe WHERE id_chef = ?");
    $stmt->execute([$id_chef]);
    $chef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chef) {
        throw new Exception("Chef d'équipe non trouvé");
    }
    
    // Créer un code chef à partir du nom (3 premières lettres en majuscules)
    $code_chef = strtoupper(substr($chef['nom'], 0, 3));
    
    // Créer les initiales de l'agent (première lettre du nom + première lettre du prénom)
    $initiale_nom = strtoupper(substr($nom_agent, 0, 1));
    $initiale_prenom = strtoupper(substr($prenom_agent, 0, 1));
    $initiales_agent = $initiale_nom . $initiale_prenom;
    
    // Format: AGT-25-ZAL-YD (AGT + Année + Code Chef + Initiales Agent)
    $prefixe = "AGT-" . $annee_courte . "-" . $code_chef . "-" . $initiales_agent;
    
    // Récupérer le dernier numéro d'agent créé avec ces initiales pour ce chef cette année
    $stmt = $conn->prepare("
        SELECT numero_agent 
        FROM agents 
        WHERE numero_agent LIKE ? 
        ORDER BY numero_agent DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefixe . '%']);
    $dernier_numero = $stmt->fetchColumn();
    
    if ($dernier_numero) {
        // Extraire le numéro séquentiel et l'incrémenter
        // Format: AGT-25-ZAL-YD01 -> extraire les 2 derniers chiffres
        $sequence = (int)substr($dernier_numero, -2) + 1;
    } else {
        // Premier agent avec ces initiales pour ce chef cette année
        $sequence = 1;
    }
    
    // Formater avec des zéros à gauche (2 chiffres pour la séquence)
    $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
    $numero_agent = $prefixe . $sequence_format;
    
    // Vérifier l'unicité (sécurité supplémentaire)
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM agents WHERE numero_agent = ?");
    $stmt_check->execute([$numero_agent]);
    
    if ($stmt_check->fetchColumn() > 0) {
        // Si le numéro existe déjà, incrémenter jusqu'à trouver un numéro libre
        do {
            $sequence++;
            $sequence_format = str_pad($sequence, 2, '0', STR_PAD_LEFT);
            $numero_agent = $prefixe . $sequence_format;
            $stmt_check->execute([$numero_agent]);
        } while ($stmt_check->fetchColumn() > 0);
    }
    
    return $numero_agent;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'un agent
    if (isset($_POST['add_agent'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $contact = trim($_POST['contact']);
        $id_chef = $_POST['id_chef'];
        $cree_par = $_SESSION['user_id']; // ID de l'utilisateur connecté

        // Validation des données
        if (empty($nom) || empty($prenom) || empty($contact) || empty($id_chef)) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Tous les champs sont obligatoires !";
            $_SESSION['status'] = "error";
            header('Location: agents.php');
            exit;
        }

        try {
            // Vérifier que le chef d'équipe existe
            $stmt_check = $conn->prepare("SELECT id_chef FROM chef_equipe WHERE id_chef = ?");
            $stmt_check->execute([$id_chef]);
            if (!$stmt_check->fetch()) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Chef d'équipe invalide !";
                $_SESSION['status'] = "error";
                header('Location: agents.php');
                exit;
            }

            // Générer un numéro d'agent unique
            $numero_agent = genererNumeroAgent($conn, $id_chef, $nom, $prenom);
            
            // Insertion de l'agent
            $stmt = $conn->prepare("INSERT INTO agents (numero_agent, nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$numero_agent, $nom, $prenom, $contact, $id_chef, $cree_par]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent ajouté avec succès ! Numéro d'agent : " . $numero_agent;
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de l'ajout de l'agent";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur base de données : " . $e->getMessage();
            $_SESSION['status'] = "error";
            
            // Log l'erreur pour debug
            error_log("Erreur ajout agent: " . $e->getMessage());
        }
        
        header('Location: agents.php');
        exit;
    }

    // Modification d'un agent
    if (isset($_POST['update_agent'])) {
        $id_agent = $_POST['id_agent'];
        $nom = $_POST['nom'];
        $prenoms = $_POST['prenoms'];
        $contact = $_POST['contact'];

        try {
            $stmt = $conn->prepare("UPDATE agents SET nom = ?, prenom = ?, contact = ?, date_modification = NOW() WHERE id_agent = ?");
            $result = $stmt->execute([$nom, $prenoms, $contact, $id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent modifié avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la modification de l'agent";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }

    // Changement de chef d'équipe
    if (isset($_POST['change_chef'])) {
        $id_agent = $_POST['id_agent'];
        $nouveau_chef = $_POST['nouveau_chef'];

        try {
            $stmt = $conn->prepare("UPDATE agents SET id_chef = ?, date_modification = NOW() WHERE id_agent = ?");
            $result = $stmt->execute([$nouveau_chef, $id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Chef d'équipe modifié avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors du changement de chef d'équipe";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }

    // Suppression d'un agent (méthode POST)
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id_agent'])) {
        $id_agent = $_POST['id_agent'];
        
        // Debug
        file_put_contents('../debug_delete.txt', date('Y-m-d H:i:s') . " - Suppression POST ID: " . $id_agent . "\n", FILE_APPEND);

        try {
            // Vérifier si l'agent existe
            $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
            $stmt->execute([$id_agent]);
            $agent = $stmt->fetch();

            if (!$agent) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent non trouvé !";
                $_SESSION['status'] = "error";
            } else {
                // Suppression logique - marquer comme supprimé au lieu de supprimer physiquement
                $stmt = $conn->prepare("UPDATE agents SET date_suppression = NOW() WHERE id_agent = ?");
                $result = $stmt->execute([$id_agent]);
                
                // Debug détaillé
                $affected_rows = $stmt->rowCount();
                file_put_contents('../debug_delete.txt', "  - Résultat requête UPDATE: " . ($result ? 'true' : 'false') . "\n", FILE_APPEND);
                file_put_contents('../debug_delete.txt', "  - Lignes affectées: " . $affected_rows . "\n", FILE_APPEND);

                if ($result && $affected_rows > 0) {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Agent supprimé avec succès !";
                    $_SESSION['status'] = "success";
                } else {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Erreur lors de la suppression de l'agent (aucune ligne affectée)";
                    $_SESSION['status'] = "error";
                }
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }
}

// Suppression d'un agent (méthode GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_agent = $_GET['id'];
    
    // Debug - créer un fichier temporaire pour voir si on arrive ici
    file_put_contents('../debug_delete.txt', date('Y-m-d H:i:s') . " - Tentative suppression ID: " . $id_agent . "\n", FILE_APPEND);

    try {
        // Vérifier si l'agent existe
        $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
        $stmt->execute([$id_agent]);
        $agent = $stmt->fetch();

        if (!$agent) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Agent non trouvé !";
            $_SESSION['status'] = "error";
        } else {
            // Supprimer l'agent
            $stmt = $conn->prepare("DELETE FROM agents WHERE id_agent = ?");
            $result = $stmt->execute([$id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent supprimé avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la suppression de l'agent";
                $_SESSION['status'] = "error";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
        $_SESSION['status'] = "error";
    }

    header('Location: agents.php');
    exit;
}

// Redirection par défaut
header('Location: agents.php');
exit;