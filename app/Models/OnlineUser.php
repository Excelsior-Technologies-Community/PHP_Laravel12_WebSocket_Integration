<?php
// app/Models/OnlineUser.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    protected $fillable = ['session_id', 'user_name', 'last_activity'];
    
    protected $casts = [
        'last_activity' => 'datetime'
    ];
}