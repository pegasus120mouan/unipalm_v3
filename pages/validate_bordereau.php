<?php
require_once '../inc/functions/connexion.php';
// SMS géré via la configuration unifiée dans les fonctions

session_start();

// Fonction d'envoi SMS pour validation de bordereau
function envoyerSMSValidationBordereau($numero_telephone, $nom_agent, $prenom_agent, $numero_bordereau, $montant_total, $nombre_tickets) {
    try {
        // Inclure directement la classe SMS
        require_once '../inc/functions/envoiSMS/src/OvlSmsService.php';
        
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        // Créer le message de notification de validation
        $message = "UNIPALM - Bordereau Validé\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Votre bordereau a été validé avec succès !\n\n";
        $message .= "📋 Numéro : " . $numero_bordereau . "\n";
        $message .= "🎫 Tickets : " . $nombre_tickets . "\n";
        $message .= "💰 Montant : " . number_format($montant_total, 0, ',', ' ') . " FCFA\n\n";
        $message .= "✅ Vous pouvez maintenant vous présenter à la caisse pour le paiement.\n\n";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_bordereau = $_POST['id_bordereau'] ?? null;
    $action = $_POST['action'] ?? 'validate';

    if ($id_bordereau) {
        try {
            if ($action === 'validate') {
                // Valider le bordereau
                $stmt = $conn->prepare("
                    UPDATE bordereau 
                    SET date_validation_boss = NOW() 
                    WHERE id_bordereau = ?
                ");
                $stmt->execute([$id_bordereau]);
                
                // Récupérer les informations du bordereau et de l'agent pour l'envoi SMS
                $stmt_info = $conn->prepare("
                    SELECT b.numero_bordereau, b.montant_total, b.id_agent,
                           a.nom, a.prenom, a.contact,
                           (SELECT COUNT(*) FROM tickets WHERE numero_bordereau = b.numero_bordereau) as nombre_tickets
                    FROM bordereau b 
                    JOIN agents a ON b.id_agent = a.id_agent 
                    WHERE b.id_bordereau = ?
                ");
                $stmt_info->execute([$id_bordereau]);
                $bordereau_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
                
                if ($bordereau_info) {
                    // Envoyer le SMS de validation
                    $sms_result = envoyerSMSValidationBordereau(
                        $bordereau_info['contact'],
                        $bordereau_info['nom'],
                        $bordereau_info['prenom'],
                        $bordereau_info['numero_bordereau'],
                        $bordereau_info['montant_total'],
                        $bordereau_info['nombre_tickets']
                    );
                    
                    if ($sms_result['success']) {
                        $_SESSION['success'] = 'Bordereau validé avec succès - SMS envoyé à l\'agent au ' . $bordereau_info['contact'];
                        
                        // Log du succès SMS
                        error_log("SMS validation bordereau envoyé avec succès à " . $bordereau_info['contact'] . " pour le bordereau " . $bordereau_info['numero_bordereau']);
                    } else {
                        $_SESSION['success'] = 'Bordereau validé avec succès (SMS non envoyé: ' . ($sms_result['error'] ?? 'Erreur inconnue') . ')';
                        
                        // Log de l'échec SMS
                        error_log("Échec envoi SMS validation bordereau à " . $bordereau_info['contact'] . ": " . ($sms_result['error'] ?? 'Erreur inconnue'));
                    }
                } else {
                    $_SESSION['success'] = 'Bordereau validé avec succès (Informations bordereau non trouvées pour SMS)';
                }
            } else {
                // Annuler la validation
                $stmt = $conn->prepare("
                    UPDATE bordereau 
                    SET date_validation_boss = NULL 
                    WHERE id_bordereau = ?
                ");
                $stmt->execute([$id_bordereau]);
            }

            // Redirection vers la page des bordereaux après l'action
            header('Location: bordereaux.php');
            exit;
        } catch (PDOException $e) {
            // En cas d'erreur, tu peux aussi rediriger vers bordereaux.php avec un message d'erreur (optionnel)
            header('Location: bordereaux.php?error=1');
            exit;
        }
    } else {
        // Redirection avec message d'erreur si id_bordereau manquant
        header('Location: bordereaux.php?error=missing_id');
        exit;
    }
} else {
    // Si la méthode n'est pas POST, redirection simple
    header('Location: bordereaux.php');
    exit;
}
