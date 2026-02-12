<?php

namespace App\Listeners;

use App\Services\EmailLogger;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogEmailSent
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $recipient = null;
            $subject = $message->getSubject() ?? 'Sans sujet';
            
            // Récupérer le destinataire principal
            $to = $message->getTo();
            if ($to && count($to) > 0) {
                $recipient = array_key_first($to);
            }

            if (!$recipient) {
                return; // Pas de destinataire, on skip
            }

            // Déterminer le type d'email selon le sujet
            $type = null;
            if (str_contains($subject, 'Vérifiez votre adresse email') || str_contains($subject, 'vérification')) {
                $type = 'verification';
            } elseif (str_contains($subject, 'réinitialisation') || str_contains($subject, 'password')) {
                $type = 'password_reset';
            } elseif (str_contains($subject, 'Bienvenue') || str_contains($subject, 'welcome')) {
                $type = 'welcome';
            }

            // Récupérer l'utilisateur si possible
            $userId = null;
            if ($recipient) {
                $user = \App\Models\User::where('email', $recipient)->first();
                $userId = $user?->id;
            }

            // Extraire un aperçu du contenu
            $contentPreview = null;
            try {
                $htmlBody = $message->getHtmlBody();
                if ($htmlBody) {
                    // Nettoyer le HTML pour l'aperçu
                    $text = strip_tags($htmlBody);
                    $contentPreview = substr(trim($text), 0, 500);
                } else {
                    $textBody = $message->getTextBody();
                    if ($textBody) {
                        $contentPreview = substr(trim($textBody), 0, 500);
                    }
                }
            } catch (\Exception $e) {
                // Si on ne peut pas extraire le contenu, on continue
            }

            // Logger l'email
            EmailLogger::logSent(
                $userId,
                $recipient,
                $subject,
                $type,
                $contentPreview
            );
        } catch (\Exception $e) {
            // Ne pas bloquer l'envoi de l'email si le logging échoue
            Log::warning('Erreur lors du logging d\'email : ' . $e->getMessage());
        }
    }
}
