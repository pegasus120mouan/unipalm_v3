<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/log_functions.php';
require_once '../inc/functions/envoiSMS/vendor/autoload.php';
require_once '../inc/functions/envoiSMS/config.php';

session_start();

// Fonction d'envoi SMS pour paiement de bordereau
function envoyerSMSPaiementBordereau($numero_telephone, $nom_agent, $prenom_agent, $numero_bordereau, $montant_total, $montant_paye, $montant_reste) {
    try {
        // Inclure directement la classe SMS
        require_once '../inc/functions/envoiSMS/src/OvlSmsService.php';
        
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        // Créer le message de notification de paiement
        $message = "UNIPALM - Paiement Reçu\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Un paiement a été effectué sur votre bordereau :\n\n";
        $message .= "📋 Numéro : " . $numero_bordereau . "\n";
        $message .= "💰 Montant total : " . number_format($montant_total, 0, ',', ' ') . " FCFA\n";
        $message .= "✅ Montant payé : " . number_format($montant_paye, 0, ',', ' ') . " FCFA\n";
        $message .= "⏳ Reste à payer : " . number_format($montant_reste, 0, ',', ' ') . " FCFA\n\n";
        
        if ($montant_reste <= 0) {
            $message .= "🎉 Félicitations ! Votre bordereau est maintenant entièrement soldé.\n\n";
        } else {
            $message .= "ℹ️ Paiement partiel effectué. Solde restant à régler.\n\n";
        }
        
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_paiement'])) {
    try {
        $conn->beginTransaction();
        writeLog("Début de l'enregistrement du paiement");

        // Récupérer les informations du caissier
        $stmt = $conn->prepare("SELECT CONCAT(nom, ' ', prenoms) as nom_caissier FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $caissier = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validation des données
        if (!isset($_POST['montant']) || empty($_POST['montant'])) {
            throw new Exception("Le montant est requis");
        }
        if (!isset($_POST['source_paiement']) || empty($_POST['source_paiement'])) {
            throw new Exception("La source de paiement est requise");
        }

        $montant = floatval($_POST['montant']);
        $source_paiement = $_POST['source_paiement'];
        $type = $_POST['type'];
        $status = $_POST['status'];
        
        // Validation du numéro de chèque si nécessaire
        $numero_cheque = null;
        if ($source_paiement === 'cheque') {
            if (!isset($_POST['numero_cheque']) || empty(trim($_POST['numero_cheque']))) {
                throw new Exception("Le numéro de chèque est obligatoire pour les paiements par chèque");
            }
            $numero_cheque = trim($_POST['numero_cheque']);
            
            // Vérifier l'unicité du numéro de chèque
            $stmt = $conn->prepare("SELECT COUNT(*) FROM recus_paiements WHERE numero_cheque = ? AND numero_cheque IS NOT NULL");
            $stmt->execute([$numero_cheque]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Ce numéro de chèque a déjà été utilisé");
            }
        }

        if ($montant <= 0) {
            throw new Exception("Le montant doit être supérieur à 0");
        }

        // Récupérer le solde actuel
        $stmt = $conn->prepare("SELECT COALESCE(MAX(solde), 0) as solde FROM transactions");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $solde_actuel = floatval($result['solde']);
        
        writeLog("Solde actuel avant paiement: " . $solde_actuel);

        // Vérifier si le solde est suffisant (sauf pour les paiements par chèque)
        if ($source_paiement !== 'cheque' && $solde_actuel < $montant) {
            throw new Exception("Solde insuffisant pour effectuer ce paiement. Solde actuel : " . number_format($solde_actuel, 0, ',', ' ') . " FCFA");
        }

        // Calculer le nouveau solde (pas de débit pour les chèques)
        $nouveau_solde = ($source_paiement === 'cheque') ? $solde_actuel : ($solde_actuel - $montant);
        writeLog("Nouveau solde calculé: " . $nouveau_solde);

        // Variables pour le reçu
        $id_document = null;
        $numero_document = null;
        $id_agent = null;
        $nom_agent = null;
        $contact_agent = null;
        $nom_usine = null;
        $matricule_vehicule = null;
        $montant_total = 0;
        $montant_precedent = 0;
        $type_document = '';

        // Vérifier si c'est un ticket, un bordereau ou une demande
        if (isset($_POST['id_ticket'])) {
            $id_ticket = $_POST['id_ticket'];
            $numero_ticket = $_POST['numero_ticket'];
            
            // Récupérer les informations du ticket et de l'agent
            $stmt = $conn->prepare("
                SELECT t.*, 
                    CONCAT(a.nom, ' ', a.prenom) as agent_nom,
                    a.contact as agent_contact,
                    a.id_agent,
                    us.nom_usine,
                    v.matricule_vehicule,
                    COALESCE(t.montant_payer, 0) as montant_payer
                FROM tickets t
                LEFT JOIN agents a ON t.id_agent = a.id_agent
                LEFT JOIN usines us ON t.id_usine = us.id_usine
                LEFT JOIN vehicules v ON t.vehicule_id = v.vehicules_id
                WHERE t.id_ticket = ?
            ");
            $stmt->execute([$id_ticket]);
            $ticket_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $id_document = $id_ticket;
            $numero_document = $numero_ticket;
            $id_agent = $ticket_info['id_agent'];
            $nom_agent = $ticket_info['agent_nom'];
            $contact_agent = $ticket_info['agent_contact'];
            $nom_usine = $ticket_info['nom_usine'];
            $matricule_vehicule = $ticket_info['matricule_vehicule'];
            $montant_total = $ticket_info['montant_paie'];
            $montant_precedent = $ticket_info['montant_payer'];
            $type_document = 'ticket';

            // Calculer les nouveaux montants
            $nouveau_montant_payer = $montant_precedent + $montant;
            $nouveau_montant_reste = $montant_total - $nouveau_montant_payer;

            // Mettre à jour le ticket
            $stmt = $conn->prepare("
                UPDATE tickets 
                SET montant_payer = ?,
                    montant_reste = ?,
                    date_paie = NOW() 
                WHERE id_ticket = ?
            ");
            $stmt->execute([$nouveau_montant_payer, $nouveau_montant_reste, $id_ticket]);
            writeLog("Ticket #$id_ticket mis à jour avec montant_payer=$nouveau_montant_payer, montant_reste=$nouveau_montant_reste");

        } elseif (isset($_POST['id_bordereau'])) {
            $id_bordereau = $_POST['id_bordereau'];
            $numero_bordereau = $_POST['numero_bordereau'];
            
            // Récupérer les informations du bordereau et de l'agent
            $stmt = $conn->prepare("
                SELECT b.*, 
                    CONCAT(a.nom, ' ', a.prenom) as agent_nom,
                    a.contact as agent_contact,
                    a.id_agent,
                    COALESCE(b.montant_payer, 0) as montant_payer
                FROM bordereau b
                LEFT JOIN agents a ON b.id_agent = a.id_agent
                WHERE b.id_bordereau = ?
            ");
            $stmt->execute([$id_bordereau]);
            $bordereau_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $id_document = $id_bordereau;
            $numero_document = $numero_bordereau;
            $id_agent = $bordereau_info['id_agent'];
            $nom_agent = $bordereau_info['agent_nom'];
            $contact_agent = $bordereau_info['agent_contact'];
            $montant_total = $bordereau_info['montant_total'];
            $montant_precedent = $bordereau_info['montant_payer'];
            $type_document = 'bordereau';

            // Calculer les nouveaux montants
            $nouveau_montant_payer = $montant_precedent + $montant;
            $nouveau_montant_reste = $montant_total - $nouveau_montant_payer;

            // Mettre à jour le bordereau
            $stmt = $conn->prepare("
                UPDATE bordereau 
                SET montant_payer = ?,
                    montant_reste = ?,
                    date_paie = NOW() 
                WHERE id_bordereau = ?
            ");
            $stmt->execute([$nouveau_montant_payer, $nouveau_montant_reste, $id_bordereau]);
            writeLog("Bordereau #$id_bordereau mis à jour avec montant_payer=$nouveau_montant_payer, montant_reste=$nouveau_montant_reste");
            
            // Envoyer le SMS de notification de paiement si on a les informations de l'agent
            if ($contact_agent && $nom_agent) {
                // Séparer nom et prénom
                $nom_parts = explode(' ', $nom_agent, 2);
                $nom = isset($nom_parts[1]) ? $nom_parts[1] : $nom_parts[0];
                $prenom = isset($nom_parts[1]) ? $nom_parts[0] : '';
                
                $sms_result = envoyerSMSPaiementBordereau(
                    $contact_agent,
                    $nom,
                    $prenom,
                    $numero_bordereau,
                    $montant_total,
                    $nouveau_montant_payer,
                    $nouveau_montant_reste
                );
                
                if ($sms_result['success']) {
                    writeLog("SMS paiement bordereau envoyé avec succès à " . $contact_agent . " pour le bordereau " . $numero_bordereau);
                } else {
                    writeLog("Échec envoi SMS paiement bordereau à " . $contact_agent . ": " . ($sms_result['error'] ?? 'Erreur inconnue'));
                }
            }
        } else {
            // C'est une demande
            $id_demande = $_POST['id_demande'];
            $numero_demande = $_POST['numero_demande'];
            
            // Récupérer les informations de la demande
            $stmt = $conn->prepare("
                SELECT 
                    d.montant,
                    d.statut,
                    d.numero_demande,
                    COALESCE(d.montant_payer, 0) as montant_payer,
                    d.montant - COALESCE(d.montant_payer, 0) as montant_reste,
                    CONCAT(u.nom, ' ', u.prenoms) as demandeur_nom,
                    u.id as id_demandeur
                FROM demande_sortie d
                LEFT JOIN utilisateurs u ON d.id_utilisateur = u.id
                WHERE d.id_demande = ?
            ");
            $stmt->execute([$id_demande]);
            $demande_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$demande_info) {
                throw new Exception("Demande non trouvée");
            }

            if ($demande_info['statut'] !== 'approuve' && $demande_info['statut'] !== 'paye') {
                throw new Exception("Cette demande n'est pas approuvée");
            }

            $id_document = $id_demande;
            $numero_document = $numero_demande;
            $id_agent = $demande_info['id_demandeur'];
            $nom_agent = $demande_info['demandeur_nom'];
            $montant_total = $demande_info['montant'];
            $montant_precedent = $demande_info['montant_payer'];
            $type_document = 'demande';

            // Calculer les nouveaux montants
            $nouveau_montant_paye = $montant_precedent + $montant;
            $nouveau_montant_reste = $montant_total - $nouveau_montant_paye;
            $nouveau_statut = $nouveau_montant_reste <= 0 ? 'paye' : 'approuve';

            // Mettre à jour la demande
            $stmt = $conn->prepare("
                UPDATE demande_sortie 
                SET montant_payer = :montant_payer,
                    montant_reste = :montant_reste,
                    statut = :statut,
                    date_paiement = NOW(),
                    paye_par = :paye_par
                WHERE id_demande = :id_demande
            ");

            $stmt->bindValue(':montant_payer', $nouveau_montant_paye, PDO::PARAM_STR);
            $stmt->bindValue(':montant_reste', $nouveau_montant_reste, PDO::PARAM_STR);
            $stmt->bindValue(':statut', $nouveau_statut, PDO::PARAM_STR);
            $stmt->bindValue(':paye_par', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':id_demande', $id_demande, PDO::PARAM_INT);
            $stmt->execute();
            writeLog("Demande #$id_demande mise à jour avec montant_payer=$nouveau_montant_paye, montant_reste=$nouveau_montant_reste, statut=$nouveau_statut");
        }

        // Créer la transaction seulement si ce n'est pas un paiement par chèque
        $id_transaction = null;
        if ($source_paiement !== 'cheque') {
            $motifs = "Paiement " . ($type_document === 'demande' ? "de la" : "du") . " " . $type_document . " " . $numero_document;
            
            $stmt = $conn->prepare("
                INSERT INTO transactions (
                    type_transaction, 
                    montant, 
                    date_transaction, 
                    motifs, 
                    id_utilisateur,
                    solde,
                    numero_cheque
                ) VALUES (
                    'paiement',
                    :montant,
                    NOW(),
                    :motifs,
                    :id_utilisateur,
                    :solde,
                    :numero_cheque
                )
            ");
            
            $stmt->bindValue(':montant', $montant, PDO::PARAM_STR);
            $stmt->bindValue(':motifs', $motifs, PDO::PARAM_STR);
            $stmt->bindValue(':id_utilisateur', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':solde', $nouveau_solde, PDO::PARAM_STR);
            $stmt->bindValue(':numero_cheque', null, PDO::PARAM_STR);
            $stmt->execute();
            $id_transaction = $conn->lastInsertId();
            writeLog("Transaction de paiement créée #$id_transaction, nouveau solde: $nouveau_solde");
        } else {
            // Pour les chèques, on ne crée pas de transaction de caisse
            writeLog("Paiement par chèque - aucune transaction de caisse créée, solde préservé: $nouveau_solde");
        }

        // Générer un numéro de reçu unique
        $numero_recu = date('Ymd') . sprintf("%04d", rand(1, 9999));

        // Créer le reçu
        $stmt = $conn->prepare("
            INSERT INTO recus_paiements (
                numero_recu, type_document, id_document, numero_document,
                montant_total, montant_paye, montant_precedent, reste_a_payer,
                id_agent, nom_agent, contact_agent, nom_usine, matricule_vehicule,
                id_caissier, nom_caissier, source_paiement, id_transaction, numero_cheque
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $numero_recu, $type_document, $id_document, $numero_document,
            $montant_total, $montant, $montant_precedent, $nouveau_montant_reste,
            $id_agent, $nom_agent, $contact_agent, $nom_usine, $matricule_vehicule,
            $_SESSION['user_id'], $caissier['nom_caissier'], $source_paiement, $id_transaction, $numero_cheque
        ]);

        $conn->commit();
        writeLog("Paiement enregistré avec succès");
        
        // Stocker les informations pour le modal de succès
        $_SESSION['paiement_success'] = true;
        $_SESSION['success_message'] = "Paiement effectué avec succès !";
        $_SESSION['nouveau_solde'] = $nouveau_solde;
        $_SESSION['montant_paye'] = $montant;
        $_SESSION['numero_recu'] = $numero_recu;
        $_SESSION['id_recu_pdf'] = $conn->lastInsertId();
        $_SESSION['type_document'] = $type_document;
        $_SESSION['numero_document'] = $numero_document;
        
        // Redirection vers la page d'origine avec le modal de succès
        $redirect_page = isset($_POST['redirect_page']) ? $_POST['redirect_page'] : 'paiements.php';
        header("Location: " . $redirect_page . "?paiement_success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        writeLog("ERREUR: " . $e->getMessage());
        writeLog("Trace: " . $e->getTraceAsString());
        $_SESSION['error_message'] = "Erreur lors du paiement : " . $e->getMessage();
        header("Location: paiements.php?type=" . urlencode($type) . "&status=" . urlencode($status));
        exit;
    }
}

$_SESSION['error_message'] = "Erreur : requête invalide";
header("Location: paiements.php");
exit;
