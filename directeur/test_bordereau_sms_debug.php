<?php
/**
 * Test de diagnostic pour l'envoi SMS lors de la création de bordereau
 */

require_once '../inc/functions/connexion.php';
require_once '../inc/functions/envoiSMS/vendor/autoload.php';
require_once '../inc/functions/envoiSMS/config.php';

// Fonction d'envoi SMS pour bordereau (copie de bordereaux.php)
function envoyerSMSBordereau($numero_telephone, $nom_agent, $prenom_agent, $numero_bordereau, $montant_total, $nombre_tickets) {
    try {
        echo "<p><strong>🔧 Debug SMS:</strong> Début de l'envoi SMS</p>";
        echo "<p>Destinataire: $numero_telephone</p>";
        echo "<p>Agent: $prenom_agent $nom_agent</p>";
        echo "<p>Bordereau: $numero_bordereau</p>";
        
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        echo "<p>✅ Service SMS HSMS créé</p>";
        
        // Créer le message de notification de bordereau
        $message = "UNIPALM - Nouveau Bordereau\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Un nouveau bordereau a été généré pour vous :\n\n";
        $message .= "📋 Numéro : " . $numero_bordereau . "\n";
        $message .= "🎫 Tickets : " . $nombre_tickets . "\n";
        $message .= "💰 Montant : " . number_format($montant_total, 0, ',', ' ') . " FCFA\n\n";
        $message .= "Consultez votre espace agent pour plus de détails.\n\n";
        $message .= "Cordialement,\nÉquipe UNIPALM";
        
        echo "<p>📝 Message créé (" . strlen($message) . " caractères)</p>";
        echo "<pre>" . htmlspecialchars($message) . "</pre>";
        
        // Envoyer le SMS
        echo "<p>📤 Envoi du SMS en cours...</p>";
        $result = $smsService->sendSms($numero_telephone, $message);
        
        echo "<p>📊 Résultat de l'envoi:</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        return $result;
        
    } catch (Exception $e) {
        echo "<p>❌ Erreur lors de l'envoi: " . $e->getMessage() . "</p>";
        echo "<p>📍 Trace: " . $e->getTraceAsString() . "</p>";
        
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage()
        ];
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug SMS Bordereau</title></head><body>";
echo "<h1>🔍 Diagnostic SMS Bordereau BDR-20251213-266-8132</h1>";

// Récupérer les informations du bordereau créé - utiliser le format correct
$numero_bordereau = 'BORD-20251213-266-8132';

try {
    // Chercher le bordereau dans la base
    $stmt = $conn->prepare("SELECT b.*, a.nom, a.prenom, a.contact
                           FROM bordereau b 
                           JOIN agents a ON b.id_agent = a.id_agent 
                           WHERE b.numero_bordereau = ?");
    $stmt->execute([$numero_bordereau]);
    $bordereau_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bordereau_info) {
        echo "<h2>✅ Bordereau trouvé dans la base de données</h2>";
        echo "<p><strong>Agent:</strong> " . $bordereau_info['prenom'] . " " . $bordereau_info['nom'] . "</p>";
        echo "<p><strong>Contact:</strong> " . $bordereau_info['contact'] . "</p>";
        echo "<p><strong>Montant:</strong> " . number_format($bordereau_info['montant_total'], 0, ',', ' ') . " FCFA</p>";
        echo "<p><strong>Date création:</strong> " . $bordereau_info['created_at'] . "</p>";
        
        // Compter les tickets associés au bordereau
        $stmt_tickets = $conn->prepare("SELECT COUNT(*) as nombre_tickets FROM tickets WHERE numero_bordereau = ?");
        $stmt_tickets->execute([$numero_bordereau]);
        $tickets_count = $stmt_tickets->fetch(PDO::FETCH_ASSOC);
        $nombre_tickets = $tickets_count['nombre_tickets'] ?? 0;
        
        echo "<p><strong>Tickets:</strong> " . $nombre_tickets . "</p>";
        
        echo "<h2>🧪 Test d'envoi SMS</h2>";
        
        // Tester l'envoi SMS
        $sms_result = envoyerSMSBordereau(
            $bordereau_info['contact'],
            $bordereau_info['nom'],
            $bordereau_info['prenom'],
            $numero_bordereau,
            $bordereau_info['montant_total'],
            $nombre_tickets
        );
        
        if ($sms_result['success']) {
            echo "<h2>✅ SMS envoyé avec succès !</h2>";
            echo "<p>ID Message: " . ($sms_result['message_sid'] ?? 'N/A') . "</p>";
        } else {
            echo "<h2>❌ Échec de l'envoi SMS</h2>";
            echo "<p>Erreur: " . ($sms_result['error'] ?? 'Erreur inconnue') . "</p>";
        }
        
    } else {
        echo "<h2>❌ Bordereau non trouvé dans la base de données</h2>";
        echo "<p>Le bordereau <strong>$numero_bordereau</strong> n'existe pas ou a été supprimé.</p>";
        
        // Chercher des bordereaux récents
        $stmt = $conn->prepare("SELECT numero_bordereau, created_at FROM bordereau ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $recent_bordereaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>📋 Bordereaux récents:</h3>";
        foreach ($recent_bordereaux as $b) {
            echo "<p>• " . $b['numero_bordereau'] . " (créé le " . $b['created_at'] . ")</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur de base de données</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔧 Vérifications système</h2>";

// Vérifier les dépendances
echo "<h3>📦 Dépendances</h3>";
if (class_exists('\App\OvlSmsService')) {
    echo "<p>✅ Classe OvlSmsService disponible</p>";
} else {
    echo "<p>❌ Classe OvlSmsService non trouvée</p>";
}

if (defined('SMS_CLIENT_ID')) {
    echo "<p>✅ Constantes SMS définies</p>";
} else {
    echo "<p>❌ Constantes SMS non définies</p>";
}

// Vérifier la configuration PHP
echo "<h3>⚙️ Configuration PHP</h3>";
echo "<p>Version PHP: " . phpversion() . "</p>";
echo "<p>Extension cURL: " . (extension_loaded('curl') ? '✅ Activée' : '❌ Désactivée') . "</p>";
echo "<p>Extension JSON: " . (extension_loaded('json') ? '✅ Activée' : '❌ Désactivée') . "</p>";

echo "</body></html>";
?>
