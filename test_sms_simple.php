<?php
/**
 * Test SMS simple - diagnostic rapide
 */

echo "<h1>Test SMS Simple - UNIPALM</h1>\n";
echo "<p>Date/Heure: " . date('Y-m-d H:i:s') . "</p>\n";

// Test 1: Vérifier l'existence du fichier OvlSmsService
$sms_file = __DIR__ . '/inc/functions/envoiSMS/src/OvlSmsService.php';
echo "<h2>1. Vérification du fichier OvlSmsService.php</h2>\n";
echo "Chemin testé: " . $sms_file . "<br>\n";
if (file_exists($sms_file)) {
    echo "✅ Fichier trouvé<br>\n";
    require_once $sms_file;
    echo "✅ Fichier inclus avec succès<br>\n";
} else {
    echo "❌ Fichier non trouvé<br>\n";
    echo "Contenu du répertoire src:<br>\n";
    $src_dir = __DIR__ . '/inc/functions/envoiSMS/src/';
    if (is_dir($src_dir)) {
        $files = scandir($src_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- " . $file . "<br>\n";
            }
        }
    } else {
        echo "❌ Répertoire src non trouvé<br>\n";
    }
    exit;
}

// Test 2: Créer l'instance SMS
echo "<h2>2. Test de création de l'instance SMS</h2>\n";
try {
    $smsService = new \App\OvlSmsService(
        'UNIPALM_HOvuHXr',
        'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
        '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
    );
    echo "✅ Instance OvlSmsService créée avec succès<br>\n";
} catch (Exception $e) {
    echo "❌ Erreur lors de la création: " . $e->getMessage() . "<br>\n";
    exit;
}

// Test 3: Test d'envoi SMS
echo "<h2>3. Test d'envoi SMS</h2>\n";
$test_phone = '0769292989';
$test_message = 'Test SMS UNIPALM - ' . date('H:i:s');

try {
    $result = $smsService->sendSms($test_phone, $test_message);
    echo "<h3>Résultat:</h3>\n";
    echo "<pre>" . print_r($result, true) . "</pre>\n";
    
    if ($result['success']) {
        echo "✅ SMS envoyé avec succès!<br>\n";
    } else {
        echo "❌ Échec de l'envoi SMS<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>\n";
}

echo "<hr>\n";
echo "<p>Test terminé à " . date('Y-m-d H:i:s') . "</p>\n";
?>
