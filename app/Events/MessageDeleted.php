<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageDeleted implements ShouldBroadcast
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('chat');
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }
}