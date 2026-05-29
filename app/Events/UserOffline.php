<?php
// app/Events/UserOffline.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOffline implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $user_name;

    public function __construct($user_name)
    {
        $this->user_name = $user_name;
    }

    public function broadcastOn(): array
    {
        return [new Channel('chat')];
    }
}