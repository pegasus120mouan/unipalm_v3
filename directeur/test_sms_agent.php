<?php
/**
 * Test de l'intégration SMS HSMS pour les agents UNIPALM
 */

require_once '../inc/functions/connexion.php';
require_once '../inc/functions/envoiSMS/vendor/autoload.php';
require_once '../inc/functions/envoiSMS/config.php';

// Inclure la fonction d'envoi SMS
function envoyerSMSNouvelAgent($numero_telephone, $nom_agent, $prenom_agent, $code_pin, $numero_agent) {
    try {
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        // Créer le message de bienvenue
        $message = "Bienvenue chez UNIPALM !\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Votre compte agent a été créé avec succès.\n\n";
        $message .= "Votre numéro d'agent : " . $numero_agent . "\n";
        $message .= "Votre code PIN : " . $code_pin . "\n\n";
        $message .= "Gardez ces informations confidentielles.\n\n";
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMS Agent UNIPALM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="glass-card p-4">
                    <h2 class="text-white text-center mb-4">
                        <i class="fas fa-sms me-2"></i>Test SMS Agent UNIPALM
                    </h2>
                    
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $numero = $_POST['numero'] ?? '';
                        $nom = $_POST['nom'] ?? '';
                        $prenom = $_POST['prenom'] ?? '';
                        $code_pin = $_POST['code_pin'] ?? '';
                        $numero_agent = $_POST['numero_agent'] ?? '';
                        
                        if (!empty($numero) && !empty($nom) && !empty($prenom) && !empty($code_pin) && !empty($numero_agent)) {
                            echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Test d\'envoi SMS en cours...</div>';
                            
                            $resultat = envoyerSMSNouvelAgent($numero, $nom, $prenom, $code_pin, $numero_agent);
                            
                            if ($resultat['success']) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i><strong>SMS envoyé avec succès !</strong><br>';
                                echo 'Destinataire : ' . htmlspecialchars($numero) . '<br>';
                                echo 'ID Message : ' . ($resultat['message_sid'] ?? 'N/A') . '<br>';
                                echo 'Statut : ' . ($resultat['status'] ?? 'N/A');
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="fas fa-exclamation-circle me-2"></i><strong>Échec de l\'envoi SMS</strong><br>';
                                echo 'Erreur : ' . htmlspecialchars($resultat['error'] ?? 'Erreur inconnue');
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Veuillez remplir tous les champs</div>';
                        }
                    }
                    ?>
                    
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="numero" class="form-label text-white">
                                <i class="fas fa-phone me-1"></i>Numéro de téléphone
                            </label>
                            <input type="text" class="form-control" id="numero" name="numero" 
                                   value="<?= htmlspecialchars($_POST['numero'] ?? '2250101010101') ?>" 
                                   placeholder="2250101010101" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="nom" class="form-label text-white">
                                <i class="fas fa-user me-1"></i>Nom
                            </label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?= htmlspecialchars($_POST['nom'] ?? 'KOUAME') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="prenom" class="form-label text-white">
                                <i class="fas fa-user me-1"></i>Prénom
                            </label>
                            <input type="text" class="form-control" id="prenom" name="prenom" 
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? 'Jean') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="code_pin" class="form-label text-white">
                                <i class="fas fa-lock me-1"></i>Code PIN
                            </label>
                            <input type="text" class="form-control" id="code_pin" name="code_pin" 
                                   value="<?= htmlspecialchars($_POST['code_pin'] ?? '123456') ?>" 
                                   pattern="[0-9]{6}" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="numero_agent" class="form-label text-white">
                                <i class="fas fa-id-card me-1"></i>Numéro d'agent
                            </label>
                            <input type="text" class="form-control" id="numero_agent" name="numero_agent" 
                                   value="<?= htmlspecialchars($_POST['numero_agent'] ?? 'AGT-25-KOU-JK01') ?>" required>
                        </div>
                        
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer SMS de Test
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <div class="glass-card p-3">
                            <h5 class="text-white mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informations
                            </h5>
                            <div class="text-white-50 small">
                                <p><strong>Système :</strong> HSMS (hsms.ci)</p>
                                <p><strong>Client ID :</strong> UNIPALM_HOvuHXr</p>
                                <p><strong>Format numéro :</strong> 225XXXXXXXX (Côte d'Ivoire)</p>
                                <p><strong>Message type :</strong> Bienvenue nouvel agent avec PIN</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="agents.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux Agents
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
