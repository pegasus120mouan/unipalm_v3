<?php
/**
 * Test SMS simple pour vérifier la réception
 * URL: http://alerte.unipalm-ci.site/pages/test_sms_reception.php
 */

echo "<h1>📱 Test Réception SMS - UNIPALM</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Serveur:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";

// Formulaire pour saisir le numéro
if (!isset($_POST['send_sms'])) {
?>
<form method="POST" style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h2>🎯 Envoyer un SMS de test</h2>
    <p>
        <label for="phone"><strong>Numéro de téléphone:</strong></label><br>
        <input type="text" id="phone" name="phone" value="0769292989" 
               style="padding: 8px; width: 200px; margin: 5px 0;" 
               placeholder="Ex: 0769292989">
    </p>
    <p>
        <label for="message"><strong>Message personnalisé (optionnel):</strong></label><br>
        <textarea id="message" name="message" rows="3" cols="50" 
                  style="padding: 8px; margin: 5px 0;"
                  placeholder="Laissez vide pour un message automatique">Test SMS UNIPALM Production - <?php echo date('H:i:s'); ?></textarea>
    </p>
    <p>
        <button type="submit" name="send_sms" value="1" 
                style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            📤 Envoyer SMS de Test
        </button>
    </p>
</form>

<div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
    <h3>ℹ️ Instructions:</h3>
    <ol>
        <li>Saisissez votre numéro de téléphone</li>
        <li>Cliquez sur "Envoyer SMS de Test"</li>
        <li>Vérifiez la réception sur votre téléphone</li>
        <li>Le résultat s'affichera ci-dessous</li>
    </ol>
</div>

<?php
} else {
    // Traitement de l'envoi SMS
    $phone = trim($_POST['phone']);
    $custom_message = trim($_POST['message']);
    
    echo "<hr>";
    echo "<h2>📤 Envoi SMS en cours...</h2>";
    
    if (empty($phone)) {
        echo "<p style='color: red;'>❌ Veuillez saisir un numéro de téléphone</p>";
    } else {
        echo "<p><strong>Numéro:</strong> $phone</p>";
        
        // Message par défaut ou personnalisé
        if (empty($custom_message)) {
            $message = "Test SMS UNIPALM Production - " . date('Y-m-d H:i:s') . " - Réception OK ?";
        } else {
            $message = $custom_message;
        }
        
        echo "<p><strong>Message:</strong> $message</p>";
        
        try {
            // Charger la configuration SMS
            require_once '../inc/functions/envoiSMS/config.php';
            
            // Créer le service SMS
            $smsService = createSmsService();
            echo "<p>✅ Service SMS créé: " . get_class($smsService) . "</p>";
            
            // Envoyer le SMS
            $result = $smsService->sendSms($phone, $message);
            
            echo "<h3>📋 Résultat de l'envoi:</h3>";
            
            if ($result['success']) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
                echo "<h4>✅ SMS ENVOYÉ AVEC SUCCÈS !</h4>";
                echo "<p><strong>ID Message:</strong> " . ($result['message_id'] ?? 'N/A') . "</p>";
                echo "<p><strong>Statut:</strong> Envoyé</p>";
                echo "<p><strong>Vérifiez votre téléphone maintenant !</strong></p>";
                echo "</div>";
                
                echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
                echo "<h4>⏰ Délai de réception:</h4>";
                echo "<ul>";
                echo "<li>SMS local: 1-5 secondes</li>";
                echo "<li>SMS international: 10-60 secondes</li>";
                echo "<li>Si pas reçu après 2 minutes, vérifiez le numéro</li>";
                echo "</ul>";
                echo "</div>";
                
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
                echo "<h4>❌ ÉCHEC DE L'ENVOI</h4>";
                echo "<p><strong>Erreur:</strong> " . ($result['error'] ?? 'Erreur inconnue') . "</p>";
                echo "</div>";
            }
            
            // Afficher les détails techniques
            echo "<details style='margin: 20px 0;'>";
            echo "<summary style='cursor: pointer; font-weight: bold;'>🔧 Détails techniques (cliquez pour voir)</summary>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
            echo json_encode($result, JSON_PRETTY_PRINT);
            echo "</pre>";
            echo "</details>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h4>❌ ERREUR SYSTÈME</h4>";
            echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>Fichier:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='test_sms_reception.php' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔄 Nouveau Test</a></p>";
}
?>

<hr>
<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
    <h3>📊 Informations système:</h3>
    <p><strong>Serveur:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
    <p><strong>Script:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
    <p><strong>IP Serveur:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></p>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
</div>
