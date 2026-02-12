<?php

namespace App\Notifications\Channels;

use App\Models\SmsLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Twilio\Rest\Client as TwilioClient;

class TwilioSmsChannel
{
    /**
     * Envoie la notification via le canal Twilio.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Vérifier si la notification implémente la méthode toTwilio
        if (!method_exists($notification, 'toTwilio')) {
            return;
        }

        $data = $notification->toTwilio($notifiable);
        $message = $data['message'] ?? '';
        $reservationId = $data['reservation_id'] ?? null;

        // Récupérer le numéro de téléphone du destinataire
        $phoneNumber = $this->getPhoneNumber($notifiable);
        
        if (!$phoneNumber) {
            Log::warning('SMS non envoyé : aucun numéro de téléphone trouvé', [
                'user_id' => $notifiable->id ?? null,
                'reservation_id' => $reservationId,
            ]);
            return;
        }

        // Récupérer l'adresse IP pour le rate limiting
        $ipAddress = request()->ip();
        $userId = $notifiable->id ?? null;

        // Vérifier le rate limiting (3 SMS/heure par IP et par utilisateur)
        if (!$this->checkRateLimit($ipAddress, $userId)) {
            $this->logSms($phoneNumber, $message, 'echec', 'Rate limit dépassé', $ipAddress, $userId, $reservationId);
            Log::warning('SMS bloqué : rate limit dépassé', [
                'phone' => $phoneNumber,
                'ip' => $ipAddress,
                'user_id' => $userId,
            ]);
            return;
        }

        // Créer le log SMS en attente
        $smsLog = $this->logSms($phoneNumber, $message, 'en_attente', null, $ipAddress, $userId, $reservationId);

        // Vérifier le driver SMS configuré (depuis Setting en priorité, sinon .env)
        $smsDriver = \App\Models\Setting::get('sms_driver') ?? env('SMS_DRIVER', 'log');

        try {
            if ($smsDriver === 'twilio') {
                // Mode production : envoi réel via Twilio
                $this->sendViaTwilio($phoneNumber, $message, $smsLog);
            } else {
                // Mode développement : écriture dans les logs
                $this->sendViaLog($phoneNumber, $message, $smsLog);
            }
        } catch (\Exception $e) {
            // Mettre à jour le log en cas d'erreur
            $smsLog->update([
                'statut' => 'echec',
                'error_message' => $e->getMessage(),
            ]);
            
            Log::error('Erreur lors de l\'envoi du SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'reservation_id' => $reservationId,
            ]);
            
            throw $e;
        }
    }

    /**
     * Récupère le numéro de téléphone du destinataire.
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        // Si c'est un User, utiliser le champ telephone
        if (method_exists($notifiable, 'getAttribute') && $notifiable->getAttribute('telephone')) {
            return $this->formatPhoneNumber($notifiable->telephone);
        }

        // Si c'est un objet avec une propriété phone_number
        if (isset($notifiable->phone_number)) {
            return $this->formatPhoneNumber($notifiable->phone_number);
        }

        // Si c'est un objet avec une propriété telephone
        if (isset($notifiable->telephone)) {
            return $this->formatPhoneNumber($notifiable->telephone);
        }

        return null;
    }

    /**
     * Formate le numéro de téléphone pour Twilio (format international).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Nettoyer le numéro (supprimer espaces, tirets, etc.)
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si le numéro commence par 0, le remplacer par +33
        if (preg_match('/^0/', $phone)) {
            $phone = '+33' . substr($phone, 1);
        }

        // Si le numéro ne commence pas par +, ajouter +33 par défaut
        if (!preg_match('/^\+/', $phone)) {
            $phone = '+33' . $phone;
        }

        return $phone;
    }

    /**
     * Vérifie le rate limiting (3 SMS/heure par IP et par utilisateur).
     */
    protected function checkRateLimit(string $ipAddress, ?int $userId): bool
    {
        // Rate limit par IP
        $ipKey = 'sms:ip:' . $ipAddress;
        $ipAttempts = RateLimiter::attempts($ipKey);
        
        if ($ipAttempts >= 3) {
            return false;
        }

        // Rate limit par utilisateur (si connecté)
        if ($userId) {
            $userKey = 'sms:user:' . $userId;
            $userAttempts = RateLimiter::attempts($userKey);
            
            if ($userAttempts >= 3) {
                return false;
            }
        }

        // Incrémenter les compteurs
        RateLimiter::hit($ipKey, 3600); // Expire après 1 heure
        
        if ($userId) {
            RateLimiter::hit($userKey, 3600); // Expire après 1 heure
        }

        return true;
    }

    /**
     * Envoie le SMS via Twilio (mode production).
     */
    protected function sendViaTwilio(string $phoneNumber, string $message, SmsLog $smsLog): void
    {
        // TWILIO_USERNAME contient l'Account SID (format: ACxxxxxxxxxxxxxxxxxxxx)
        $accountSid = env('TWILIO_USERNAME') ?: env('TWILIO_ACCOUNT_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $fromNumber = env('TWILIO_FROM');

        if (!$accountSid || !$authToken || !$fromNumber) {
            throw new \Exception('Configuration Twilio incomplète. Vérifiez les variables d\'environnement TWILIO_USERNAME, TWILIO_AUTH_TOKEN et TWILIO_FROM.');
        }

        $client = new TwilioClient($accountSid, $authToken);

        $twilioMessage = $client->messages->create(
            $phoneNumber,
            [
                'from' => $fromNumber,
                'body' => $message,
            ]
        );

        // Mettre à jour le log avec succès
        $smsLog->update([
            'statut' => 'envoye',
            'provider_message_id' => $twilioMessage->sid,
            'sent_at' => now(),
        ]);
    }

    /**
     * Enregistre le SMS dans les logs (mode développement).
     */
    protected function sendViaLog(string $phoneNumber, string $message, SmsLog $smsLog): void
    {
        Log::info('SMS (mode log) - Non envoyé réellement', [
            'destinataire' => $phoneNumber,
            'message' => $message,
            'reservation_id' => $smsLog->reservation_id,
        ]);

        // Mettre à jour le log comme envoyé (même si c'est un faux envoi)
        $smsLog->update([
            'statut' => 'envoye',
            'provider' => 'log',
            'provider_message_id' => 'LOG_' . $smsLog->id,
            'sent_at' => now(),
        ]);
    }

    /**
     * Crée un log SMS dans la base de données.
     */
    protected function logSms(
        string $phoneNumber,
        string $message,
        string $statut,
        ?string $errorMessage,
        ?string $ipAddress,
        ?int $userId,
        ?int $reservationId
    ): SmsLog {
        // Récupérer le provider actuel
        $provider = \App\Models\Setting::get('sms_driver') ?? env('SMS_DRIVER', 'log');
        
        return SmsLog::create([
            'destinataire' => $phoneNumber,
            'message' => $message,
            'statut' => $statut,
            'provider' => $provider,
            'error_message' => $errorMessage,
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'reservation_id' => $reservationId,
            'sent_at' => $statut === 'envoye' ? now() : null,
        ]);
    }
}
