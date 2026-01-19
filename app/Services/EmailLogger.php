<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;

class EmailLogger
{
    /**
     * Logger un email envoyé
     */
    public static function log(
        ?int $userId,
        string $recipientEmail,
        string $subject,
        ?string $type = null,
        string $status = 'sent',
        ?string $errorMessage = null,
        ?string $contentPreview = null,
        ?string $ipAddress = null
    ): EmailLog {
        try {
            return EmailLog::create([
                'user_id' => $userId,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'type' => $type,
                'status' => $status,
                'error_message' => $errorMessage,
                'content_preview' => $contentPreview ? substr($contentPreview, 0, 500) : null,
                'ip_address' => $ipAddress ?? request()->ip(),
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du logging d\'email : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Logger un email envoyé avec succès
     */
    public static function logSent(
        ?int $userId,
        string $recipientEmail,
        string $subject,
        ?string $type = null,
        ?string $contentPreview = null
    ): EmailLog {
        return self::log($userId, $recipientEmail, $subject, $type, 'sent', null, $contentPreview);
    }

    /**
     * Logger un email en échec
     */
    public static function logFailed(
        ?int $userId,
        string $recipientEmail,
        string $subject,
        string $errorMessage,
        ?string $type = null,
        ?string $contentPreview = null
    ): EmailLog {
        return self::log($userId, $recipientEmail, $subject, $type, 'failed', $errorMessage, $contentPreview);
    }
}
