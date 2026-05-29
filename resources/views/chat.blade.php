<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel WebSocket Chat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .chat-messages {
            height: calc(100vh - 250px);
            overflow-y: auto;
        }
        .message {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .typing-indicator {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background-color: #9ca3af;
            border-radius: 50%;
            display: inline-block;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #c4b5fd;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 to-indigo-600 min-h-screen p-4">

<div x-data="chatApp()" x-init="init()" x-cloak class="max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
        <h1 class="text-white text-2xl font-bold">💬 Real-time Chat</h1>
        <p class="text-purple-200 text-sm">Powered by Laravel 12 + WebSocket</p>
    </div>

    <!-- User Name Input -->
    <div class="bg-purple-50 px-6 py-3 border-b border-purple-100">
        <div class="flex items-center gap-3">
            <label class="text-purple-700 font-semibold text-sm">Your Name:</label>
            <input 
                type="text" 
                x-model="userName"
                @change="saveUserName()"
                placeholder="Enter your name"
                class="flex-1 px-3 py-2 border border-purple-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
        </div>
    </div>

    <!-- Messages Area -->
    <div class="chat-messages bg-gray-50 px-6 py-4 space-y-3" x-ref="messagesContainer">
        <template x-for="msg in messages" :key="msg.id">
            <div :class="{'flex justify-end': msg.guest_name === userName, 'flex justify-start': msg.guest_name !== userName}" class="message">
                <div :class="{
                    'bg-purple-600 text-white rounded-l-2xl rounded-tr-2xl': msg.guest_name === userName,
                    'bg-white text-gray-800 rounded-r-2xl rounded-tl-2xl shadow-md': msg.guest_name !== userName
                }" class="max-w-[70%] px-4 py-2">
                    <p class="text-xs font-semibold mb-1" :class="{'text-purple-200': msg.guest_name === userName, 'text-purple-600': msg.guest_name !== userName}">
                        <span x-text="msg.guest_name"></span>
                    </p>
                    <p class="text-sm break-words" x-text="msg.message"></p>
                    <p class="text-xs mt-1" :class="{'text-purple-300': msg.guest_name === userName, 'text-gray-400': msg.guest_name !== userName}">
                        <span x-text="formatTime(msg.created_at)"></span>
                    </p>
                </div>
            </div>
        </template>
        
        <!-- Typing Indicator -->
        <div x-show="typingUser" class="flex justify-start">
            <div class="bg-white rounded-r-2xl rounded-tl-2xl shadow-md px-4 py-2">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="text-xs text-gray-500 ml-2" x-text="typingUser + ' is typing...'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="px-6 py-4 bg-white border-t border-gray-200">
        <div class="flex gap-2">
            <input 
                type="text" 
                x-model="message"
                @keydown.enter="sendMessage()"
                @input="onTyping()"
                placeholder="Type your message..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
            <button 
                @click="sendMessage()"
                class="bg-purple-600 text-white px-6 py-2 rounded-full hover:bg-purple-700 transition font-semibold">
                Send ➤
            </button>
        </div>
    </div>
</div>

<script>
function chatApp() {
    return {
        messages: [],
        message: '',
        userName: localStorage.getItem('chat_user_name') || '',
        typingUser: '',
        typingTimer: null,
        
        init() {
            // Set CSRF token for all axios requests
            axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            axios.defaults.headers.common['Accept'] = 'application/json';
            
            // Load messages on start
            this.loadMessages();
            
            // Setup Pusher for real-time
            this.setupPusher();
            
            // Scroll to bottom on load
            setTimeout(() => this.scrollToBottom(), 100);
        },
        
        loadMessages() {
            axios.get('/messages')
                .then(response => {
                    this.messages = response.data;
                    this.scrollToBottom();
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        },
        
        setupPusher() {
            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;
            
            const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
                cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
                wsHost: '{{ env("PUSHER_HOST") }}',
                wsPort: {{ env("PUSHER_PORT") }},
                forceTLS: false,
                disableStats: true,
                enabledTransports: ['ws', 'wss']
            });
            
            const channel = pusher.subscribe('chat');
            
            channel.bind('App\\Events\\MessageSent', (data) => {
                console.log('New message received:', data);
                // Check if message already exists
                const exists = this.messages.some(m => m.id === data.message.id);
                if (!exists) {
                    this.messages.push(data.message);
                    this.scrollToBottom();
                }
            });
            
            channel.bind('App\\Events\\UserTyping', (data) => {
                if (data.name !== this.userName) {
                    this.typingUser = data.name;
                    clearTimeout(this.typingTimer);
                    this.typingTimer = setTimeout(() => {
                        this.typingUser = '';
                    }, 2000);
                }
            });
        },
        
        sendMessage() {
            // Validation
            if (!this.message.trim()) {
                alert('Please enter a message');
                return;
            }
            
            if (!this.userName.trim()) {
                alert('Please enter your name first');
                return;
            }
            
            // Save name to localStorage
            localStorage.setItem('chat_user_name', this.userName);
            
            console.log('Sending message:', {
                user: this.userName,
                message: this.message,
                guest_name: this.userName
            });
            
            // Send message using POST method
            axios.post('/send-message', {
                user: this.userName,
                message: this.message,
                guest_name: this.userName
            })
            .then(response => {
                console.log('Message sent successfully:', response.data);
                this.message = '';
                this.scrollToBottom();
            })
            .catch(error => {
                console.error('Error sending message:', error);
                console.error('Error response:', error.response);
                alert('Failed to send message: ' + (error.response?.data?.message || error.message));
            });
        },
        
        onTyping() {
            if (!this.userName) return;
            
            axios.post('/typing', {
                name: this.userName
            }).catch(error => {
                console.error('Typing error:', error);
            });
        },
        
        saveUserName() {
            if (this.userName) {
                localStorage.setItem('chat_user_name', this.userName);
            }
        },
        
        scrollToBottom() {
            setTimeout(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        },
        
        formatTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>

</body>
</html>