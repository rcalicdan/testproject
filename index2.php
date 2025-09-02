<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gemini AI Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-gray-900 text-gray-200 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div id="sidebar"
        class="sidebar-container"
        x-data="sidebarController()"
        x-init="init()"
        :class="{ 'show': sidebarVisible }">

        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-200">Conversations</h2>
                <button @click="hideSidebar()" class="lg:hidden text-gray-400 hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <button @click="createNewChat()" class="new-chat-btn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>New Chat</span>
            </button>
        </div>

        <!-- Conversations List -->
        <div class="sidebar-content">
            <div class="conversations-list">
                <template x-for="conversation in conversations" :key="conversation.id">
                    <div class="conversation-item"
                        :class="{ 'active': conversation.id === currentConversationId }"
                        @click="selectConversation(conversation.id)">
                        <div class="conversation-content">
                            <div class="conversation-title" x-text="conversation.title"></div>
                            <div class="conversation-date" x-text="formatDate(conversation.lastMessage)"></div>
                        </div>
                        <button @click.stop="deleteConversation(conversation.id)"
                            class="delete-btn">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </template>

                <div x-show="conversations.length === 0" class="empty-state">
                    <svg class="w-12 h-12 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="text-gray-400 text-sm">No conversations yet</p>
                    <p class="text-gray-500 text-xs mt-1">Start a new chat to begin</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <button @click="clearAllConversations()" class="clear-all-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Clear All
            </button>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="main-container">
        <!-- Header -->
        <header class="chat-header">
            <div class="header-content">
                <!-- In your header, change the button to: -->
                <button onclick="window.toggleSidebar()" class="sidebar-toggle-btn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <div class="header-title">
                    <h1 class="text-xl font-semibold">Gemini AI Chat</h1>
                    <p class="text-sm text-gray-400">Powered by a Superior PHP Stack</p>
                </div>

                <div class="header-spacer"></div>
            </div>
        </header>

        <!-- Chat Interface -->
        <div id="app" class="chat-container" x-data="chatController()" x-init="init()" x-cloak>
            <main x-ref="chatbox" class="chat-messages">
                <template x-for="message in messages" :key="message.id">
                    <div class="message-wrapper">
                        <div class="message-avatar" :class="message.sender === 'user' ? 'user-avatar' : 'bot-avatar'">
                            <template x-if="message.sender === 'user'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </template>
                            <template x-if="message.sender === 'bot'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm4.284 15.284l-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21C5.06 16.94 5 16.48 5 16V8c0-1.103.897-2 2-2h10c1.103 0 2 .897 2 2v8c0 .48-.06 1.94-.346 2.284z" />
                                </svg>
                            </template>
                        </div>
                        <div class="message-content" :id="`message-wrapper-${message.id}`">
                            <template x-if="message.sender === 'bot' && isWaitingForResponse && message.id === messages[messages.length - 1].id">
                                <div class="thinking-indicator">
                                    <div class="loading-spinner"></div>
                                    <span>Thinking...</span>
                                </div>
                            </template>
                            <template x-if="!(message.sender === 'bot' && isWaitingForResponse && message.id === messages[messages.length - 1].id)">
                                <div x-html="renderMarkdown(message.text, message.id)"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </main>

            <footer class="chat-input-container">
                <form @submit.prevent="sendMessage()" class="chat-input-form">
                    <div class="input-wrapper">
                        <textarea x-ref="input"
                            @input="autoResize($el)"
                            x-model="prompt"
                            :disabled="isStreaming"
                            @keydown="handleKeydown($event)"
                            placeholder="Type your message..."
                            class="chat-input"
                            rows="1"></textarea>
                        <button type="submit"
                            :disabled="!prompt.trim() || isStreaming"
                            class="send-button">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </footer>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>