<?php

namespace App;

class OvlSmsService
{
    private $clientId;
    private $clientSecret;
    private $token;
    private $apiUrl;

    public function __construct($clientId, $clientSecret, $token)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->apiUrl = 'https://hsms.ci'; // URL officielle HSMS
    }

    /**
     * Envoie un SMS via l'API HSMS
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Message à envoyer
     * @return array Résultat de l'envoi
     */
    public function sendSms($to, $message)
    {
        try {
            // Validation du numéro de téléphone
            if (!$this->isValidPhoneNumber($to)) {
                return [
                    'success' => false,
                    'error' => 'Numéro de téléphone invalide'
                ];
            }

            // Validation du message
            if (empty(trim($message))) {
                return [
                    'success' => false,
                    'error' => 'Le message ne peut pas être vide'
                ];
            }

            if (strlen($message) > 1600) {
                return [
                    'success' => false,
                    'error' => 'Le message est trop long (maximum 1600 caractères)'
                ];
            }

            // Formatage du numéro selon les exigences HSMS (225xxxxxxxx)
            $formattedPhone = $this->formatPhoneNumber($to);

            // Préparation des données selon la documentation HSMS
            $data = [
                'clientid' => $this->clientId,
                'clientsecret' => $this->clientSecret,
                'telephone' => $formattedPhone,
                'message' => $message
            ];

            // Headers pour l'authentification HSMS
            $headers = [
                'Authorization: Bearer ' . $this->token
                // Content-Type sera automatiquement défini par cURL pour multipart/form-data
            ];

            // Debug - Log des données envoyées (à supprimer en production)
            if ($_ENV['APP_DEBUG'] === 'true') {
                error_log("HSMS Debug - URL: " . $this->apiUrl . '/api/envoi-sms/');
                error_log("HSMS Debug - Data: " . print_r($data, true));
                error_log("HSMS Debug - Headers: " . print_r($headers, true));
            }

            // Envoi de la requête
            $response = $this->makeApiRequest('/api/envoi-sms/', $data, $headers);

            if ($response['success']) {
                $responseData = $response['data'];
                
                if (isset($responseData['success']) && $responseData['success'] === true) {
                    $ticket = '';
                    if (isset($responseData['resultats']) && is_array($responseData['resultats']) && count($responseData['resultats']) > 0) {
                        $ticket = $responseData['resultats'][0]['ticket'] ?? '';
                    }
                    
                    return [
                        'success' => true,
                        'message_sid' => $ticket,
                        'status' => 'sent',
                        'to' => $to,
                        'from' => 'HSMS',
                        'body' => $message,
                        'date_sent' => date('Y-m-d H:i:s'),
                        'provider' => 'HSMS'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Erreur API HSMS: ' . ($responseData['message'] ?? 'Erreur inconnue')
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Erreur API HSMS: ' . ($response['error'] ?? 'Erreur inconnue')
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Effectue une requête vers l'API HSMS
     *
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     */
    private function makeApiRequest($endpoint, $data, $headers)
    {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data, // Envoyer directement le tableau pour multipart/form-data
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'Erreur cURL: ' . $error
            ];
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decodedResponse
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Code HTTP: ' . $httpCode . ' - ' . ($decodedResponse['message'] ?? $response)
            ];
        }
    }

    /**
     * Valide un numéro de téléphone (format Côte d'Ivoire)
     *
     * @param string $phoneNumber
     * @return bool
     */
    private function isValidPhoneNumber($phoneNumber)
    {
        // Supprime tous les caractères non numériques
        $cleaned = preg_replace('/[^\d]/', '', $phoneNumber);
        
        // Vérifie que le numéro commence par 225 ou contient au moins 10 chiffres
        return preg_match('/^(225)?\d{8,10}$/', $cleaned) || preg_match('/^\d{10,15}$/', $cleaned);
    }

    /**
     * Formate un numéro de téléphone pour HSMS (format 225xxxxxxxx)
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneNumber($phoneNumber)
    {
        // Supprime tous les caractères non numériques (y compris le +)
        $cleaned = preg_replace('/[^\d]/', '', $phoneNumber);
        
        // Si le numéro ne commence pas par 225, on l'ajoute
        if (!str_starts_with($cleaned, '225')) {
            $cleaned = '225' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Vérifie le solde SMS disponible
     *
     * @return array
     */
    public function checkSmsBalance()
    {
        try {
            $data = [
                'clientid' => $this->clientId,
                'clientsecret' => $this->clientSecret
            ];

            $headers = [
                'Authorization: Bearer ' . $this->token
                // Content-Type sera automatiquement défini par cURL pour multipart/form-data
            ];

            $response = $this->makeApiRequest('/api/check-sms', $data, $headers);

            if ($response['success']) {
                $responseData = $response['data'];
                return [
                    'success' => true,
                    'balance' => $responseData['SMS disponibles'] ?? 0,
                    'application' => $responseData['Application'] ?? 'N/A'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error']
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
}
