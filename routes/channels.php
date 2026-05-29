<?php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat', function () {
    return true;
});

Broadcast::channel('private-user.{id}', function ($user, $id) {
    return session()->getId() === $id;
});