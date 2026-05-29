<?php
// app/Events/MessageEdited.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEdited implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message_id;
    public $new_message;
    public $is_private;
    public $receiver_id;

    public function __construct($message_id, $new_message, $is_private = false, $receiver_id = null)
    {
        $this->message_id = $message_id;
        $this->new_message = $new_message;
        $this->is_private = $is_private;
        $this->receiver_id = $receiver_id;
    }

    public function broadcastOn(): array
    {
        if ($this->is_private && $this->receiver_id) {
            return [
                new Channel('private-user.' . $this->receiver_id),
                new Channel('chat')
            ];
        }
        return [new Channel('chat')];
    }
}