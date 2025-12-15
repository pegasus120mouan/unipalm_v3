<?php
require_once 'config.php';

/**
 * Classe pour l'envoi de SMS via l'API UNIPALM
 */
class SMSSender {
    
    private $client_id;
    private $client_secret;
    private $token;
    private $api_base_url;
    
    public function __construct() {
        $this->client_id = SMS_CLIENT_ID;
        $this->client_secret = SMS_CLIENT_SECRET;
        $this->token = SMS_TOKEN;
        $this->api_base_url = SMS_API_BASE_URL;
    }
    
    /**
     * Envoie un SMS de code PIN à un agent
     * @param string $numero_telephone Numéro de téléphone du destinataire
     * @param string $nom_agent Nom de l'agent
     * @param string $prenom_agent Prénom de l'agent
     * @param string $code_pin Code PIN à envoyer
     * @param string $numero_agent Numéro d'agent généré
     * @return array Résultat de l'envoi
     */
    public function envoyerCodePinAgent($numero_telephone, $nom_agent, $prenom_agent, $code_pin, $numero_agent) {
        // Nettoyer le numéro de téléphone
        $numero_telephone = $this->nettoyerNumeroTelephone($numero_telephone);
        
        // Créer le message
        $message = $this->creerMessageCodePin($nom_agent, $prenom_agent, $code_pin, $numero_agent);
        
        // Envoyer le SMS
        return $this->envoyerSMS($numero_telephone, $message);
    }
    
    /**
     * Nettoie et formate le numéro de téléphone
     * @param string $numero Numéro brut
     * @return string Numéro formaté
     */
    private function nettoyerNumeroTelephone($numero) {
        // Supprimer tous les caractères non numériques sauf le +
        $numero = preg_replace('/[^\d+]/', '', $numero);
        
        // Si le numéro commence par 0, le remplacer par +225 (Côte d'Ivoire)
        if (substr($numero, 0, 1) === '0') {
            $numero = '+225' . substr($numero, 1);
        }
        
        // Si le numéro ne commence pas par +, ajouter +225
        if (substr($numero, 0, 1) !== '+') {
            $numero = '+225' . $numero;
        }
        
        return $numero;
    }
    
    /**
     * Crée le message de code PIN
     * @param string $nom Nom de l'agent
     * @param string $prenom Prénom de l'agent
     * @param string $code_pin Code PIN
     * @param string $numero_agent Numéro d'agent
     * @return string Message formaté
     */
    private function creerMessageCodePin($nom, $prenom, $code_pin, $numero_agent) {
        $message = "Bienvenue chez UNIPALM !\n\n";
        $message .= "Bonjour " . ucfirst(strtolower($prenom)) . " " . strtoupper($nom) . ",\n\n";
        $message .= "Votre compte agent a été créé avec succès.\n\n";
        $message .= "Votre numéro d'agent : " . $numero_agent . "\n";
        $message .= "Votre code PIN : " . $code_pin . "\n\n";
        $message .= "Gardez ces informations confidentielles.\n\n";
        $message .= "Cordialement,\nÉquipe UNIPALM";
        
        return $message;
    }
    
    /**
     * Envoie un SMS via l'API
     * @param string $numero_telephone Numéro de destination
     * @param string $message Contenu du message
     * @return array Résultat de l'envoi
     */
    private function envoyerSMS($numero_telephone, $message) {
        $tentatives = 0;
        $max_tentatives = SMS_MAX_RETRIES;
        
        while ($tentatives < $max_tentatives) {
            $tentatives++;
            
            try {
                // Préparer les données pour l'API
                $data = [
                    'client_id' => $this->client_id,
                    'token' => $this->token,
                    'sender' => SMS_SENDER_NAME,
                    'recipient' => $numero_telephone,
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                // Pour le moment, simuler l'envoi car nous n'avons pas l'URL exacte de l'API
                // En production, remplacer par un vrai appel API
                $resultat = $this->simulerEnvoiSMS($data);
                
                // Logger le succès
                $this->loggerSMS($numero_telephone, $message, 'SUCCESS', $resultat);
                
                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'tentative' => $tentatives,
                    'details' => $resultat
                ];
                
            } catch (Exception $e) {
                $error_message = "Tentative $tentatives/$max_tentatives échouée: " . $e->getMessage();
                
                // Logger l'erreur
                $this->loggerErreur($numero_telephone, $message, $error_message);
                
                if ($tentatives >= $max_tentatives) {
                    return [
                        'success' => false,
                        'message' => 'Échec de l\'envoi SMS après ' . $max_tentatives . ' tentatives',
                        'error' => $e->getMessage(),
                        'tentatives' => $tentatives
                    ];
                }
                
                // Attendre avant la prochaine tentative
                sleep(2);
            }
        }
        
        return [
            'success' => false,
            'message' => 'Échec de l\'envoi SMS',
            'tentatives' => $tentatives
        ];
    }
    
    /**
     * Simule l'envoi SMS (à remplacer par un vrai appel API)
     * @param array $data Données du SMS
     * @return array Résultat simulé
     */
    private function simulerEnvoiSMS($data) {
        // Simulation d'un délai d'API
        usleep(500000); // 0.5 seconde
        
        // Simuler un succès (en production, faire le vrai appel API ici)
        return [
            'status' => 'sent',
            'message_id' => 'SMS_' . time() . '_' . rand(1000, 9999),
            'recipient' => $data['recipient'],
            'timestamp' => $data['timestamp'],
            'cost' => 0.05 // Coût simulé
        ];
    }
    
    /**
     * Fait un vrai appel API (à utiliser quand l'URL exacte sera fournie)
     * @param array $data Données du SMS
     * @return array Résultat de l'API
     */
    private function appelAPI($data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_base_url . '/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
                'X-Client-ID: ' . $this->client_id
            ],
            CURLOPT_TIMEOUT => SMS_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception("Erreur cURL: " . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new Exception("Erreur HTTP: " . $http_code . " - " . $response);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erreur JSON: " . json_last_error_msg());
        }
        
        return $result;
    }
    
    /**
     * Logger les SMS envoyés
     * @param string $numero Numéro de destination
     * @param string $message Contenu du message
     * @param string $status Statut de l'envoi
     * @param array $details Détails de l'envoi
     */
    private function loggerSMS($numero, $message, $status, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'numero' => $numero,
            'status' => $status,
            'message_length' => strlen($message),
            'details' => $details
        ];
        
        $log_line = json_encode($log_entry) . "\n";
        file_put_contents(SMS_LOG_FILE, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Logger les erreurs SMS
     * @param string $numero Numéro de destination
     * @param string $message Contenu du message
     * @param string $error Message d'erreur
     */
    private function loggerErreur($numero, $message, $error) {
        $error_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'numero' => $numero,
            'error' => $error,
            'message_length' => strlen($message)
        ];
        
        $error_line = json_encode($error_entry) . "\n";
        file_put_contents(SMS_ERROR_LOG_FILE, $error_line, FILE_APPEND | LOCK_EX);
    }
}
?>
