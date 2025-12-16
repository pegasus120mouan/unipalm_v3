<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

echo "=== Test de connexion HSMS ===\n\n";

// Affichage des variables d'environnement
echo "Variables d'environnement:\n";
echo "HSMS_CLIENT_ID: " . ($_ENV['HSMS_CLIENT_ID'] ?? 'NON DÉFINI') . "\n";
echo "HSMS_CLIENT_SECRET: " . (isset($_ENV['HSMS_CLIENT_SECRET']) ? substr($_ENV['HSMS_CLIENT_SECRET'], 0, 10) . '...' : 'NON DÉFINI') . "\n";
echo "HSMS_TOKEN: " . (isset($_ENV['HSMS_TOKEN']) ? substr($_ENV['HSMS_TOKEN'], 0, 10) . '...' : 'NON DÉFINI') . "\n";
echo "SMS_PROVIDER: " . ($_ENV['SMS_PROVIDER'] ?? 'NON DÉFINI') . "\n\n";

// Test de création du service
try {
    $smsService = createSmsService();
    echo "✅ Service SMS créé avec succès\n\n";
    
    // Test de vérification du solde
    echo "=== Test de vérification du solde ===\n";
    $balanceResult = $smsService->checkSmsBalance();
    
    if ($balanceResult['success']) {
        echo "✅ Solde récupéré avec succès\n";
        echo "Solde: " . $balanceResult['balance'] . " SMS\n";
        echo "Application: " . $balanceResult['application'] . "\n\n";
    } else {
        echo "❌ Erreur lors de la vérification du solde\n";
        echo "Erreur: " . $balanceResult['error'] . "\n\n";
    }
    
    // Test d'envoi SMS
    echo "=== Test d'envoi SMS ===\n";
    $testNumber = "2250787703000"; // Numéro de test fourni
    $testMessage = "Test HSMS - " . date('H:i:s');
    
    echo "Numéro de test: $testNumber\n";
    echo "Message: $testMessage\n\n";
    
    $result = $smsService->sendSms($testNumber, $testMessage);
    
    if ($result['success']) {
        echo "✅ SMS envoyé avec succès\n";
        echo "ID du message: " . $result['message_sid'] . "\n";
        echo "Statut: " . $result['status'] . "\n";
    } else {
        echo "❌ Erreur lors de l'envoi du SMS\n";
        echo "Erreur: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création du service: " . $e->getMessage() . "\n";
}

echo "\n=== Fin du test ===\n";
?>
