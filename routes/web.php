<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', [ChatController::class, 'index']);
Route::get('/messages', [ChatController::class, 'getMessages']);
Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::post('/typing', [ChatController::class, 'typing']);