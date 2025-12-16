<?php

namespace App;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class SmsService
{
    private $client;
    private $fromNumber;

    public function __construct($accountSid, $authToken, $fromNumber)
    {
        $this->client = new Client($accountSid, $authToken);
        $this->fromNumber = $fromNumber;
    }

    /**
     * Envoie un SMS
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

            // Envoi du SMS via Twilio
            $twilioMessage = $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            return [
                'success' => true,
                'message_sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
                'to' => $to,
                'from' => $this->fromNumber,
                'body' => $message,
                'date_sent' => date('Y-m-d H:i:s')
            ];

        } catch (TwilioException $e) {
            return [
                'success' => false,
                'error' => 'Erreur Twilio: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valide un numéro de téléphone
     *
     * @param string $phoneNumber
     * @return bool
     */
    private function isValidPhoneNumber($phoneNumber)
    {
        // Supprime tous les caractères non numériques sauf le +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Vérifie que le numéro commence par + et contient au moins 10 chiffres
        return preg_match('/^\+\d{10,15}$/', $cleaned);
    }

    /**
     * Formate un numéro de téléphone
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneNumber($phoneNumber)
    {
        // Supprime tous les caractères non numériques sauf le +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Ajoute le + si il n'y en a pas
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }
}
