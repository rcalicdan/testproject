window.fetchEventSource = window.fetchEventSource || function (url, options) {
    return fetch(url, {
        method: options.method || 'GET',
        headers: options.headers || {},
        body: options.body || null
    }).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        function processStream() {
            return reader.read().then(({ done, value }) => {
                if (done) {
                    if (options.onclose) options.onclose();
                    return;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.substring(6);
                        if (data.trim() && options.onmessage) {
                            options.onmessage({ data });
                        }
                    } else if (line.startsWith('event: error')) {
                        if (options.onerror) {
                            options.onerror(new Error('Server error'));
                        }
                    } else if (line.startsWith('event: done')) {
                        if (options.onclose) options.onclose();
                        return;
                    }
                }

                return processStream();
            });
        }

        return processStream();
    }).catch(error => {
        if (options.onerror) {
            options.onerror(error);
        }
    });
};

// Conversation Management
function conversationManager() {
    const storageKey = 'gemini-conversations';

    return {
        getAllConversations() {
            try {
                const data = localStorage.getItem(storageKey);
                return data ? JSON.parse(data) : {};
            } catch (error) {
                console.error('Error loading conversations:', error);
                return {};
            }
        },

        saveConversation(id, messages, title = null) {
            try {
                const conversations = this.getAllConversations();
                const conversationTitle = title || this.generateTitle(messages);

                conversations[id] = {
                    id,
                    title: conversationTitle,
                    messages,
                    lastMessage: new Date().toISOString(),
                    createdAt: conversations[id]?.createdAt || new Date().toISOString()
                };

                localStorage.setItem(storageKey, JSON.stringify(conversations));
                return conversations[id];
            } catch (error) {
                console.error('Error saving conversation:', error);
                return null;
            }
        },

        getConversation(id) {
            const conversations = this.getAllConversations();
            return conversations[id] || null;
        },

        deleteConversation(id) {
            try {
                const conversations = this.getAllConversations();
                delete conversations[id];
                localStorage.setItem(storageKey, JSON.stringify(conversations));
                return true;
            } catch (error) {
                console.error('Error deleting conversation:', error);
                return false;
            }
        },

        clearAllConversations() {
            try {
                localStorage.removeItem(storageKey);
                return true;
            } catch (error) {
                console.error('Error clearing conversations:', error);
                return false;
            }
        },

        generateTitle(messages) {
            const firstUserMessage = messages.find(msg => msg.sender === 'user');
            if (firstUserMessage) {
                return firstUserMessage.text.substring(0, 50) + (firstUserMessage.text.length > 50 ? '...' : '');
            }
            return 'New Chat';
        },

        generateId() {
            return Date.now().toString();
        }
    };
}

// Sidebar Controller
function sidebarController() {
    const manager = conversationManager();

    return {
        conversations: [],
        currentConversationId: null,
        sidebarVisible: window.innerWidth > 1024,
        isMobile: window.innerWidth <= 1024,

        init() {
            this.loadConversations();
            this.setupEventListeners();
            this.updateSidebarVisibility();
        },

        loadConversations() {
            const allConversations = manager.getAllConversations();
            this.conversations = Object.values(allConversations)
                .sort((a, b) => new Date(b.lastMessage) - new Date(a.lastMessage));
        },

        createNewChat() {
            const newId = manager.generateId();
            this.currentConversationId = newId;

            window.dispatchEvent(new CustomEvent('newChat', { detail: { id: newId } }));

            if (this.isMobile) {
                this.hideSidebar();
            }
        },

        selectConversation(id) {
            this.currentConversationId = id;
            const conversation = manager.getConversation(id);

            if (conversation) {
                window.dispatchEvent(new CustomEvent('loadConversation', {
                    detail: { id, messages: conversation.messages }
                }));
            }

            if (this.isMobile) {
                this.hideSidebar();
            }
        },

        toggleSidebar() {
            this.sidebarVisible = !this.sidebarVisible;
            this.updateSidebarVisibility();
        },

        showSidebar() {
            this.sidebarVisible = true;
            this.updateSidebarVisibility();
        },

        hideSidebar() {
            this.sidebarVisible = false;
            this.updateSidebarVisibility();
        },

        updateSidebarVisibility() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            if (this.sidebarVisible) {
                sidebar.classList.add('show');
                if (this.isMobile && !overlay) {
                    const newOverlay = document.createElement('div');
                    newOverlay.className = 'sidebar-overlay';
                    newOverlay.onclick = () => this.hideSidebar();
                    document.body.appendChild(newOverlay);
                }
            } else {
                sidebar.classList.remove('show');
                if (overlay) {
                    overlay.remove();
                }
            }
        },

        handleResize() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 1024;

            if (wasMobile && !this.isMobile) {
                this.showSidebar();
            } else if (!wasMobile && this.isMobile) {
                this.hideSidebar();
            }
        },

        deleteConversation(id) {
            if (confirm('Are you sure you want to delete this conversation?')) {
                manager.deleteConversation(id);
                this.loadConversations();

                if (this.currentConversationId === id) {
                    this.createNewChat();
                }
            }
        },

        clearAllConversations() {
            if (confirm('Are you sure you want to delete all conversations?')) {
                manager.clearAllConversations();
                this.loadConversations();
                this.createNewChat();
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));

            if (days === 0) return 'Today';
            if (days === 1) return 'Yesterday';
            if (days < 7) return `${days} days ago`;

            return date.toLocaleDateString();
        },

        setupEventListeners() {
            window.addEventListener('conversationUpdated', (event) => {
                const { id, messages } = event.detail;
                manager.saveConversation(id, messages);
                this.loadConversations();
            });

            window.addEventListener('resize', () => {
                this.handleResize();
            });
        }
    };
}

