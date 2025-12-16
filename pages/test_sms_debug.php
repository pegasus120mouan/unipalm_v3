<?php
/**
 * Test debug pour identifier le problème de chargement de classe
 * URL: http://alerte.unipalm-ci.site/pages/test_sms_debug.php
 */

echo "<h1>🔍 Debug SMS - Chargement de classe</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

echo "<hr>";

// Test 1: Vérifier le chemin vers config.php
echo "<h2>1. Test chemin config.php</h2>";
$config_path = __DIR__ . '/../inc/functions/envoiSMS/config.php';
echo "Chemin testé: <code>$config_path</code><br>";

if (file_exists($config_path)) {
    echo "✅ Fichier config.php trouvé<br>";
    echo "Taille: " . filesize($config_path) . " bytes<br>";
} else {
    echo "❌ Fichier config.php NON TROUVÉ<br>";
}

echo "<hr>";

// Test 2: Inclure config.php et vérifier les variables
echo "<h2>2. Test inclusion config.php</h2>";
try {
    require_once $config_path;
    echo "✅ Config.php inclus avec succès<br>";
    
    echo "<h3>Variables d'environnement:</h3>";
    $vars = ['SMS_PROVIDER', 'HSMS_CLIENT_ID', 'HSMS_CLIENT_SECRET', 'HSMS_TOKEN'];
    foreach ($vars as $var) {
        $value = $_ENV[$var] ?? 'NON DÉFINIE';
        if ($var === 'HSMS_CLIENT_SECRET' || $var === 'HSMS_TOKEN') {
            $value = !empty($_ENV[$var]) ? '***MASQUÉ***' : 'NON DÉFINIE';
        }
        echo "- <strong>$var:</strong> $value<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur inclusion config: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: Vérifier le chemin vers OvlSmsService.php
echo "<h2>3. Test chemin OvlSmsService.php</h2>";
$sms_service_path = __DIR__ . '/../inc/functions/envoiSMS/src/OvlSmsService.php';
echo "Chemin testé: <code>$sms_service_path</code><br>";

if (file_exists($sms_service_path)) {
    echo "✅ Fichier OvlSmsService.php trouvé<br>";
    echo "Taille: " . filesize($sms_service_path) . " bytes<br>";
} else {
    echo "❌ Fichier OvlSmsService.php NON TROUVÉ<br>";
}

echo "<hr>";

// Test 4: Inclure directement OvlSmsService.php
echo "<h2>4. Test inclusion directe OvlSmsService.php</h2>";
try {
    require_once $sms_service_path;
    echo "✅ OvlSmsService.php inclus avec succès<br>";
    
    if (class_exists('\App\OvlSmsService')) {
        echo "✅ Classe \\App\\OvlSmsService disponible<br>";
    } else {
        echo "❌ Classe \\App\\OvlSmsService NON DISPONIBLE<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur inclusion OvlSmsService: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 5: Test de la fonction createSmsService
echo "<h2>5. Test fonction createSmsService()</h2>";
if (function_exists('createSmsService')) {
    echo "✅ Fonction createSmsService disponible<br>";
    
    try {
        $smsService = createSmsService();
        echo "✅ Service SMS créé avec succès<br>";
        echo "Type: " . get_class($smsService) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erreur création service: " . $e->getMessage() . "<br>";
        echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "❌ Fonction createSmsService NON DISPONIBLE<br>";
}

echo "<hr>";

// Test 6: Debug __DIR__ depuis config.php
echo "<h2>6. Debug chemins relatifs</h2>";
echo "Current __DIR__: <code>" . __DIR__ . "</code><br>";
echo "Config __DIR__ serait: <code>" . dirname($config_path) . "</code><br>";
echo "OvlSmsService depuis config: <code>" . dirname($config_path) . "/src/OvlSmsService.php</code><br>";

$config_dir = dirname($config_path);
$ovl_from_config = $config_dir . "/src/OvlSmsService.php";
if (file_exists($ovl_from_config)) {
    echo "✅ Chemin depuis config.php valide<br>";
} else {
    echo "❌ Chemin depuis config.php INVALIDE<br>";
}

echo "<hr>";
echo "<h2>📋 Résumé</h2>";
echo "<p>Ce test identifie exactement où le chargement de classe échoue.</p>";
?>
