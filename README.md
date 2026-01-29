# PHP_Laravel12_WebSocket_Integration



This project is a real-time chat application built using Laravel 12 and WebSocket technology with Pusher. The main goal of this project is to demonstrate how real-time communication works in Laravel using events and broadcasting.

In a traditional application, users must refresh the page to see new data. In this project, messages are delivered instantly to all connected users without refreshing the browser, using WebSockets.

## Application Features:

- Enter their name
- Send chat messages
- Receive messages in real time across multiple browser tabs or users


## This project is designed especially for beginners and freshers to understand:

- Laravel 12 project structure
- Events and Broadcasting
- WebSocket communication
- Real-time UI updates using Alpine.js
- Backend–Frontend communication using Axios

## Key Features

- Real-time chat using WebSockets
- Laravel 12 broadcasting with Pusher
- No page refresh required
- Multiple users can chat simultaneously
- Clean and modern UI
- Beginner-friendly code structure
- Uses Alpine.js for frontend reactivity
- Uses Axios for AJAX requests


## Technologies Used

- Backend: PHP, Laravel 12
- Frontend: Blade, Alpine.js, CSS
- Real-Time: WebSockets, Pusher
- Database: MySQL
- AJAX: Axios


## Learning Outcomes

After completing this project, you will understand:
- How WebSockets work in Laravel
- How Events and Broadcasting function
- How to integrate Pusher with Laravel 12
- How to build real-time features without page refresh



---



# Project Setup 

---

## STEP 1: Create New Laravel 12 Project

### Run Command :

```
composer create-project laravel/laravel PHP_Laravel12_WebSocket_Integration "12.*"

```

### Go inside project:

```
cd PHP_Laravel12_WebSocket_Integration

```

Make sure Laravel 12 is installed successfully.



## STEP 2: Database Configuration

### Open .env file and update database credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=websocket_db
DB_USERNAME=root
DB_PASSWORD=

```

### Create database:

```
websocket_db

```



## STEP 3: Install WebSocket Packages

### We will use Pusher (easy for beginners):

```
composer require pusher/pusher-php-server

npm install --save laravel-echo pusher-js

```


### Set broadcasting driver in .env:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

```

You can create a free Pusher account to get these keys.





## STEP 4: Create Message Model and Migration

## Run:

```
php artisan make:model Message -m

```


### Open the migration file in database/migrations/xxxx_create_messages_table.php and update:

```

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->string('user');
    $table->text('message');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

```

### app/Model/Message.php

```

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user', 'message']; // allow mass assignment
}


```

### Run migration:

```
php artisan migrate

```


## STEP 5: Create Event for WebSocket

### Run:

```
php artisan make:event MessageSent

```

### Edit app/Events/MessageSent.php:

```

<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }
}

```

ShouldBroadcast → Laravel will broadcast this event in real-time.



## Step 6: Create Controller

### Run:

```
php artisan make:controller ChatController

```

### app/Http/Controllers/ChatController.php:

```

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


```



## STEP 7: Create Chat View

### resources/views/chat.blade.php:

