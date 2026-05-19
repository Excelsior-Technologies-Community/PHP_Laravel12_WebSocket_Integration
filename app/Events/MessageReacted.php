<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message_id;
    public $emoji;

    public function __construct($message_id, $emoji)
    {
        $this->message_id = $message_id;
        $this->emoji      = $emoji;
    }

    public function broadcastOn(): array
    {
        return [new Channel('chat')];
    }

    public function broadcastAs(): string
    {
        return 'App\\Events\\MessageReacted';
    }
}