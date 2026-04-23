<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat', function () {
    return [
        'id' => uniqid(),
        'name' => 'Guest'
    ];
});