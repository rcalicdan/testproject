<?php
// =======================================================================
// PHP BACKEND (POWERED BY FIBERASYNC)
// =======================================================================
if (isset($_GET['prompt']) && !empty($_GET['prompt'])) {
    require __DIR__ . '/vendor/autoload.php';

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    $prompt = $_GET['prompt'];
    $key = 'AIzaSyDyd2xW1YJlMMO6E1iWM_gNjx6qiMGSiJk';
    $model = 'gemini-1.5-flash';
    $uri = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':streamGenerateContent?alt=sse';
    $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
    $buffer = '';

    try {
        run(function () use ($uri, $payload, $key, &$buffer) {
            return http()
                ->header('x-goog-api-key', $key)
                ->header('Content-Type', 'application/json')
                ->header('Accept', 'text/event-stream')
                ->body(json_encode($payload))
                ->streamPost($uri, null, function (string $chunk) use (&$buffer) {
                    $buffer .= $chunk;
                    while (($boundary = strpos($buffer, "\n\n")) !== false) {
                        $messageBlock = substr($buffer, 0, $boundary);
                        $buffer = substr($buffer, $boundary + 2);
                        processSseMessage($messageBlock);
                    }
                });
        });

        if (!empty($buffer)) { processSseMessage($buffer); }
        echo "event: done\ndata: {}\n\n";
        flush();
    } catch (\Throwable $e) {
        echo "event: error\ndata: {\"error\": \"API Error: " . htmlspecialchars($e->getMessage()) . "\"}\n\n";
        flush();
    }
    exit();
}

