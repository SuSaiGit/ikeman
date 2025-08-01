<?php
/**
 * Shared LINE Bot Functions
 * Common functions for webhook handling
 */

/**
 * Verify the request signature from LINE
 */
function verifySignature($body, $signature, $secret) {
    $hash = hash_hmac('sha256', $body, $secret, true);
    $expected = base64_encode($hash);
    return hash_equals($signature, $expected);
}

/**
 * Send reply message to LINE
 */
function replyMessage($replyToken, $message, $accessToken) {
    $url = 'https://api.line.me/v2/bot/message/reply';
    
    $data = [
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['result' => $result, 'httpCode' => $httpCode];
}

/**
 * Send flex message to LINE
 */
function sendFlexMessage($replyToken, $flexMessage, $accessToken) {
    $url = 'https://api.line.me/v2/bot/message/reply';
    
    $data = [
        'replyToken' => $replyToken,
        'messages' => [$flexMessage]
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['result' => $result, 'httpCode' => $httpCode];
}

/**
 * Call Gemini API to generate response
 */
function callGeminiAPI($message, $apiKey, $apiUrl) {
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $message
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1024
        ]
    ];
    
    $headers = [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logMessage("Gemini API CURL Error: " . $error);
        return "Sorry, I'm having trouble connecting to my AI brain right now.";
    }
    
    if ($httpCode !== 200) {
        logMessage("Gemini API HTTP Error: $httpCode - $result");
        return "Sorry, I'm experiencing some technical difficulties.";
    }
    
    $response = json_decode($result, true);
    
    if (!$response || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        logMessage("Gemini API Invalid Response: " . $result);
        return "Sorry, I couldn't generate a proper response.";
    }
    
    return $response['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Log messages for debugging
 * @param string $message The message to log
 * @param string|null $logFile The log file to write to (default: 'webhook.log', or use botName parameter)
 * @param string $botName The bot name to determine log file (default: 'default', kimutaku: 'kimutaku')
 */
function logMessage($message, $logFile = null, $botName = 'default') {
    global $config;
    
    // If no specific log file is provided, determine based on bot name
    if ($logFile === null) {
        if ($botName === 'kimutaku') {
            $logFile = $config['kimutaku_log_file'] ?? 'webhook-kimutaku.log';
        } else {
            $logFile = 'webhook.log';
        }
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Process text messages and generate responses using Gemini AI
 * @param string $message The user's message
 * @param string $userId The user's ID
 * @param string|null $replyToken The reply token for responding
 * @param string $botName The bot name (default: 'default', kimutaku: 'kimutaku')
 * @param string|null $customPrompt Custom prompt for Gemini AI (if null, uses default based on botName)
 */
function processTextMessage($message, $userId, $replyToken = null, $botName = 'default', $customPrompt = null) {
    global $gemini_api_key, $gemini_api_url, $config, $channel_access_token;
    
    $originalMessage = trim($message);
    $lowerMessage = strtolower($originalMessage);
    
    // Handle special commands first (bot-specific responses)
    switch ($lowerMessage) {
        case 'help':
        case 'ヘルプ':
        case 'ช่วยเหลือ':
            return "こんにちは！私はKimutaku Botです。Gemini AIを搭載しています！\n- 質問をしてください\n- 会話を楽しみましょう\n- 様々なトピックでお手伝いします\n- 'time'で現在時刻を表示\n\nメッセージを送ってください！";
            
        case 'time':
        case '時間':
        case 'เวลา':
            return "現在時刻: " . date('Y年m月d日 H:i:s (T)') . " (Kimutaku Bot)";
            
        case 'ping':
            return "Pong! Kimutaku Botはオンラインです！";
            
        case 'kimutaku':
        case 'キムタク':
        case 'kimura':
        case 'キムラ':
            return "はい、私がKimutaku Botです！何かお手伝いできることはありますか？";
    }
    
    // For all other messages, use Gemini AI
    if (empty($gemini_api_key) || $gemini_api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        logMessage("Gemini API key not configured", null, $botName);
        return "申し訳ございませんが、AIの設定がまだ完了していません。管理者にGemini APIキーの設定を依頼してください。";
    }
    
    // Prepare context for Gemini (use custom prompt if provided, otherwise use default based on bot)
    if ($customPrompt !== null) {
        $prompt = $customPrompt . $originalMessage;
    } else {
        $prompt = "You are a helpful and friendly chatbot assistant. Please respond to the following message in a conversational and helpful way. Keep responses concise but informative (max 500 characters for LINE messaging). Message: " . $originalMessage;
    }
    
    logMessage("Calling Gemini API for user $userId with message: $originalMessage", null, $botName);
    
    // Call Gemini API
    $geminiResponse = callGeminiAPI($prompt, $gemini_api_key, $gemini_api_url);
    
    logMessage("Gemini API response: " . $geminiResponse, null, $botName);
    
    // Truncate response if too long for LINE (LINE has a 5000 character limit)
    if (strlen($geminiResponse) > 4900) {
        $geminiResponse = substr($geminiResponse, 0, 4900) . "...";
    }
    
    return $geminiResponse;
}

/**
 * Parse message context from event
 */
function parseMessageContext($event) {
    $sourceType = $event['source']['type'] ?? 'unknown';
    $userId = $event['source']['userId'] ?? 'unknown';
    $groupId = $event['source']['groupId'] ?? null;
    $roomId = $event['source']['roomId'] ?? null;
    
    $context = "private chat";
    if ($sourceType === 'group') {
        $context = "group chat (ID: $groupId)";
    } elseif ($sourceType === 'room') {
        $context = "room chat (ID: $roomId)";
    }
    
    return [
        'sourceType' => $sourceType,
        'userId' => $userId,
        'groupId' => $groupId,
        'roomId' => $roomId,
        'context' => $context
    ];
}

/**
 * Process LINE webhook events
 * @param array $events The events from LINE webhook
 * @param string $channel_access_token The channel access token
 * @param string $botName The bot name (default: 'default', kimutaku: 'kimutaku')
 * @param string|null $customPrompt Custom prompt for Gemini AI (if null, uses default based on botName)
 */
function processWebhookEvents($events, $channel_access_token, $botName = 'default', $customPrompt = null) {
    foreach ($events as $event) {
        logMessage("Processing event: " . json_encode($event), null, $botName);
        
        $eventType = $event['type'];
        $replyToken = $event['replyToken'] ?? '';
        
        switch ($eventType) {
            case 'message':
                $messageType = $event['message']['type'];
                
                if ($messageType === 'text') {
                    $userMessage = $event['message']['text'];
                    $messageContext = parseMessageContext($event);
                    
                    logMessage("Text message from {$messageContext['userId']} in {$messageContext['context']}: $userMessage", null, $botName);
                    
                    // Process the message and generate a response
                    $response = processTextMessage($userMessage, $messageContext['userId'], $replyToken, $botName, $customPrompt);
                    
                    // Reply to the user (only if response is not null)
                    if ($replyToken && $response !== null) {
                        $result = replyMessage($replyToken, $response, $channel_access_token);
                        logMessage("Reply sent to {$messageContext['context']}: " . json_encode($result), null, $botName);
                    }
                    
                } elseif ($messageType === 'image') {
                    logMessage("Image message received", null, $botName);
                    // Handle image messages (bot-specific responses)
                    if ($botName === 'kimutaku') {
                        $response = "こんにちは！画像をありがとうございます！ (Kimutaku Bot)";
                    } else {
                        $response = "Thank you for sending an image!";
                    }
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                    
                } elseif ($messageType === 'audio') {
                    logMessage("Audio message received", null, $botName);
                    // Handle audio messages (bot-specific responses)
                    if ($botName === 'kimutaku') {
                        $response = "音声メッセージをありがとうございます！ (Kimutaku Bot)";
                    } else {
                        $response = "Thank you for sending an audio message!";
                    }
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                    
                } else {
                    logMessage("Unsupported message type: $messageType", null, $botName);
                    if ($botName === 'kimutaku') {
                        $response = "申し訳ございませんが、そのメッセージタイプはサポートしていません。 (Kimutaku Bot)";
                    } else {
                        $response = "Sorry, I don't support this message type yet.";
                    }
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                }
                break;
                
            case 'follow':
                logMessage("New follower", null, $botName);
                if ($botName === 'kimutaku') {
                    $response = "こんにちは！友達に追加していただき、ありがとうございます！私はKimutaku Botです。";
                } else {
                    $response = "Hello! Thank you for adding me as a friend!";
                }
                if ($replyToken) {
                    replyMessage($replyToken, $response, $channel_access_token);
                }
                break;
                
            case 'unfollow':
                logMessage("User unfollowed", null, $botName);
                // Handle unfollow event
                break;
                
            case 'postback':
                $postbackData = $event['postback']['data'];
                logMessage("Postback received: $postbackData", null, $botName);
                // Handle postback data
                break;
                
            default:
                logMessage("Unsupported event type: $eventType", null, $botName);
        }
    }
}
?>
