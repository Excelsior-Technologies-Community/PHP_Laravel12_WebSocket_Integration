<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_name',
        'message',
        'file_path',
        'reactions',
    ];

    protected $casts = [
        'reactions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}