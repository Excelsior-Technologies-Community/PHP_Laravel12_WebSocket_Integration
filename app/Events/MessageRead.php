<?php
// app/Events/MessageRead.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message_id;
    public $reader_name;

    public function __construct($message_id, $reader_name)
    {
        $this->message_id = $message_id;
        $this->reader_name = $reader_name;
    }

    public function broadcastOn(): array
    {
        return [new Channel('chat')];
    }
}