function processSseMessage(string $messageBlock) {
    $lines = explode("\n", $messageBlock);
    foreach ($lines as $line) {
        $line = rtrim($line, "\r");
        if (!str_starts_with($line, 'data:')) continue;
        $jsonData = substr($line, 6);
        if (trim($jsonData) === '[DONE]') continue;
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['candidates'][0]['content']['parts'][0]['text'])) continue;
        $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
        echo "data: " . json_encode(['text' => $responseText]) . "\n\n";
        flush();
    }
}
?>
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #111827; }
    ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
    [x-cloak] { display: none !important; }
    .prose p { margin: 1em 0; }
    .prose h1, .prose h2, .prose h3 { margin: 1.5em 0 0.5em; font-weight: 600; }
    .prose ul, .prose ol { padding-left: 1.5em; }
    .prose li > p { margin: 0.25em 0; }
    .prose blockquote { border-left: 4px solid #4b5563; padding-left: 1em; margin-left: 0; font-style: italic; color: #9ca3af; }
    .prose pre { background-color: #1f2937; color: #d1d5db; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.9em; }
    .prose code:not(pre > code) { background-color: #374151; color: #e5e7eb; padding: 0.2em 0.4em; border-radius: 0.25rem; font-size: 0.9em; }
    .prose table { width: 100%; border-collapse: collapse; margin: 1.5em 0; }
    .prose th, .prose td { border: 1px solid #4b5563; padding: 0.5em 1em; text-align: left;}
    .prose th { background-color: #374151; font-weight: 600; }
    .prose tr:nth-child(even) { background-color: #1f2937; }
    .loading-spinner { border: 2px solid #374151; border-top: 2px solid #60a5fa; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .cursor-blink { animation: blink 1s step-end infinite; }
    @keyframes blink { 50% { opacity: 0; } }
    .fade-in { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>

<body class="bg-gray-900 text-gray-200 flex flex-col h-screen">
  <header class="bg-gray-900 shadow-md p-4">
    <h1 class="text-xl font-semibold text-center">Gemini AI Chat</h1>
    <p class="text-md text-gray-400 text-center">Powered by Alpine.js & FiberAsync</p>
  </header>
  
  <div id="app" class="flex-1 flex flex-col max-w-4xl w-full mx-auto" x-data="chatApp()" x-init="init()" x-cloak>
    <main x-ref="chatbox" class="flex-1 px-4 pb-4 overflow-y-auto">
      
      <template x-for="message in messages" :key="message.id">
        <div class="flex items-start gap-4 my-6 fade-in">
          <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white" :class="message.sender === 'user' ? 'bg-blue-600' : 'bg-gray-700'">
            <template x-if="message.sender === 'user'">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
            </template>
            <template x-if="message.sender === 'bot'">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm4.284 15.284l-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21C5.06 16.94 5 16.48 5 16V8c0-1.103.897-2 2-2h10c1.103 0 2 .897 2 2v8c0 .48-.06 1.94-.346 2.284z" /><path d="M10.707 10.707L10 10l-.707.707-1.061-1.061 1.061-1.061L10 9l.707.707 1.061 1.061-1.061 1.061zM14 12l-.707.707-1.061-1.061L13 11l.707-.707.232-.232.829.829L14 12z" /></svg>
            </template>
          </div>
          <div class="prose prose-invert max-w-none flex-1" x-html="renderMarkdown(message.text, message.id)"></div>
        </div>
      </template>

      <template x-if="isConnecting">
        <div class="flex items-start gap-4 my-6 fade-in">
          <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white bg-gray-700">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm4.284 15.284l-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21-1.21-1.21c-.39-.39-1.023-.39-1.414 0l-1.21 1.21C5.06 16.94 5 16.48 5 16V8c0-1.103.897-2 2-2h10c1.103 0 2 .897 2 2v8c0 .48-.06 1.94-.346 2.284z" /><path d="M10.707 10.707L10 10l-.707.707-1.061-1.061 1.061-1.061L10 9l.707.707 1.061 1.061-1.061 1.061zM14 12l-.707.707-1.061-1.061L13 11l.707-.707.232-.232.829.829L14 12z" /></svg>
          </div>
          <div class="flex items-center gap-2 pt-1">
            <div class="loading-spinner"></div>
            <span class="text-gray-400">Gemini is thinking...</span>
          </div>
        </div>
      </template>
    </main>

    <footer class="p-4 pt-2 sticky bottom-0 bg-gray-900">
      <div class="w-full bg-gray-800 rounded-lg p-2 flex items-end">
        <textarea 
          x-ref="input"
          @input="autoResize($el)"
          x-model="prompt" 
          :disabled="isConnecting || isStreaming" 
          placeholder="Type your message..." 
          class="flex-1 w-full bg-transparent focus:outline-none resize-none" 
          rows="1" 
          style="max-height: 200px;"
          @keydown="handleKeydown($event)"
        ></textarea>
        <button 
          @click.prevent="sendMessage()"
          :disabled="!prompt.trim() || isConnecting || isStreaming" 
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed rounded-md p-2 ml-2 transition-colors flex-shrink-0"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg>
        </button>
      </div>
    </footer>
  </div>

  <script>
    function chatApp() {
      return {
        prompt: '',
        isConnecting: false,
        isStreaming: false,
        messages: [],
        currentBotMessageId: null,
        eventSource: null,
        textQueue: [],
        renderingInterval: null,
        typingSpeed: 20,

        init() {
            marked.setOptions({
                highlight: (code, lang) => {
                    const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                    return hljs.highlight(code, { language }).value;
                },
                gfm: true,
                breaks: true,
            });
        },
        
        handleKeydown(event) {
          if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
          }
        },

        sendMessage() {
          if (this.prompt.trim() === '' || this.isConnecting || this.isStreaming) return;
          const userPrompt = this.prompt.trim();
          this.messages.push({ id: Date.now(), text: userPrompt, sender: 'user' });
          this.isConnecting = true;
          this.prompt = '';
          this.$nextTick(() => { this.$refs.input.style.height = 'auto'; });
          this.scrollToBottom();
          this.streamBotResponse(userPrompt);
        },

        streamBotResponse(userPrompt) {
          this.eventSource = new EventSource(`?prompt=${encodeURIComponent(userPrompt)}`);
          
          this.eventSource.onopen = () => {
            this.isConnecting = false;
            this.isStreaming = true;
            const messageId = Date.now() + 1;
            this.currentBotMessageId = messageId;
            this.messages.push({ id: messageId, text: '', sender: 'bot' });
            this.startRendering();
          };

          this.eventSource.onmessage = (event) => {
            try {
              const data = JSON.parse(event.data);
              if (data.text) {
                const words = data.text.split(/(\s+|```|`|\*\*|\*|_|\n|\|)/);
                this.textQueue.push(...words.filter(w => w));
              }
            } catch (e) { /* ignore */ }
          };

          this.eventSource.onerror = (error) => {
            const errorMessage = '\n\n**Sorry, an error occurred.**'.split(/(\s+)/);
            this.textQueue.push(...errorMessage);
            this.closeStream();
          };

          this.eventSource.addEventListener('done', () => {
            this.closeStream();
          });
        },

        startRendering() {
          if (this.renderingInterval) return;
          this.renderingInterval = setInterval(() => {
            if (this.textQueue.length > 0) {
              const chunk = this.textQueue.splice(0, 2).join(''); 
              this.updateBotMessage(this.currentBotMessageId, chunk, true);
              this.scrollToBottom();
            } else if (!this.isStreaming && this.textQueue.length === 0) {
              this.stopRendering();
            }
          }, this.typingSpeed);
        },

        stopRendering() {
          clearInterval(this.renderingInterval);
          this.renderingInterval = null;
          this.$nextTick(() => {
            document.querySelectorAll('pre code').forEach(hljs.highlightElement);
          });
          this.currentBotMessageId = null;
          this.scrollToBottom();
        },

        updateBotMessage(messageId, text, append = false) {
          const messageIndex = this.messages.findIndex(m => m.id === messageId);
          if (messageIndex !== -1) {
            if (append) this.messages[messageIndex].text += text;
            else this.messages[messageIndex].text = text;
          }
        },

        closeStream() {
          if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
          }
          this.isStreaming = false;
          this.isConnecting = false;
        },

        scrollToBottom() {
          this.$nextTick(() => {
            this.$refs.chatbox.scrollTop = this.$refs.chatbox.scrollHeight;
          });
        },

        renderMarkdown(text, messageId) {
          const parsedText = marked.parse(text);
          if ((this.renderingInterval || this.isStreaming) && this.currentBotMessageId === messageId) {
            return parsedText + '<span class="inline-block w-2 h-5 bg-gray-400 cursor-blink ml-1"></span>';
          }
          return parsedText;
        },

        autoResize(el) {
          el.style.height = 'auto';
          el.style.height = (el.scrollHeight) + 'px';
        }
      };
    }
  </script>
</body>
</html>