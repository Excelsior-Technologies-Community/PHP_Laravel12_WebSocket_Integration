<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageDeleted;

class ChatController extends Controller
{
    public function index()
    {
        $messages = Message::orderBy('created_at', 'asc')->get();
        return view('chat', compact('messages'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'user' => 'required|string|max:50',
            'message' => 'required|string|max:1000'
        ]);

        $message = Message::create([
            'user' => $request->user,
            'message' => $request->message
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function deleteMessage($id)
    {
        Message::findOrFail($id)->delete();

        broadcast(new MessageDeleted($id))->toOthers();

        return response()->json(['status' => 'deleted']);
    }
}