<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessageEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $conversation;

    /**
     * Create a new message instance.
     */
    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $senderName = $this->message->user ? $this->message->user->name : 'Un utilisateur';
        
        if ($this->conversation->user_id) {
            // Message pour un client
            $subject = "Nouveau message de {$this->conversation->entreprise->nom}";
        } else {
            // Message pour un gérant
            $subject = "Nouveau message de {$senderName}";
        }

        return $this->subject($subject)
                    ->view('emails.new-message')
                    ->with([
                        'message' => $this->message,
                        'conversation' => $this->conversation,
                    ]);
    }
}
