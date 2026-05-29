<?php
// app/Http/Controllers/ChatController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\OnlineUser;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\MessageEdited;
use App\Events\MessageRead;
use App\Events\UserTyping;
use App\Events\MessageReacted;
use App\Events\UserOffline;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = session()->getId();
        $userName = $request->query('name', 'Guest');
        
        // Update or create online user
        OnlineUser::updateOrCreate(
            ['session_id' => $userId],
            [
                'user_name' => $userName,
                'last_activity' => now()
            ]
        );
        
        // Get online users
        $onlineUsers = OnlineUser::where('last_activity', '>=', now()->subMinutes(5))
            ->orderBy('user_name')
            ->get();
        
        // Get messages (public + private for this user)
        $messages = Message::where(function($query) use ($userId) {
                $query->whereNull('receiver_id')
                      ->orWhere('receiver_id', $userId)
                      ->orWhere('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Mark messages as read
        Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'messages' => $messages,
                'online_users' => $onlineUsers
            ]);
        }
        
        return view('chat', compact('messages', 'onlineUsers', 'userName'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'file' => 'nullable|file|max:2048',
            'guest_name' => 'nullable|string|max:50',
            'receiver_id' => 'nullable|exists:online_users,session_id'
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('chat', 'public');
        }

        $message = Message::create([
            'user_id' => session()->getId(),
            'guest_name' => $request->input('guest_name', 'Guest'),
            'receiver_id' => $request->input('receiver_id'),
            'message' => $request->message,
            'file_path' => $filePath,
            'reactions' => null,
            'is_edited' => false,
            'is_read' => false
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
    
    public function editMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);
        
        $message = Message::findOrFail($id);
        $oldMessage = $message->message;
        $message->message = $request->message;
        $message->is_edited = true;
        $message->save();
        
        broadcast(new MessageEdited($id, $request->message, !is_null($message->receiver_id), $message->receiver_id))->toOthers();
        
        return response()->json(['status' => 'edited', 'message' => $message]);
    }

    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        broadcast(new MessageDeleted($id))->toOthers();

        return response()->json(['status' => 'deleted']);
    }
    
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->markAsRead();
        
        broadcast(new MessageRead($id, session()->get('guest_name', 'Guest')))->toOthers();
        
        return response()->json(['status' => 'read']);
    }

    public function typing(Request $request)
    {
        $name = $request->input('name', 'Guest');
        $receiverId = $request->input('receiver_id');
        
        broadcast(new UserTyping(session()->getId(), $name, $receiverId))->toOthers();
        
        return response()->json(['status' => 'ok']);
    }

    public function react(Request $request, $id)
    {
        $request->validate(['emoji' => 'required|string|max:10']);

        $message = Message::findOrFail($id);
        $reactions = $message->reactions ?? [];
        $emoji = $request->emoji;
        $reactions[$emoji] = ($reactions[$emoji] ?? 0) + 1;
        $message->reactions = $reactions;
        $message->save();

        broadcast(new MessageReacted($id, $emoji))->toOthers();

        return response()->json(['status' => 'reacted', 'reactions' => $reactions]);
    }
    
    public function searchMessages(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);
        
        $query = $request->query('query');
        $userId = session()->getId();
        
        $messages = Message::where(function($q) use ($userId) {
                $q->whereNull('receiver_id')
                  ->orWhere('receiver_id', $userId)
                  ->orWhere('user_id', $userId);
            })
            ->where('message', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json($messages);
    }
    
    public function heartbeat(Request $request)
    {
        $userId = session()->getId();
        $userName = $request->input('name', 'Guest');
        
        OnlineUser::updateOrCreate(
            ['session_id' => $userId],
            [
                'user_name' => $userName,
                'last_activity' => now()
            ]
        );
        
        // Clean up old entries
        OnlineUser::where('last_activity', '<', now()->subMinutes(5))->delete();
        
        $onlineUsers = OnlineUser::where('last_activity', '>=', now()->subMinutes(5))
            ->orderBy('user_name')
            ->get();
            
        return response()->json($onlineUsers);
    }
    
    public function logout(Request $request)
    {
        $userId = session()->getId();
        $userName = $request->input('name', 'Guest');
        
        OnlineUser::where('session_id', $userId)->delete();
        
        broadcast(new UserOffline($userName))->toOthers();
        
        return response()->json(['status' => 'offline']);
    }

    public function getMessages()
{
    $messages = Message::orderBy('created_at', 'asc')->get();
    return response()->json($messages);
}
}