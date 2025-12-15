<?php
require_once 'sms_sender.php';

/**
 * Fonction utilitaire pour envoyer un SMS de code PIN à un nouvel agent
 * @param string $numero_telephone Numéro de téléphone de l'agent
 * @param string $nom_agent Nom de l'agent
 * @param string $prenom_agent Prénom de l'agent
 * @param string $code_pin Code PIN généré
 * @param string $numero_agent Numéro d'agent généré
 * @return array Résultat de l'envoi
 */
function envoyerSMSNouvelAgent($numero_telephone, $nom_agent, $prenom_agent, $code_pin, $numero_agent) {
    try {
        $sms_sender = new SMSSender();
        $resultat = $sms_sender->envoyerCodePinAgent(
            $numero_telephone,
            $nom_agent,
            $prenom_agent,
            $code_pin,
            $numero_agent
        );
        
        return $resultat;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Fonction pour tester l'envoi d'un SMS
 * @param string $numero_test Numéro de test
 * @return array Résultat du test
 */
function testerEnvoiSMS($numero_test = '+22507000000') {
    return envoyerSMSNouvelAgent(
        $numero_test,
        'TEST',
        'Agent',
        '123456',
        'AGT-25-TST-TA01'
    );
}
?>
