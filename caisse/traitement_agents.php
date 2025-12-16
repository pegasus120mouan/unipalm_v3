<?php
require_once '../inc/functions/connexion.php';
session_start();

// Inclure le système SMS existant
require_once '../inc/functions/envoiSMS/vendor/autoload.php';
require_once '../inc/functions/envoiSMS/config.php';

/**
 * Génère un code PIN à 6 chiffres aléatoire
 * @return string Code PIN à 6 chiffres
 */
function genererCodePin() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

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

/**
 * Envoie un SMS de code PIN à un nouvel agent via HSMS
 * @param string $numero_telephone Numéro de téléphone de l'agent
 * @param string $nom_agent Nom de l'agent
 * @param string $prenom_agent Prénom de l'agent
 * @param string $code_pin Code PIN généré
 * @param string $numero_agent Numéro d'agent généré
 * @return array Résultat de l'envoi
 */
function envoyerSMSNouvelAgent($numero_telephone, $nom_agent, $prenom_agent, $code_pin, $numero_agent) {
    try {
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        // Créer le message de bienvenue
        $message = "Bienvenue chez UNIPALM !\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Votre compte agent a été créé avec succès.\n\n";
        $message .= "Votre numéro d'agent : " . $numero_agent . "\n";
        $message .= "Votre code PIN : " . $code_pin . "\n\n";
        $message .= "Gardez ces informations confidentielles.\n\n";
        $message .= "Cordialement,\nÉquipe UNIPALM";
        
        // Envoyer le SMS
        $result = $smsService->sendSms($numero_telephone, $message);
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage()
        ];
    }
}

// Debug - Log des données POST
file_put_contents('../debug_agents.txt', date('Y-m-d H:i:s') . " - POST reçu: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'un agent
    if (isset($_POST['add_agent'])) {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $contact = trim($_POST['contact']);
        $id_chef = $_POST['id_chef'];
        $cree_par = $_SESSION['user_id'] ?? 4; // ID de l'utilisateur connecté ou 4 par défaut
        
        // Debug - Vérifier la session
        file_put_contents('../debug_agents.txt', "Session user_id: " . ($_SESSION['user_id'] ?? 'NULL') . ", cree_par: $cree_par\n", FILE_APPEND);

        // Debug - Log des valeurs extraites
        file_put_contents('../debug_agents.txt', "Nom: '$nom', Prenom: '$prenom', Contact: '$contact', ID_Chef: '$id_chef'\n", FILE_APPEND);
        
        // Validation des données
        if (empty($nom) || empty($prenom) || empty($contact) || empty($id_chef)) {
            file_put_contents('../debug_agents.txt', "ERREUR: Validation échouée - champs vides\n", FILE_APPEND);
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
            
            // Générer le code PIN (6 chiffres uniquement)
            $code_pin_clair = genererCodePin();
            
            // Debug - Log avant insertion
            file_put_contents('../debug_agents.txt', "Tentative insertion - Numero: $numero_agent, PIN: $code_pin_clair\n", FILE_APPEND);
            file_put_contents('../debug_agents.txt', "Parametres: [$numero_agent, $nom, $prenom, $contact, $id_chef, $cree_par, pin_length=" . strlen($code_pin_clair) . "]\n", FILE_APPEND);
            
            // Insertion de l'agent (sans date_ajout car elle a CURRENT_TIMESTAMP par défaut)
            $stmt = $conn->prepare("INSERT INTO agents (numero_agent, nom, prenom, contact, id_chef, cree_par, code_pin) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Debug - Log de la requête préparée
            file_put_contents('../debug_agents.txt', "Requête SQL préparée\n", FILE_APPEND);
            
            $result = $stmt->execute([$numero_agent, $nom, $prenom, $contact, $id_chef, $cree_par, $code_pin_clair]);
            
            // Debug - Log immédiat après execute
            file_put_contents('../debug_agents.txt', "Execute terminé\n", FILE_APPEND);

            // Debug - Log du résultat
            file_put_contents('../debug_agents.txt', "Résultat insertion: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            file_put_contents('../debug_agents.txt', "Lignes affectées: " . $stmt->rowCount() . "\n", FILE_APPEND);

            if ($result) {
                // Envoyer le SMS avec le code PIN via HSMS
                $sms_result = envoyerSMSNouvelAgent($contact, $nom, $prenom, $code_pin_clair, $numero_agent);
                
                if ($sms_result['success']) {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Agent ajouté avec succès ! Numéro d'agent : " . $numero_agent . " | Code PIN envoyé par SMS au " . $contact;
                    $_SESSION['status'] = "success";
                    
                    // Log du succès SMS
                    file_put_contents('../debug_agents.txt', "SMS HSMS envoyé avec succès à $contact pour l'agent $numero_agent - ID: " . ($sms_result['message_sid'] ?? 'N/A') . "\n", FILE_APPEND);
                } else {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Agent ajouté avec succès ! Numéro d'agent : " . $numero_agent . " | Code PIN : " . $code_pin_clair . " (SMS non envoyé: " . ($sms_result['error'] ?? 'Erreur inconnue') . ")";
                    $_SESSION['status'] = "warning";
                    
                    // Log de l'échec SMS
                    file_put_contents('../debug_agents.txt', "Échec envoi SMS HSMS à $contact: " . ($sms_result['error'] ?? 'Erreur inconnue') . "\n", FILE_APPEND);
                }
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de l'ajout de l'agent";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            // Debug - Log de l'erreur PDO
            file_put_contents('../debug_agents.txt', "ERREUR PDO: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents('../debug_agents.txt', "Code erreur: " . $e->getCode() . "\n", FILE_APPEND);
            
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur base de données : " . $e->getMessage();
            $_SESSION['status'] = "error";
            
            // Log l'erreur pour debug
            error_log("Erreur ajout agent: " . $e->getMessage());
        } catch(Exception $e) {
            // Debug - Log des autres erreurs
            file_put_contents('../debug_agents.txt', "ERREUR GENERALE: " . $e->getMessage() . "\n", FILE_APPEND);
            
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
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

    // Renvoi du code PIN par SMS
    if (isset($_POST['action']) && $_POST['action'] === 'resend_pin' && isset($_POST['id_agent'])) {
        $id_agent = $_POST['id_agent'];
        
        try {
            // Récupérer les informations de l'agent
            $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
            $stmt->execute([$id_agent]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$agent) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent non trouvé !";
                $_SESSION['status'] = "error";
            } else {
                // Envoyer le SMS avec le code PIN existant
                $sms_result = envoyerSMSNouvelAgent(
                    $agent['contact'], 
                    $agent['nom'], 
                    $agent['prenom'], 
                    $agent['code_pin'], 
                    $agent['numero_agent']
                );
                
                if ($sms_result['success']) {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Code PIN renvoyé avec succès par SMS au " . $agent['contact'];
                    $_SESSION['status'] = "success";
                    
                    // Log du succès
                    file_put_contents('../debug_agents.txt', "PIN renvoyé avec succès à " . $agent['contact'] . " pour l'agent " . $agent['numero_agent'] . " - ID: " . ($sms_result['message_sid'] ?? 'N/A') . "\n", FILE_APPEND);
                } else {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Erreur lors du renvoi du PIN : " . ($sms_result['error'] ?? 'Erreur inconnue');
                    $_SESSION['status'] = "error";
                    
                    // Log de l'échec
                    file_put_contents('../debug_agents.txt', "Échec renvoi PIN à " . $agent['contact'] . ": " . ($sms_result['error'] ?? 'Erreur inconnue') . "\n", FILE_APPEND);
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