function chatController() {
    const manager = conversationManager();

    return {
        prompt: '',
        isStreaming: false,
        isWaitingForResponse: false,
        messages: [],
        textQueue: [],
        renderingInterval: null,
        typingSpeed: 25,
        currentConversationId: null,

        init() {
            marked.setOptions({
                highlight: function (code, lang) {
                    const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                    return hljs.highlight(code, { language }).value;
                },
                gfm: true,
                breaks: true,
            });

            const renderer = new marked.Renderer();
            renderer.table = function(header, body) {
                return `
                    <div class="table-wrapper">
                        <table>
                            <thead>${header}</thead>
                            <tbody>${body}</tbody>
                        </table>
                    </div>
                `;
            };
            marked.use({ renderer });


            this.setupEventListeners();
            this.createNewConversation();
        },

        setupEventListeners() {
            window.addEventListener('newChat', (event) => {
                this.currentConversationId = event.detail.id;
                this.messages = [];
            });

            window.addEventListener('loadConversation', (event) => {
                this.currentConversationId = event.detail.id;
                this.messages = event.detail.messages;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.updateEnhancedFormatting(); 
                });
            });
        },
        
        createNewConversation() {
            this.currentConversationId = manager.generateId();
            this.messages = [];
        },

        saveCurrentConversation() {
            if (this.currentConversationId && this.messages.length > 0) {
                manager.saveConversation(this.currentConversationId, this.messages);

                window.dispatchEvent(new CustomEvent('conversationUpdated', {
                    detail: { id: this.currentConversationId, messages: this.messages }
                }));
            }
        },

        handleKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.sendMessage();
            }
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight) + 'px';
        },

        sendMessage() {
            if (this.prompt.trim() === '' || this.isStreaming) return;

            this.messages.push({
                id: Date.now(),
                sender: 'user',
                text: this.prompt.trim()
            });

            this.saveCurrentConversation();
            this.isStreaming = true;
            this.isWaitingForResponse = true;
            this.prompt = '';

            this.$nextTick(() => {
                this.$refs.input.style.height = 'auto';
                this.scrollToBottom();
            });

            this.streamBotResponse();
        },

        streamBotResponse() {
            const botMessageId = Date.now() + 1;
            this.messages.push({
                id: botMessageId,
                sender: 'bot',
                text: ''
            });

            this.startRendering(botMessageId);
            
            // ... (The rest of streamBotResponse remains unchanged)
            const historyForApi = this.messages.map(msg => ({
                role: msg.sender === 'user' ? 'user' : 'model',
                parts: [{ text: msg.text }]
            }));

            fetchEventSource('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ history: historyForApi }),
                onmessage: (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        if (data.text) {
                            if (this.isWaitingForResponse) this.isWaitingForResponse = false;
                            const chunks = data.text.split(/(\s+|```|`|\*\*|\*|_|\n|\|)/).filter(chunk => chunk);
                            this.textQueue.push(...chunks);
                        }
                    } catch (e) { console.error('Error parsing message:', e); }
                },
                onclose: () => {
                    this.isStreaming = false;
                    this.isWaitingForResponse = false;
                },
                onerror: (err) => {
                    console.error('Stream error:', err);
                    this.textQueue.push(...'\n\n**Sorry, an error occurred.**'.split(/(\s+)/));
                    this.isStreaming = false;
                    this.isWaitingForResponse = false;
                }
            });
        },

        startRendering(targetId) {
            if (this.renderingInterval) clearInterval(this.renderingInterval);
            let lastUpdateTime = Date.now();

            this.renderingInterval = setInterval(() => {
                const messageIndex = this.messages.findIndex(m => m.id === targetId);
                if (messageIndex === -1) {
                    clearInterval(this.renderingInterval);
                    return;
                }

                if (this.textQueue.length > 0) {
                    const chunksToProcess = Math.min(2, this.textQueue.length);
                    const chunks = this.textQueue.splice(0, chunksToProcess);
                    this.messages[messageIndex].text += chunks.join('');
                    
                    const now = Date.now();
                    if (now - lastUpdateTime > 100) {
                        this.scrollToBottom();
                        lastUpdateTime = now;
                    }
                } else if (!this.isStreaming) {
                    clearInterval(this.renderingInterval);
                    this.renderingInterval = null;
                    this.saveCurrentConversation();
                    this.$nextTick(() => {
                        this.updateEnhancedFormatting(); // Replaced highlight calls
                        this.scrollToBottom();
                    });
                }
            }, this.typingSpeed);
        },
        
        // --- (scrollToBottom, renderMarkdown, escapeHtml remain the same) ---
        
        scrollToBottom() {
            this.$nextTick(() => {
                const chatbox = this.$refs.chatbox;
                if (chatbox && chatbox.scrollHeight - chatbox.scrollTop - chatbox.clientHeight < 200) {
                    chatbox.scrollTop = chatbox.scrollHeight;
                }
            });
        },
        
        renderMarkdown(text, id) {
             const isCurrentlyStreaming = this.isStreaming && this.messages.length > 0 && this.messages[this.messages.length - 1].id === id;
             try {
                let content = marked.parse(text);
                if (isCurrentlyStreaming) {
                    content = content.replace(/<span class="cursor-blink">\|<\/span>/g, '');
                    if (content.endsWith('</p>')) {
                        content = content.slice(0, -4) + '<span class="cursor-blink">|</span></p>';
                    } else if (content.endsWith('</code></pre>')) {
                        content += '<span class="cursor-blink">|</span>';
                    } else {
                        content += '<span class="cursor-blink">|</span>';
                    }
                }
                return content;
             } catch (error) {
                console.error('Markdown parsing error:', error);
                return this.escapeHtml(text);
             }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // NEW: This function handles both highlighting and adding copy buttons
        updateEnhancedFormatting() {
            this.$nextTick(() => {
                const chatbox = this.$refs.chatbox;
                if (!chatbox) return;

                // 1. Highlight all code blocks that aren't highlighted yet
                const codeBlocks = chatbox.querySelectorAll('pre code:not(.hljs)');
                codeBlocks.forEach(block => {
                    hljs.highlightElement(block);
                });

                // 2. Add copy buttons to all code blocks that don't have one
                const preElements = chatbox.querySelectorAll('pre');
                preElements.forEach(pre => {
                    if (pre.querySelector('.copy-code-btn')) {
                        return; // Button already exists
                    }
                    
                    const button = document.createElement('button');
                    button.className = 'copy-code-btn';
                    button.textContent = 'Copy';
                    
                    button.onclick = () => {
                        const code = pre.querySelector('code').innerText;
                        navigator.clipboard.writeText(code).then(() => {
                            button.textContent = 'Copied!';
                            button.classList.add('copied');
                            setTimeout(() => {
                                button.textContent = 'Copy';
                                button.classList.remove('copied');
                            }, 2000);
                        }).catch(err => {
                            button.textContent = 'Error';
                            console.error('Failed to copy text: ', err);
                        });
                    };
                    
                    pre.appendChild(button);
                });
            });
        }
    };
}
// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const isVisible = sidebar.classList.contains('show');

    if (isVisible) {
        sidebar.classList.remove('show');
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.remove();
        }
    } else {
        sidebar.classList.add('show');
        if (window.innerWidth <= 1024) {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.onclick = () => toggleSidebar();
            document.body.appendChild(overlay);
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.remove('show');
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    });
});