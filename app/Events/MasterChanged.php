<?php

namespace App\Events;

use App\Models\Note;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MasterChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $note;
    public $masterUserId;
    public $masterUserName;

    /**
     * Create a new event instance.
     */
    public function __construct(Note $note, ?User $masterUser = null)
    {
        $this->note = $note;
        $this->masterUserId = $masterUser?->id;
        $this->masterUserName = $masterUser?->name;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('presence-note.' . $this->note->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'master.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'note_id' => $this->note->id,
            'master_user_id' => $this->masterUserId,
            'master_user_name' => $this->masterUserName,
        ];
    }
}
