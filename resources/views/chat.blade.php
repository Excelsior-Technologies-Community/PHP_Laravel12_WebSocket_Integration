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
