<?php
// database/migrations/2026_01_29_073907_create_messages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->unsignedBigInteger('receiver_id')->nullable(); // For private messages
            $table->text('message');
            $table->string('file_path')->nullable();
            $table->json('reactions')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'receiver_id']);
        });
        
        // Create online_users table
        Schema::create('online_users', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('user_name');
            $table->timestamp('last_activity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('online_users');
    }
};