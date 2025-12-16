<?php
/**
 * Test SMS simple pour production
 * URL: http://alerte.unipalm-ci.site/test_sms_simple_production.php
 */

echo "<h1>📱 Test SMS Production Simple</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Vérifier la configuration SMS
echo "<h2>1. Test configuration SMS</h2>";
$config_path = __DIR__ . '/inc/functions/envoiSMS/config.php';

if (file_exists($config_path)) {
    echo "✅ Fichier config.php trouvé<br>";
    
    try {
        require_once $config_path;
        echo "✅ Configuration chargée<br>";
        
        // Vérifier createSmsService
        if (function_exists('createSmsService')) {
            echo "✅ Fonction createSmsService disponible<br>";
            
            try {
                $smsService = createSmsService();
                echo "✅ Service SMS créé avec succès<br>";
                echo "Type de service: " . get_class($smsService) . "<br>";
                
                // Test d'envoi SMS
                echo "<h2>2. Test envoi SMS</h2>";
                $test_phone = '0769292989';
                $test_message = 'Test SMS Production UNIPALM - ' . date('H:i:s');
                
                $result = $smsService->sendSms($test_phone, $test_message);
                
                if ($result['success']) {
                    echo "✅ <strong>SMS envoyé avec succès!</strong><br>";
                    echo "ID Message: " . ($result['message_id'] ?? 'N/A') . "<br>";
                    echo "Réponse: " . json_encode($result) . "<br>";
                } else {
                    echo "❌ <strong>Échec envoi SMS</strong><br>";
                    echo "Erreur: " . ($result['error'] ?? 'Erreur inconnue') . "<br>";
                    echo "Détails: " . json_encode($result) . "<br>";
                }
                
            } catch (Exception $e) {
                echo "❌ Erreur création service: " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "❌ Fonction createSmsService non trouvée<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur chargement config: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Fichier config.php non trouvé à: $config_path<br>";
    
    // Essayer de lister le contenu du dossier
    $sms_dir = __DIR__ . '/inc/functions/envoiSMS';
    if (is_dir($sms_dir)) {
        echo "<br>Contenu du dossier envoiSMS:<br>";
        $files = scandir($sms_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- $file<br>";
            }
        }
    } else {
        echo "❌ Dossier envoiSMS non trouvé<br>";
    }
}

echo "<hr>";
echo "<h2>3. Informations de debug</h2>";
echo "<strong>Chemin actuel:</strong> " . __DIR__ . "<br>";
echo "<strong>Serveur:</strong> " . $_SERVER['HTTP_HOST'] . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Vérifier les variables d'environnement SMS
echo "<br><strong>Variables SMS:</strong><br>";
$sms_vars = ['SMS_PROVIDER', 'HSMS_CLIENT_ID', 'HSMS_CLIENT_SECRET', 'HSMS_TOKEN'];
foreach ($sms_vars as $var) {
    $value = $_ENV[$var] ?? 'NON DÉFINIE';
    if ($var === 'HSMS_CLIENT_SECRET' || $var === 'HSMS_TOKEN') {
        $value = !empty($_ENV[$var]) ? '***MASQUÉ***' : 'NON DÉFINIE';
    }
    echo "- $var: $value<br>";
}
?>
