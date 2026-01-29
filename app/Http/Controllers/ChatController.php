<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\MessageSent;

class ChatController extends Controller
{
    // Show chat page
    public function index()
    {
        $messages = Message::orderBy('created_at', 'asc')->get();
        return view('chat', compact('messages'));
    }

    // Send message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'user' => 'required',
            'message' => 'required'
        ]);

        $message = Message::create([
            'user' => $request->user,
            'message' => $request->message
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
