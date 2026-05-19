<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', [ChatController::class, 'index']);
Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::delete('/message/{id}', [ChatController::class, 'deleteMessage']);
Route::post('/typing', [ChatController::class, 'typing']);
Route::post('/message/{id}/react', [ChatController::class, 'react']);