```

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Modern Laravel Chat</title>
<script src="//unpkg.com/alpinejs" defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<style>
    /* Body */
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    /* Chat Container */
    .chat-container {
        width: 100%;
        max-width: 450px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Header */
    .chat-header {
        background: #764ba2;
        color: white;
        padding: 20px;
        font-size: 1.2rem;
        font-weight: bold;
        text-align: center;
    }

    /* Messages Area */
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f5f5f5;
    }

    /* Message Bubbles */
    .message {
        max-width: 75%;
        padding: 10px 15px;
        border-radius: 20px;
        word-wrap: break-word;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        position: relative;
        animation: fadeIn 0.3s ease;
    }

    .message.user {
        background: #764ba2;
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 0;
    }

    .message.other {
        background: #e0e0e0;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 0;
    }

    /* Inputs */
    .chat-input {
        display: flex;
        gap: 10px;
        padding: 15px;
        background: #fff;
        border-top: 1px solid #ddd;
    }

    .chat-input input.user-name {
        flex: 1;
        min-width: 70px;
        padding: 10px 15px;
        border-radius: 25px;
        border: 1px solid #ccc;
        outline: none;
        transition: all 0.2s;
    }

    .chat-input input.user-name:focus {
        border-color: #764ba2;
        box-shadow: 0 0 5px rgba(118,75,162,0.5);
    }

    .chat-input input.message-text {
        flex: 2;
        min-width: 100px;
        padding: 10px 15px;
        border-radius: 25px;
        border: 1px solid #ccc;
        outline: none;
        transition: all 0.2s;
    }

    .chat-input input.message-text:focus {
        border-color: #764ba2;
        box-shadow: 0 0 5px rgba(118,75,162,0.5);
    }

    .chat-input button {
        flex: 0 0 auto;
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        background: #764ba2;
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
    }

    .chat-input button:hover {
        background: #667eea;
    }

    /* Scrollbar Styling */
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .chat-messages::-webkit-scrollbar-thumb {
        background: rgba(118,75,162,0.5);
        border-radius: 3px;
    }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body x-data="chatApp()" x-init="init()">

<div class="chat-container">
    <div class="chat-header">Laravel WebSocket Chat</div>

    <div class="chat-messages" id="chatMessages">
        <template x-for="msg in messages" :key="msg.id">
            <div :class="{'message user': msg.user === user, 'message other': msg.user !== user}">
                <strong x-text="msg.user + ':'"></strong> <span x-text="msg.message"></span>
            </div>
        </template>
    </div>

    <div class="chat-input">
        <input type="text" x-model="user" placeholder="Your Name" class="user-name">
        <input type="text" x-model="message" placeholder="Type a message..." class="message-text">
        <button @click="sendMessage()">Send</button>
    </div>
</div>

<script>
function chatApp() {
    return {
        user: '',
        message: '',
        messages: @json($messages),

        sendMessage() {
            if(this.user === '' || this.message === '') return;

            axios.post('/send-message', { user: this.user, message: this.message })
                .then(res => {
                    this.messages.push(res.data);
                    this.message = '';
                    scrollToBottom();
                });
        },

        init() {
            const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
                cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
                wsHost: '{{ env("PUSHER_HOST") }}',
                wsPort: {{ env("PUSHER_PORT") }},
                forceTLS: false,
                disableStats: true
            });

            const channel = pusher.subscribe('chat');
            channel.bind('App\\Events\\MessageSent', (data) => {
                this.messages.push(data.message);
                scrollToBottom();
            });

            function scrollToBottom() {
                const chat = document.getElementById('chatMessages');
                chat.scrollTop = chat.scrollHeight;
            }
        }
    }
}
</script>

</body>
</html>


```


## Step 8: Define Routes

### routes/web.php:

```

<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ChatController;

Route::get('/', [ChatController::class, 'index']);
Route::post('/send-message', [ChatController::class, 'sendMessage']);

```


## STEP 9: Run Server

### Run:

```
php artisan serve

```

### Open in browser:

```
 http://127.0.0.1:8000

```

Test: Open two tabs → send a message → it should appear in real-time in the other tab.



## So you can see this type Output:


### Tab-1(write name-demo3 & message-this is best):


<img width="1919" height="966" alt="Screenshot 2026-01-29 153642" src="https://github.com/user-attachments/assets/f62008bf-aad7-47de-9999-5e8a17d9ebc9" />


### Tab-2:


<img width="1919" height="953" alt="Screenshot 2026-01-29 153701" src="https://github.com/user-attachments/assets/b85eee30-0aaf-422b-977e-0f1c2ad40f83" />



---


# Project Folder Structure:

```
PHP_Laravel12_WebSocket_Integration
│
├── app
│   ├── Events
│   │   └── MessageSent.php        # WebSocket Event
│   │
│   ├── Http
│   │   └── Controllers
│   │       └── ChatController.php # Chat logic (send & receive)
│   │
│   ├── Models
│   │   └── Message.php            # Message Model
│   │
│   └── Providers
│
├── bootstrap
│   └── app.php
│
├── config
│   ├── app.php
│   ├── broadcasting.php           # Broadcasting config (Pusher)
│   └── database.php
│
├── database
│   ├── migrations
│   │   └── xxxx_xx_xx_create_messages_table.php
│   │
│   └── seeders
│
├── public
│   └── index.php
│
├── resources
│   ├── views
│   │   └── chat.blade.php          # Chat UI (Alpine.js + Pusher)
│   │
│   ├── css
│   └── js
│
├── routes
│   └── web.php                     # Chat routes
│
├── storage
│   ├── app
│   ├── framework
│   └── logs
│
├── tests
│
├── .env                            # Environment config
├── .env.example
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── vite.config.js
├── README.md                       # Project documentation
└── phpunit.xml

```
