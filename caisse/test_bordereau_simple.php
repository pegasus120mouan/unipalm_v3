<?php
/**
 * Test simple pour vérifier l'envoi SMS du bordereau BDR-20251213-266-8132
 */

require_once '../inc/functions/connexion.php';

// Vérifier si le bordereau existe
$numero_bordereau = 'BDR-20251213-266-8132';

try {
    $stmt = $conn->prepare("SELECT b.*, a.nom, a.prenom, a.contact FROM bordereau b JOIN agents a ON b.id_agent = a.id_agent WHERE b.numero_bordereau = ?");
    $stmt->execute([$numero_bordereau]);
    $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bordereau) {
        echo "✅ Bordereau trouvé:<br>";
        echo "Agent: " . $bordereau['prenom'] . " " . $bordereau['nom'] . "<br>";
        echo "Contact: " . $bordereau['contact'] . "<br>";
        echo "Montant: " . number_format($bordereau['montant_total'], 0, ',', ' ') . " FCFA<br>";
        echo "Date: " . $bordereau['created_at'] . "<br><br>";
        
        // Test simple d'inclusion SMS
        if (file_exists('../inc/functions/envoiSMS/vendor/autoload.php')) {

            echo "✅ Autoload SMS trouvé<br>";
           require_once '../inc/functions/envoiSMS/vendor/autoload.php';
           require_once '../inc/functions/envoiSMS/config.php';
            
            if (class_exists('\App\OvlSmsService')) {
                echo "✅ Classe SMS disponible<br>";
                
                // Test d'envoi
                try {
                    $smsService = new \App\OvlSmsService(
                        'UNIPALM_HOvuHXr',
                        'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
                        '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
                    );
                    
                    $message = "Test SMS pour bordereau " . $numero_bordereau;
                    $result = $smsService->sendSms($bordereau['contact'], $message);
                    
                    if ($result['success']) {
                        echo "✅ SMS envoyé avec succès !<br>";
                        echo "ID: " . ($result['message_sid'] ?? 'N/A') . "<br>";
                    } else {
                        echo "❌ Échec SMS: " . ($result['error'] ?? 'Erreur inconnue') . "<br>";
                    }
                    
                } catch (Exception $e) {
                    echo "❌ Erreur SMS: " . $e->getMessage() . "<br>";
                }
                
            } else {
                echo "❌ Classe SMS non trouvée<br>";
            }
        } else {
            echo "❌ Autoload SMS non trouvé<br>";
        }
        
    } else {
        echo "❌ Bordereau non trouvé<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}
?>
