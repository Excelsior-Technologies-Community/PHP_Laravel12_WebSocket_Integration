<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $user_id;
    public $name;

    public function __construct($user_id, $name)
    {
        $this->user_id = $user_id;
        $this->name    = $name;
    }

    public function broadcastOn(): array
    {
        return [new Channel('chat')];
    }

    public function broadcastAs(): string
    {
        return 'App\\Events\\UserTyping';
    }
}