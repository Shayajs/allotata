<?php

namespace App\Events;

use App\Models\Note;
use App\Models\NoteCursor;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteCursorMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $note;
    public $user;
    public $cursor;

    /**
     * Create a new event instance.
     */
    public function __construct(Note $note, User $user, NoteCursor $cursor)
    {
        $this->note = $note;
        $this->user = $user;
        $this->cursor = $cursor->load('user');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('note.' . $this->note->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'cursor.moved';
    }
}
