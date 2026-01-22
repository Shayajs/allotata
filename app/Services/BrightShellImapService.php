<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Webklex\IMAP\Facades\Client as ImapClient;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;

class BrightShellImapService
{
    /**
     * Liste les messages reçus (INBOX).
     *
     * @return Collection<int, object{id: int, from: string, subject: string, date: Carbon}>
     */
    public function listInbox(int $limit = 50): Collection
    {
        try {
            $client = ImapClient::account('brightshell');
            $client->connect();
            $folder = $client->getFolder('INBOX');
        } catch (ConnectionFailedException|FolderFetchingException $e) {
            \Log::warning('BrightShell IMAP: ' . $e->getMessage());
            return collect();
        }

        try {
            $messages = $folder->query()
                ->all()
                ->setFetchBody(false)
                ->setFetchFlags(false)
                ->limit($limit, 1)
                ->get();
        } catch (GetMessagesFailedException $e) {
            \Log::warning('BrightShell IMAP fetch: ' . $e->getMessage());
            return collect();
        }

        $result = collect();
        foreach ($messages as $msg) {
            $from = $msg->getFrom();
            $fromStr = '';
            if ($from) {
                // L'objet Attribute de php-imap n'est pas toujours countable, on tente d'accéder au premier élément s'il existe
                try {
                    $address = null;
                    if (is_array($from) && isset($from[0])) {
                        $address = $from[0];
                    } elseif (isset($from[0])) {
                        $address = $from[0];
                    }
                    
                    if ($address) {
                        $fromStr = $address->mail ?? $address->full ?? (string)$address;
                    }
                } catch (\Exception $e) {
                    // Fallback si l'accès par index échoue
                    $fromStr = (string)$from;
                }
            }
            $date = $msg->getDate();
            $result->push((object) [
                'id' => $msg->getUid(),
                'from' => $fromStr,
                'subject' => $msg->getSubject() ?? '',
                'date' => $date && method_exists($date, 'toDate') ? $date->toDate() : now(),
            ]);
        }

        return $result;
    }

    /**
     * Récupère un message reçu par UID.
     *
     * @return object{id: int, from: string, subject: string, date: Carbon, body_html: string, body_text: string}|null
     */
    public function getMessage(int $uid): ?object
    {
        try {
            $client = ImapClient::account('brightshell');
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $msg = $folder->query()->getMessageByUid($uid);
        } catch (\Throwable $e) {
            \Log::warning('BrightShell IMAP getMessage: ' . $e->getMessage());
            return null;
        }

        $from = $msg->getFrom();
        $fromStr = '';
        if ($from) {
            try {
                $address = null;
                if (is_array($from) && isset($from[0])) {
                    $address = $from[0];
                } elseif (isset($from[0])) {
                    $address = $from[0];
                }
                
                if ($address) {
                    $fromStr = $address->mail ?? $address->full ?? (string)$address;
                }
            } catch (\Exception $e) {
                $fromStr = (string)$from;
            }
        }
        $date = $msg->getDate();

        return (object) [
            'id' => $msg->getUid(),
            'from' => $fromStr,
            'subject' => $msg->getSubject() ?? '',
            'date' => $date && method_exists($date, 'toDate') ? $date->toDate() : now(),
            'body_html' => $msg->getHTMLBody() ?: '',
            'body_text' => $msg->getTextBody() ?: '',
        ];
    }

    public function isConfigured(): bool
    {
        return !empty(config('imap.accounts.brightshell.password'));
    }
}
