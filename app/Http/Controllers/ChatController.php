<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\UserTyping;
use App\Events\MessageReacted;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $messages = Message::orderBy('created_at', 'asc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($messages);
        }

        return view('chat', compact('messages'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:1000',
            'file'       => 'nullable|file|max:2048',
            'guest_name' => 'nullable|string|max:50',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('chat', 'public');
        }

        $message = Message::create([
            'user_id'    => null,
            'guest_name' => $request->input('guest_name', 'Guest'),
            'message'    => $request->message,
            'file_path'  => $filePath,
            'reactions'  => null,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        broadcast(new MessageDeleted($id))->toOthers();

        return response()->json(['status' => 'deleted']);
    }

    public function typing(Request $request)
    {
        $name = $request->input('name', 'Guest');
        broadcast(new UserTyping(session()->getId(), $name))->toOthers();
        return response()->json(['status' => 'ok']);
    }

    public function react(Request $request, $id)
    {
        $request->validate(['emoji' => 'required|string|max:10']);

        $message           = Message::findOrFail($id);
        $reactions         = $message->reactions ?? [];
        $emoji             = $request->emoji;
        $reactions[$emoji] = ($reactions[$emoji] ?? 0) + 1;
        $message->reactions = $reactions;
        $message->save();

        broadcast(new MessageReacted($id, $emoji))->toOthers();

        return response()->json(['status' => 'reacted', 'reactions' => $reactions]);
    }
}