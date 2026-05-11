<?php

namespace App\Events;

use App\Models\Drink;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrinkDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $id;
    public $class;

    /**
     * Create a new event instance.
     */
    public function __construct(Drink|string $class, ?int $id = null)
    {
        if ($class instanceof Drink) {
            $this->class = $class::class;
            $this->id = $class->id;
            return;
        }

        $this->class = $class;
        $this->id = $id;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
