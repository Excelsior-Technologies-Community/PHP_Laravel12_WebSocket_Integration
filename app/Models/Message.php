<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'guest_name', 
        'receiver_id',
        'message', 
        'file_path', 
        'reactions',
        'is_edited',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'reactions' => 'array',
        'is_edited' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }
}