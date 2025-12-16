<?php
/**
 * Test de l'intégration SMS HSMS pour la validation de bordereaux UNIPALM
 */

require_once '../inc/functions/connexion.php';  
require_once '../inc/functions/envoiSMS/vendor/autoload.php';
require_once '../inc/functions/envoiSMS/config.php';

// Inclure la fonction d'envoi SMS pour validation de bordereau
function envoyerSMSValidationBordereau($numero_telephone, $nom_agent, $prenom_agent, $numero_bordereau, $montant_total, $nombre_tickets) {
    try {
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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMS Validation Bordereau UNIPALM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(-45deg, #28a745, #20c997, #17a2b8, #007bff);
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
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 15px;
        }
        .message-preview {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            color: white;
            font-family: monospace;
            white-space: pre-line;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="glass-card p-4">
                    <h2 class="text-white text-center mb-4">
                        <i class="fas fa-check-circle me-2"></i>Test SMS Validation Bordereau UNIPALM
                    </h2>
                    
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $numero = $_POST['numero'] ?? '';
                        $nom = $_POST['nom'] ?? '';
                        $prenom = $_POST['prenom'] ?? '';
                        $numero_bordereau = $_POST['numero_bordereau'] ?? '';
                        $montant_total = floatval($_POST['montant_total'] ?? 0);
                        $nombre_tickets = intval($_POST['nombre_tickets'] ?? 0);
                        
                        if (!empty($numero) && !empty($nom) && !empty($prenom) && !empty($numero_bordereau)) {
                            echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Test d\'envoi SMS validation en cours...</div>';
                            
                            $resultat = envoyerSMSValidationBordereau($numero, $nom, $prenom, $numero_bordereau, $montant_total, $nombre_tickets);
                            
                            if ($resultat['success']) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i><strong>SMS de validation envoyé avec succès !</strong><br>';
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
                            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Veuillez remplir tous les champs obligatoires</div>';
                        }
                    }
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" class="row g-3">
                                <div class="col-12">
                                    <label for="numero" class="form-label text-white">
                                        <i class="fas fa-phone me-1"></i>Numéro de téléphone *
                                    </label>
                                    <input type="text" class="form-control" id="numero" name="numero" 
                                           value="<?= htmlspecialchars($_POST['numero'] ?? '2250769929289') ?>" 
                                           placeholder="2250769929289" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="nom" class="form-label text-white">
                                        <i class="fas fa-user me-1"></i>Nom *
                                    </label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?= htmlspecialchars($_POST['nom'] ?? 'SOULEYMANE') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label text-white">
                                        <i class="fas fa-user me-1"></i>Prénom *
                                    </label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?= htmlspecialchars($_POST['prenom'] ?? 'Ange') ?>" required>
                                </div>
                                
                                <div class="col-12">
                                    <label for="numero_bordereau" class="form-label text-white">
                                        <i class="fas fa-file-invoice me-1"></i>Numéro de bordereau *
                                    </label>
                                    <input type="text" class="form-control" id="numero_bordereau" name="numero_bordereau" 
                                           value="<?= htmlspecialchars($_POST['numero_bordereau'] ?? 'BORD-20251213-266-5729') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="montant_total" class="form-label text-white">
                                        <i class="fas fa-money-bill me-1"></i>Montant total (FCFA)
                                    </label>
                                    <input type="number" class="form-control" id="montant_total" name="montant_total" 
                                           value="<?= htmlspecialchars($_POST['montant_total'] ?? '3080000') ?>" min="0">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="nombre_tickets" class="form-label text-white">
                                        <i class="fas fa-ticket-alt me-1"></i>Nombre de tickets
                                    </label>
                                    <input type="number" class="form-control" id="nombre_tickets" name="nombre_tickets" 
                                           value="<?= htmlspecialchars($_POST['nombre_tickets'] ?? '1') ?>" min="0">
                                </div>
                                
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-check-circle me-2"></i>Envoyer SMS de Validation
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-white mb-3">
                                <i class="fas fa-eye me-2"></i>Aperçu du message
                            </h5>
                            <div class="message-preview" id="messagePreview">
                                UNIPALM - Bordereau Validé

Bonjour Ange SOULEYMANE,

Votre bordereau a été validé avec succès !

📋 Numéro : BORD-20251213-266-5729
🎫 Tickets : 1
💰 Montant : 3 080 000 FCFA

✅ Vous pouvez maintenant vous présenter à la caisse pour le paiement.

Cordialement,
Équipe UNIPALM
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="glass-card p-3">
                            <h5 class="text-white mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informations
                            </h5>
                            <div class="text-white-50 small">
                                <p><strong>Système :</strong> HSMS (hsms.ci)</p>
                                <p><strong>Client ID :</strong> UNIPALM_HOvuHXr</p>
                                <p><strong>Format numéro :</strong> 225XXXXXXXX (Côte d'Ivoire)</p>
                                <p><strong>Message type :</strong> Notification de validation de bordereau</p>
                                <p><strong>Déclenchement :</strong> Automatique lors de la validation d'un bordereau</p>
                                <p><strong>Action suivante :</strong> L'agent peut se présenter à la caisse</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="bordereaux.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-arrow-left me-2"></i>Retour aux Bordereaux
                        </a>
                        <a href="test_sms_bordereau.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-file-invoice me-2"></i>Test SMS Bordereau
                        </a>
                        <a href="test_sms_agent.php" class="btn btn-outline-light">
                            <i class="fas fa-user me-2"></i>Test SMS Agent
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mise à jour dynamique de l'aperçu du message
        function updatePreview() {
            const nom = document.getElementById('nom').value || 'SOULEYMANE';
            const prenom = document.getElementById('prenom').value || 'Ange';
            const numero_bordereau = document.getElementById('numero_bordereau').value || 'BORD-20251213-266-5729';
            const montant_total = parseInt(document.getElementById('montant_total').value) || 3080000;
            const nombre_tickets = parseInt(document.getElementById('nombre_tickets').value) || 1;
            
            const message = `UNIPALM - Bordereau Validé

Bonjour ${prenom.charAt(0).toUpperCase() + prenom.slice(1).toLowerCase()} ${nom.toUpperCase()},

Votre bordereau a été validé avec succès !

📋 Numéro : ${numero_bordereau}
🎫 Tickets : ${nombre_tickets}
💰 Montant : ${montant_total.toLocaleString('fr-FR')} FCFA

✅ Vous pouvez maintenant vous présenter à la caisse pour le paiement.

Cordialement,
Équipe UNIPALM`;
            
            document.getElementById('messagePreview').textContent = message;
        }
        
        // Écouter les changements sur tous les champs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        
        // Mise à jour initiale
        updatePreview();
    </script>
</body>
</html>
