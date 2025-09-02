<?php
// =======================================================================
// PHP BACKEND API - NOW WITH AUTOMATIC RETRIES
// =======================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'C:\\wamp64\\package\\chatbot\\vendor\\autoload.php';


    set_time_limit(0);
    ini_set('output_buffering', 'Off');
    ini_set('zlib.output_compression', 'Off');
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    flush();

    $json_payload = file_get_contents('php://input');
    $request_data = json_decode($json_payload, true);
    $history = $request_data['history'] ?? [];

    if (empty($history)) {
        echo "event: error\ndata: {\"error\": \"No history provided.\"}\n\n";
        flush();
        exit();
    }

    $key = 'AIzaSyDyd2xW1YJlMMO6E1iWM_gNjx6qiMGSiJk';
    $model = 'gemini-2.5-flash';
    $uri = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':streamGenerateContent?alt=sse';

    $payload = ['contents' => $history];
    $buffer = '';

    try {
        run(function () use ($uri, $payload, $key, &$buffer) {
            return http()
                ->header('x-goog-api-key', $key)
                ->header('Content-Type', 'application/json')
                ->body(json_encode($payload))
                ->retry(3, 1.0)
                ->streamPost($uri, null, function (string $chunk) use (&$buffer) {
                    $buffer .= $chunk;
                    while (($boundary = strpos($buffer, "\n\n")) !== false) {
                        $messageBlock = substr($buffer, 0, $boundary);
                        $buffer = substr($buffer, $boundary + 2);
                        processAndSendJsonChunk($messageBlock);
                    }
                });
        });
        if (!empty($buffer)) {
            processAndSendJsonChunk($buffer);
        }
    } catch (\Throwable $e) {
        $errorPayload = json_encode(['error' => 'API Error after retries: ' . htmlspecialchars($e->getMessage())]);
        echo "event: error\ndata: " . $errorPayload . "\n\n";
        flush();
    }

    echo "event: done\ndata: {}\n\n";
    flush();
    exit();
}

function processAndSendJsonChunk(string $messageBlock)
{
    $lines = explode("\n", $messageBlock);
    foreach ($lines as $line) {
        if (!str_starts_with($line, 'data:')) continue;
        $jsonData = substr($line, 6);
        $data = json_decode($jsonData, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $textChunk = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "data: " . json_encode(['text' => $textChunk]) . "\n\n";
            flush();
        }
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
