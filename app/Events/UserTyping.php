<?php
// app/Events/UserTyping.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }
    
    public function broadcastAs()
    {
        return 'App\\Events\\UserTyping';
    }
}