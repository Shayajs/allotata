<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    /**
     * Envoyer un email en utilisant un template
     * 
     * @param string $type Type de template (welcome, reservation_confirmation, etc.)
     * @param string $to Adresse email du destinataire
     * @param array $data Données pour remplacer les variables dans le template
     * @param array $options Options supplémentaires (cc, bcc, attachments, etc.)
     * @return bool
     */
    public static function send(string $type, string $to, array $data = [], array $options = []): bool
    {
        try {
            $template = EmailTemplate::getByType($type);
            
            if (!$template) {
                Log::error("Template email introuvable : {$type}");
                return false;
            }

            // Remplacer les variables
            $replaced = $template->replaceVariables($data);
            
            // Préparer les données pour la vue
            $viewData = array_merge($data, [
                'subject' => $replaced['subject'],
                'body' => $replaced['body'],
                'template' => $template,
            ]);

            // Envoyer l'email
            Mail::send('emails.template', $viewData, function ($message) use ($to, $replaced, $options) {
                $message->to($to)
                        ->subject($replaced['subject']);
                
                // Options supplémentaires
                if (isset($options['cc'])) {
                    $message->cc($options['cc']);
                }
                if (isset($options['bcc'])) {
                    $message->bcc($options['bcc']);
                }
                if (isset($options['reply_to'])) {
                    $message->replyTo($options['reply_to']);
                }
                if (isset($options['attachments'])) {
                    foreach ($options['attachments'] as $attachment) {
                        $message->attach($attachment);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email avec template {$type} : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir le contenu d'un template (pour prévisualisation)
     */
    public static function getContent(string $type, array $data = []): ?array
    {
        $template = EmailTemplate::getByType($type);
        
        if (!$template) {
            return null;
        }

        return $template->replaceVariables($data);
    }

    /**
     * Créer ou mettre à jour un template
     */
    public static function createOrUpdate(string $type, array $attributes): EmailTemplate
    {
        return EmailTemplate::updateOrCreate(
            ['type' => $type],
            $attributes
        );
    }
}
