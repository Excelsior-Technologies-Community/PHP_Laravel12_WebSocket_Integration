<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel WebSocket Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script>
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }
        .msg-anim    { animation: fadeIn 0.25s ease; }
        .pulse-dot   { animation: pulse-dot 1.5s infinite; }
        .scrollbar-thin::-webkit-scrollbar       { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 4px; }
        .delete-btn  { opacity: 0; transition: opacity 0.2s; }
        .msg-wrap:hover .delete-btn { opacity: 1; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-700 p-3"
      x-data="chatApp()" x-init="init()">

<div class="flex gap-3 w-full max-w-4xl h-[92vh]">

    <!-- ───── Online Sidebar ───── -->
    <div class="w-44 flex-shrink-0 bg-white/20 backdrop-blur rounded-2xl p-4 flex flex-col gap-2 overflow-y-auto scrollbar-thin">
        <p class="text-white/70 text-xs uppercase tracking-widest font-semibold">Online</p>
        <p class="text-white text-4xl font-bold leading-none" x-text="onlineCount"></p>
        <p class="text-white/80 text-xs flex items-center gap-1">
            <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot inline-block"></span>
            Active Users
        </p>
        <hr class="border-white/20 my-1">
        <template x-for="(name, idx) in onlineUsers" :key="idx">
            <div class="flex items-center gap-2 text-white text-xs">
                <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot flex-shrink-0"></span>
                <span x-text="name" class="truncate"></span>
            </div>
        </template>
    </div>

    <!-- ───── Main Chat ───── -->
    <div class="flex-1 bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-indigo-500 px-5 py-4 flex items-center justify-between flex-shrink-0">
            <div>
                <p class="text-white font-bold text-base">💬 Laravel WebSocket Chat</p>
                <p class="text-white/70 text-xs mt-0.5">Real-time · Laravel 12 + Reverb</p>
            </div>
            <span class="text-white/60 text-xs bg-white/10 px-3 py-1 rounded-full">
                WebSocket Live
            </span>
        </div>

        <!-- Name Bar -->
        <div class="bg-purple-50 border-b border-purple-100 px-4 py-2.5 flex items-center gap-3 flex-shrink-0">
            <label class="text-purple-700 text-xs font-semibold whitespace-nowrap">👤 Your Name</label>
            <input
                type="text"
                x-model="guestName"
                placeholder="Enter your name..."
                maxlength="30"
                class="flex-1 text-sm px-4 py-1.5 rounded-full border border-purple-300 outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-200 transition"
            >
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto scrollbar-thin bg-slate-50 px-4 py-3 flex flex-col gap-2" id="chatMessages">

            <!-- Load More -->
            <button
                x-show="hasMore"
                @click="loadMore()"
                class="self-center text-xs text-purple-600 border border-purple-300 bg-white px-4 py-1.5 rounded-full hover:bg-purple-600 hover:text-white transition mb-1"
            >
                ⬆ Load Older Messages
            </button>

            <!-- Message Loop -->
            <template x-for="msg in messages" :key="msg.id">
                <div class="msg-wrap flex"
                     :class="isMyMessage(msg) ? 'justify-end' : 'justify-start'">

                    <div class="relative max-w-[70%] msg-anim">

                        <!-- Bubble -->
                        <div :class="isMyMessage(msg)
                                ? 'bg-gradient-to-br from-purple-600 to-indigo-500 text-white rounded-2xl rounded-br-sm'
                                : 'bg-white text-gray-800 border border-gray-100 rounded-2xl rounded-bl-sm shadow-sm'"
                             class="px-4 py-2.5">

                            <!-- Sender Name -->
                            <p class="text-[11px] font-semibold mb-1"
                               :class="isMyMessage(msg) ? 'text-purple-200' : 'text-purple-500'"
                               x-text="msg.guest_name || (msg.user ? msg.user.name : 'Guest')">
                            </p>

                            <!-- Message Text -->
                            <p class="text-sm leading-relaxed break-words" x-text="msg.message"></p>

                            <!-- File Attachment -->
                            <template x-if="msg.file_path">
                                <a :href="'/storage/' + msg.file_path"
                                   target="_blank"
                                   :class="isMyMessage(msg) ? 'bg-white/20 text-white' : 'bg-purple-50 text-purple-600'"
                                   class="inline-flex items-center gap-1 mt-2 text-xs px-3 py-1 rounded-full no-underline hover:opacity-80 transition">
                                    📎 Download File
                                </a>
                            </template>

                            <!-- Reactions -->
                            <div class="flex flex-wrap gap-1 mt-2">
                                <template x-for="emoji in ['👍','❤️','😂','😮','🔥']" :key="emoji">
                                    <button
                                        @click="react(msg, emoji)"
                                        :class="isMyMessage(msg) ? 'bg-white/15 hover:bg-white/30' : 'bg-gray-100 hover:bg-gray-200'"
                                        class="flex items-center gap-1 text-sm px-2 py-0.5 rounded-full border-none cursor-pointer transition hover:scale-110">
                                        <span x-text="emoji"></span>
                                        <span class="text-[11px]"
                                              :class="isMyMessage(msg) ? 'text-white/80' : 'text-gray-500'"
                                              x-show="msg.reactions && msg.reactions[emoji] > 0"
                                              x-text="msg.reactions && msg.reactions[emoji] ? msg.reactions[emoji] : ''">
                                        </span>
                                    </button>
                                </template>
                            </div>

                            <!-- Time -->
                            <p class="text-[10px] mt-1.5 text-right"
                               :class="isMyMessage(msg) ? 'text-white/50' : 'text-gray-400'"
                               x-text="formatTime(msg.created_at)">
                            </p>
                        </div>

                        <!-- Delete Button -->
                        <button
                            @click="deleteMessage(msg.id)"
                            class="delete-btn absolute -top-2 -right-7 text-red-400 hover:text-red-600 text-base bg-white rounded-full w-6 h-6 flex items-center justify-center shadow border border-red-100"
                            title="Delete">
                            🗑
                        </button>

                    </div>
                </div>
            </template>
        </div>

        <!-- Typing Indicator -->
        <div class="px-4 py-1 text-xs text-gray-400 italic min-h-[22px] flex-shrink-0" x-text="typingText"></div>

        <!-- Input Area -->
        <div class="px-4 py-3 bg-white border-t border-gray-100 flex-shrink-0">
            <div class="flex items-center gap-2">

                <!-- File Attach -->
                <label class="cursor-pointer text-gray-400 hover:text-purple-600 transition text-xl flex-shrink-0" title="Attach file">
                    📎
                    <input type="file" class="hidden" @change="onFileChange($event)">
                </label>

                <!-- Text Input -->
                <input
                    type="text"
                    x-model="message"
                    placeholder="Type a message..."
                    @keydown.enter="sendMessage()"
                    @input="onTyping()"
                    class="flex-1 px-4 py-2.5 rounded-full border border-gray-200 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm transition"
                >

                <!-- Send Button -->
                <button
                    @click="sendMessage()"
                    class="bg-gradient-to-r from-purple-600 to-indigo-500 text-white px-5 py-2.5 rounded-full font-semibold text-sm hover:opacity-90 active:scale-95 transition flex-shrink-0">
                    Send ➤
                </button>
            </div>

            <!-- File Preview -->
            <p x-show="fileName"
               x-text="'📎 ' + fileName"
               class="text-xs text-purple-500 mt-1.5 pl-2">
            </p>
        </div>

    </div>
</div>

<script>
function chatApp() {
    return {
        message:      '',
        file:         null,
        fileName:     '',
        guestName:    localStorage.getItem('chat_guest_name') || '',
        messages:     @json($messages->items()),
        hasMore:      {{ $messages->hasMorePages() ? 'true' : 'false' }},
        page:         1,
        onlineCount:  1,
        onlineUsers:  [],
        typingText:   '',
        typingTimer:  null,
        _prevName:    '',

        init() {
            // Name change watch — localStorage + online list update
            this.$watch('guestName', val => {
                localStorage.setItem('chat_guest_name', val);
                const idx = this.onlineUsers.indexOf(this._prevName);
                if (idx !== -1) this.onlineUsers.splice(idx, 1, val);
                else if (val)   this.onlineUsers.push(val);
                this._prevName  = val;
                this.onlineCount = this.onlineUsers.filter(Boolean).length;
            });

            if (this.guestName) {
                this.onlineUsers.push(this.guestName);
                this._prevName   = this.guestName;
                this.onlineCount = 1;
            }

            // Pusher / Reverb connect
            const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
                cluster:      '{{ env("PUSHER_APP_CLUSTER") }}',
                wsHost:       '{{ env("PUSHER_HOST") }}',
                wsPort:       {{ env("PUSHER_PORT") }},
                forceTLS:     false,
                disableStats: true,
            });

            const channel = pusher.subscribe('chat');

            // New message
            channel.bind('App\\Events\\MessageSent', (data) => {
                if (!this.messages.find(m => m.id === data.message.id)) {
                    this.messages.push(data.message);
                    this.scrollToBottom();
                }
            });

            // Message deleted
            channel.bind('App\\Events\\MessageDeleted', (data) => {
                this.messages = this.messages.filter(m => m.id !== data.id);
            });

            // Typing
            channel.bind('App\\Events\\UserTyping', (data) => {
                if (data.name !== this.guestName) {
                    this.typingText = data.name + ' is typing...';
                    clearTimeout(this.typingTimer);
                    this.typingTimer = setTimeout(() => { this.typingText = ''; }, 2500);
                }
            });

            // Reaction
            channel.bind('App\\Events\\MessageReacted', (data) => {
                const msg = this.messages.find(m => m.id === data.message_id);
                if (msg) {
                    if (!msg.reactions) msg.reactions = {};
                    msg.reactions[data.emoji] = (msg.reactions[data.emoji] || 0) + 1;
                }
            });

            this.scrollToBottom();
        },

        isMyMessage(msg) {
            if (!this.guestName) return false;
            return msg.guest_name === this.guestName;
        },

        sendMessage() {
            if (!this.message.trim()) return;
            if (!this.guestName.trim()) {
                alert('Please enter your name first!');
                return;
            }

            const formData = new FormData();
            formData.append('message',    this.message);
            formData.append('guest_name', this.guestName);
            if (this.file) formData.append('file', this.file);

            axios.post('/send-message', formData)
                .then(res => {
                    this.messages.push(res.data);
                    this.message  = '';
                    this.file     = null;
                    this.fileName = '';
                    this.scrollToBottom();
                })
                .catch(err => {
                    const msg = err.response?.data?.message || err.message;
                    alert('Error: ' + msg);
                });
        },

        deleteMessage(id) {
            if (!confirm('Delete this message?')) return;
            axios.delete('/message/' + id)
                .then(() => {
                    this.messages = this.messages.filter(m => m.id !== id);
                })
                .catch(err => {
                    alert('Delete failed: ' + err.message);
                });
        },

        react(msg, emoji) {
            if (!msg.reactions) msg.reactions = {};
            msg.reactions[emoji] = (msg.reactions[emoji] || 0) + 1;
            axios.post('/message/' + msg.id + '/react', { emoji })
                .catch(err => console.error('React error:', err));
        },

        onFileChange(event) {
            this.file     = event.target.files[0] || null;
            this.fileName = this.file ? this.file.name : '';
        },

        onTyping() {
            if (!this.guestName) return;
            axios.post('/typing', { name: this.guestName }).catch(() => {});
        },

        loadMore() {
            this.page++;
            axios.get('/?page=' + this.page).then(res => {
                this.messages = [...res.data.data, ...this.messages];
                this.hasMore  = res.data.next_page_url !== null;
            });
        },

        scrollToBottom() {
            setTimeout(() => {
                const el = document.getElementById('chatMessages');
                if (el) el.scrollTop = el.scrollHeight;
            }, 100);
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>
</body>
</html>

