<?php
/**
 * Test de l'intégration SMS HSMS pour les paiements de bordereaux UNIPALM
 */

require_once '../inc/functions/connexion.php';
if (file_exists('../inc/functions/envoiSMS/vendor/autoload.php')) {
    require_once '../inc/functions/envoiSMS/vendor/autoload.php';
}
require_once '../inc/functions/envoiSMS/config.php';

// Inclure la fonction d'envoi SMS pour paiement de bordereau
function envoyerSMSPaiementBordereau($numero_telephone, $nom_agent, $prenom_agent, $numero_bordereau, $montant_total, $montant_paye, $montant_reste) {
    try {
        // Créer le service SMS HSMS avec vos identifiants
        $smsService = new \App\OvlSmsService(
            'UNIPALM_HOvuHXr',
            'UNIPALM20251129194026.813697uv2rU5edhLWCv5HDLqoA',
            '0eebac3b6594eb3c37b675f8ab0299629f5d96f9'
        );
        
        // Créer le message de notification de paiement
        $message = "UNIPALM - Paiement Reçu\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom_agent)) . " " . strtoupper($nom_agent) . ",\n\n";
        $message .= "Un paiement a été effectué sur votre bordereau :\n\n";
        $message .= "📋 Numéro : " . $numero_bordereau . "\n";
        $message .= "💰 Montant total : " . number_format($montant_total, 0, ',', ' ') . " FCFA\n";
        $message .= "✅ Montant payé : " . number_format($montant_paye, 0, ',', ' ') . " FCFA\n";
        $message .= "⏳ Reste à payer : " . number_format($montant_reste, 0, ',', ' ') . " FCFA\n\n";
        
        if ($montant_reste <= 0) {
            $message .= "🎉 Félicitations ! Votre bordereau est maintenant entièrement soldé.\n\n";
        } else {
            $message .= "ℹ️ Paiement partiel effectué. Solde restant à régler.\n\n";
        }
        
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
    <title>Test SMS Paiement Bordereau UNIPALM</title>
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
            max-height: 400px;
            overflow-y: auto;
        }
        .payment-status {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .payment-status.partial {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
        }
        .payment-status.complete {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="glass-card p-4">
                    <h2 class="text-white text-center mb-4">
                        <i class="fas fa-money-bill-wave me-2"></i>Test SMS Paiement Bordereau UNIPALM
                    </h2>
                    
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $numero = $_POST['numero'] ?? '';
                        $nom = $_POST['nom'] ?? '';
                        $prenom = $_POST['prenom'] ?? '';
                        $numero_bordereau = $_POST['numero_bordereau'] ?? '';
                        $montant_total = floatval($_POST['montant_total'] ?? 0);
                        $montant_paye = floatval($_POST['montant_paye'] ?? 0);
                        $montant_reste = floatval($_POST['montant_reste'] ?? 0);
                        
                        if (!empty($numero) && !empty($nom) && !empty($prenom) && !empty($numero_bordereau)) {
                            echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Test d\'envoi SMS paiement en cours...</div>';
                            
                            $resultat = envoyerSMSPaiementBordereau($numero, $nom, $prenom, $numero_bordereau, $montant_total, $montant_paye, $montant_reste);
                            
                            if ($resultat['success']) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="fas fa-check-circle me-2"></i><strong>SMS de paiement envoyé avec succès !</strong><br>';
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
                                
                                <div class="col-md-4">
                                    <label for="montant_total" class="form-label text-white">
                                        <i class="fas fa-money-bill me-1"></i>Montant total (FCFA)
                                    </label>
                                    <input type="number" class="form-control" id="montant_total" name="montant_total" 
                                           value="<?= htmlspecialchars($_POST['montant_total'] ?? '3080000') ?>" min="0">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="montant_paye" class="form-label text-white">
                                        <i class="fas fa-check-circle me-1"></i>Montant payé (FCFA)
                                    </label>
                                    <input type="number" class="form-control" id="montant_paye" name="montant_paye" 
                                           value="<?= htmlspecialchars($_POST['montant_paye'] ?? '1500000') ?>" min="0">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="montant_reste" class="form-label text-white">
                                        <i class="fas fa-hourglass-half me-1"></i>Reste à payer (FCFA)
                                    </label>
                                    <input type="number" class="form-control" id="montant_reste" name="montant_reste" 
                                           value="<?= htmlspecialchars($_POST['montant_reste'] ?? '1580000') ?>" min="0">
                                </div>
                                
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-money-bill-wave me-2"></i>Envoyer SMS de Paiement
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-white mb-3">
                                <i class="fas fa-eye me-2"></i>Aperçu du message
                            </h5>
                            
                            <div id="paymentStatus" class="payment-status partial">
                                <div class="text-white">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Statut :</strong> <span id="statusText">Paiement partiel</span>
                                </div>
                            </div>
                            
                            <div class="message-preview" id="messagePreview">
                                UNIPALM - Paiement Reçu

Bonjour Ange SOULEYMANE,

Un paiement a été effectué sur votre bordereau :

📋 Numéro : BORD-20251213-266-5729
💰 Montant total : 3 080 000 FCFA
✅ Montant payé : 1 500 000 FCFA
⏳ Reste à payer : 1 580 000 FCFA

ℹ️ Paiement partiel effectué. Solde restant à régler.

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
                                <p><strong>Message type :</strong> Notification de paiement de bordereau</p>
                                <p><strong>Déclenchement :</strong> Automatique lors d'un paiement sur un bordereau</p>
                                <p><strong>Contenu :</strong> Montant total, montant payé, reste à payer</p>
                                <p><strong>Statut :</strong> Différenciation paiement partiel / complet</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="compte_agent_detail.php?id=266" class="btn btn-outline-light me-2">
                            <i class="fas fa-arrow-left me-2"></i>Retour au Compte Agent
                        </a>
                        <a href="test_sms_validation_bordereau.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-check-circle me-2"></i>Test SMS Validation
                        </a>
                        <a href="test_sms_bordereau.php" class="btn btn-outline-light">
                            <i class="fas fa-file-invoice me-2"></i>Test SMS Bordereau
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
            const montant_paye = parseInt(document.getElementById('montant_paye').value) || 1500000;
            const montant_reste = parseInt(document.getElementById('montant_reste').value) || 1580000;
            
            // Mise à jour du statut
            const paymentStatus = document.getElementById('paymentStatus');
            const statusText = document.getElementById('statusText');
            
            if (montant_reste <= 0) {
                paymentStatus.className = 'payment-status complete';
                statusText.textContent = 'Bordereau entièrement soldé';
            } else {
                paymentStatus.className = 'payment-status partial';
                statusText.textContent = 'Paiement partiel';
            }
            
            // Message SMS
            let message = `UNIPALM - Paiement Reçu

Bonjour ${prenom.charAt(0).toUpperCase() + prenom.slice(1).toLowerCase()} ${nom.toUpperCase()},

Un paiement a été effectué sur votre bordereau :

📋 Numéro : ${numero_bordereau}
💰 Montant total : ${montant_total.toLocaleString('fr-FR')} FCFA
✅ Montant payé : ${montant_paye.toLocaleString('fr-FR')} FCFA
⏳ Reste à payer : ${montant_reste.toLocaleString('fr-FR')} FCFA

`;
            
            if (montant_reste <= 0) {
                message += "🎉 Félicitations ! Votre bordereau est maintenant entièrement soldé.\n\n";
            } else {
                message += "ℹ️ Paiement partiel effectué. Solde restant à régler.\n\n";
            }
            
            message += "Cordialement,\nÉquipe UNIPALM";
            
            document.getElementById('messagePreview').textContent = message;
        }
        
        // Calcul automatique du reste à payer
        function updateReste() {
            const montant_total = parseInt(document.getElementById('montant_total').value) || 0;
            const montant_paye = parseInt(document.getElementById('montant_paye').value) || 0;
            const montant_reste = Math.max(0, montant_total - montant_paye);
            
            document.getElementById('montant_reste').value = montant_reste;
            updatePreview();
        }
        
        // Écouter les changements sur tous les champs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.id === 'montant_total' || this.id === 'montant_paye') {
                    updateReste();
                } else {
                    updatePreview();
                }
            });
        });
        
        // Mise à jour initiale
        updatePreview();
    </script>
</body>
</html>
