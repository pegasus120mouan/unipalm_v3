<?php
/**
 * Fichier de test pour l'envoi de SMS
 * Permet de tester l'intégration SMS sans créer d'agent
 */

require_once 'agent_sms.php';

echo "<!DOCTYPE html>\n";
echo "<html lang='fr'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Test SMS UNIPALM</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n";
echo "        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }\n";
echo "        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }\n";
echo "        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }\n";
echo "        .form-group { margin: 15px 0; }\n";
echo "        label { display: block; margin-bottom: 5px; font-weight: bold; }\n";
echo "        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }\n";
echo "        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }\n";
echo "        button:hover { background: #0056b3; }\n";
echo "        .log-section { margin-top: 30px; }\n";
echo "        .log-content { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <div class='container'>\n";
echo "        <h1>🔧 Test SMS UNIPALM</h1>\n";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_sms') {
        $numero = $_POST['numero'] ?? '';
        $nom = $_POST['nom'] ?? 'TEST';
        $prenom = $_POST['prenom'] ?? 'Agent';
        $code_pin = $_POST['code_pin'] ?? '123456';
        $numero_agent = $_POST['numero_agent'] ?? 'AGT-25-TST-TA01';
        
        echo "<div class='info'><strong>🚀 Test d'envoi SMS en cours...</strong></div>\n";
        
        $resultat = envoyerSMSNouvelAgent($numero, $nom, $prenom, $code_pin, $numero_agent);
        
        if ($resultat['success']) {
            echo "<div class='success'>\n";
            echo "<strong>✅ SMS envoyé avec succès !</strong><br>\n";
            echo "Numéro : " . htmlspecialchars($numero) . "<br>\n";
            echo "Message : " . $resultat['message'] . "<br>\n";
            if (isset($resultat['details'])) {
                echo "Détails : " . json_encode($resultat['details'], JSON_PRETTY_PRINT) . "\n";
            }
            echo "</div>\n";
        } else {
            echo "<div class='error'>\n";
            echo "<strong>❌ Échec de l'envoi SMS</strong><br>\n";
            echo "Erreur : " . htmlspecialchars($resultat['message']) . "<br>\n";
            if (isset($resultat['error'])) {
                echo "Détails : " . htmlspecialchars($resultat['error']) . "\n";
            }
            echo "</div>\n";
        }
    }
}

echo "        <form method='POST'>\n";
echo "            <input type='hidden' name='action' value='test_sms'>\n";
echo "            \n";
echo "            <div class='form-group'>\n";
echo "                <label for='numero'>Numéro de téléphone :</label>\n";
echo "                <input type='text' id='numero' name='numero' value='" . ($_POST['numero'] ?? '+22507000000') . "' placeholder='+22507000000' required>\n";
echo "                <small>Format : +225XXXXXXXX</small>\n";
echo "            </div>\n";
echo "            \n";
echo "            <div class='form-group'>\n";
echo "                <label for='nom'>Nom de l'agent :</label>\n";
echo "                <input type='text' id='nom' name='nom' value='" . ($_POST['nom'] ?? 'KOUAME') . "' required>\n";
echo "            </div>\n";
echo "            \n";
echo "            <div class='form-group'>\n";
echo "                <label for='prenom'>Prénom de l'agent :</label>\n";
echo "                <input type='text' id='prenom' name='prenom' value='" . ($_POST['prenom'] ?? 'Jean') . "' required>\n";
echo "            </div>\n";
echo "            \n";
echo "            <div class='form-group'>\n";
echo "                <label for='code_pin'>Code PIN :</label>\n";
echo "                <input type='text' id='code_pin' name='code_pin' value='" . ($_POST['code_pin'] ?? '123456') . "' pattern='[0-9]{6}' required>\n";
echo "            </div>\n";
echo "            \n";
echo "            <div class='form-group'>\n";
echo "                <label for='numero_agent'>Numéro d'agent :</label>\n";
echo "                <input type='text' id='numero_agent' name='numero_agent' value='" . ($_POST['numero_agent'] ?? 'AGT-25-TST-JK01') . "' required>\n";
echo "            </div>\n";
echo "            \n";
echo "            <button type='submit'>📱 Envoyer SMS de Test</button>\n";
echo "        </form>\n";

// Afficher les logs
echo "        <div class='log-section'>\n";
echo "            <h3>📋 Logs SMS</h3>\n";

$log_file = __DIR__ . '/logs/sms_log.txt';
$error_log_file = __DIR__ . '/logs/sms_errors.txt';

if (file_exists($log_file)) {
    echo "            <h4>✅ Logs de succès :</h4>\n";
    $logs = file_get_contents($log_file);
    echo "            <div class='log-content'>" . htmlspecialchars($logs) . "</div>\n";
} else {
    echo "            <div class='info'>Aucun log de succès trouvé.</div>\n";
}

if (file_exists($error_log_file)) {
    echo "            <h4>❌ Logs d'erreurs :</h4>\n";
    $error_logs = file_get_contents($error_log_file);
    echo "            <div class='log-content'>" . htmlspecialchars($error_logs) . "</div>\n";
} else {
    echo "            <div class='info'>Aucun log d'erreur trouvé.</div>\n";
}

echo "        </div>\n";

// Informations de configuration
echo "        <div class='log-section'>\n";
echo "            <h3>⚙️ Configuration SMS</h3>\n";
echo "            <div class='log-content'>\n";
echo "CLIENT_ID: " . SMS_CLIENT_ID . "\n";
echo "TOKEN: " . substr(SMS_TOKEN, 0, 10) . "...\n";
echo "SENDER: " . SMS_SENDER_NAME . "\n";
echo "            </div>\n";
echo "        </div>\n";

echo "        <div class='info'>\n";
echo "            <strong>ℹ️ Note :</strong> Actuellement en mode simulation. Pour activer l'envoi réel, modifiez la méthode <code>simulerEnvoiSMS()</code> dans <code>sms_sender.php</code> pour utiliser <code>appelAPI()</code> avec l'URL correcte de l'API SMS.\n";
echo "        </div>\n";

echo "    </div>\n";
echo "</body>\n";
echo "</html>\n";
?>
