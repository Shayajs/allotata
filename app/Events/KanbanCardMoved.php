<?php

namespace App\Events;

use App\Models\KanbanCard;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KanbanCardMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $card;
    public $oldColumnId;
    public $newColumnId;

    /**
     * Create a new event instance.
     */
    public function __construct(KanbanCard $card, $oldColumnId, $newColumnId)
    {
        $this->card = $card->load('assignee', 'creator');
        $this->oldColumnId = $oldColumnId;
        $this->newColumnId = $newColumnId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('kanban.' . $this->card->board_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'card.moved';
    }
}
