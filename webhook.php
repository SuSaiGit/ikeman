<?php
/**
 * LINE Bot Webhook Client
 * Receives and processes messages from LINE
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Load configuration
$config = require_once 'config.php';
require_once 'line_pay.php';

// LINE Bot configuration
$channel_access_token = $config['channel_access_token'];
$channel_secret = $config['channel_secret'];

// Gemini API configuration
$gemini_api_key = $config['gemini_api_key'];
$gemini_api_url = $config['gemini_api_url'];

/**
 * Verify the request signature from LINE
 */
function verifySignature($body, $signature, $secret) {
    $hash = hash_hmac('sha256', $body, $secret, true);
    $expected = base64_encode($hash);
    return hash_equals($signature, $expected);
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
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents('webhook.log', $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    
    // Log the incoming request
    logMessage("Received webhook: " . $input);
    
    // Get the signature from headers
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
    
    // Verify the signature (uncomment when you have your channel secret)
    if (!verifySignature($input, $signature, $channel_secret)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Decode the JSON data
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Process each event
    foreach ($data['events'] as $event) {
        logMessage("Processing event: " . json_encode($event));
        
        $eventType = $event['type'];
        $replyToken = $event['replyToken'] ?? '';
        
        switch ($eventType) {
            case 'message':
                $messageType = $event['message']['type'];
                
                if ($messageType === 'text') {
                    $userMessage = $event['message']['text'];
                    $sourceType = $event['source']['type'] ?? 'unknown';
                    $userId = $event['source']['userId'] ?? 'unknown';
                    $groupId = $event['source']['groupId'] ?? null;
                    $roomId = $event['source']['roomId'] ?? null;
                    
                    // Log message with context
                    $context = "private chat";
                    if ($sourceType === 'group') {
                        $context = "group chat (ID: $groupId)";
                    } elseif ($sourceType === 'room') {
                        $context = "room chat (ID: $roomId)";
                    }
                    
                    logMessage("Text message from $userId in $context: $userMessage");
                    
                    // Process the message and generate a response
                    $response = processTextMessage($userMessage, $userId, $replyToken);
                    
                    // Reply to the user (only if response is not null)
                    if ($replyToken && $response !== null) {
                        $result = replyMessage($replyToken, $response, $channel_access_token);
                        logMessage("Reply sent to $context: " . json_encode($result));
                    }
                    
                } elseif ($messageType === 'image') {
                    logMessage("Image message received");
                    // Handle image messages
                    $response = "Thank you for sending an image!";
                    
                } elseif ($messageType === 'audio') {
                    logMessage("Audio message received");
                    // Handle audio messages
                    $response = "Thank you for sending an audio message!";
                    
                } else {
                    logMessage("Unsupported message type: $messageType");
                    $response = "Sorry, I don't support this message type yet.";
                }
                break;
                
            case 'follow':
                logMessage("New follower");
                $response = "Hello! Thank you for adding me as a friend!";
                // Handle follow event
                break;
                
            case 'unfollow':
                logMessage("User unfollowed");
                // Handle unfollow event
                break;
                
            case 'postback':
                $postbackData = $event['postback']['data'];
                logMessage("Postback received: $postbackData");
                // Handle postback data
                break;
                
            default:
                logMessage("Unsupported event type: $eventType");
        }
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Process text messages and generate responses using Gemini AI
 */
function processTextMessage($message, $userId, $replyToken) {
    global $gemini_api_key, $gemini_api_url, $config, $channel_access_token;
    
    $originalMessage = trim($message);
    $lowerMessage = strtolower($originalMessage);
    
    // Handle special commands first
    switch ($lowerMessage) {
        case 'help':
        case 'ช่วยเหลือ':
            return "I'm an AI assistant powered by Gemini! You can:\n- Ask me questions\n- Have conversations\n- Get help with various topics\n- Type 'time' for current time\n- Type 'pay' to test payment\n\nJust send me any message and I'll respond!";
            
        case 'time':
        case 'เวลา':
            return "Current time: " . date('Y-m-d H:i:s (T)');
            
        case 'ping':
            return "Pong! I'm online and ready to chat.";
            
        case 'pay':
        case 'payment':
        case 'ชำระเงิน':
            // Handle payment request
            return handlePaymentRequest($userId, $replyToken);
    }
    
    // For all other messages, use Gemini AI
    if (empty($gemini_api_key) || $gemini_api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        logMessage("Gemini API key not configured");
        return "Sorry, my AI brain isn't configured yet. Please ask the admin to set up the Gemini API key.";
    }
    
    // Prepare context for Gemini
    $prompt = "You are a helpful and friendly chatbot assistant. Please respond to the following message in a conversational and helpful way. Keep responses concise but informative (max 500 characters for LINE messaging). Message: " . $originalMessage;
    
    logMessage("Calling Gemini API for user $userId with message: $originalMessage");
    
    // Call Gemini API
    $geminiResponse = callGeminiAPI($prompt, $gemini_api_key, $gemini_api_url);
    
    logMessage("Gemini API response: " . $geminiResponse);
    
    // Truncate response if too long for LINE (LINE has a 5000 character limit)
    if (strlen($geminiResponse) > 4900) {
        $geminiResponse = substr($geminiResponse, 0, 4900) . "...";
    }
    
    return $geminiResponse;
}

/**
 * Handle payment request
 */
function handlePaymentRequest($userId, $replyToken) {
    global $config, $channel_access_token;
    
    try {
        $linePayHandler = new LinePayHandler($config);
        
        // Sample product details (customize as needed)
        $productName = "Sample Product";
        $amount = 100; // Amount in smallest currency unit (e.g., cents for USD, yen for JPY)
        $currency = "JPY";
        $orderId = "order_" . time() . "_" . $userId;
        
        $confirmUrl = $config['webhook_url'] . '/../payment_confirm.php';
        $cancelUrl = $config['webhook_url'] . '/../payment_cancel.php';
        
        // Request payment
        $paymentResult = $linePayHandler->requestPayment(
            $amount,
            $currency,
            $orderId,
            $productName,
            $confirmUrl,
            $cancelUrl
        );
        
        if ($paymentResult['success']) {
            // Store payment data for confirmation
            session_start();
            $_SESSION['payment_' . $paymentResult['transactionId']] = [
                'amount' => $amount,
                'currency' => $currency,
                'orderId' => $orderId,
                'userId' => $userId
            ];
            
            // Create and send flex message
            $flexMessage = $linePayHandler->createPaymentFlexMessage(
                $productName,
                $amount,
                $currency,
                $paymentResult['paymentUrl']
            );
            
            sendFlexMessage($replyToken, $flexMessage, $channel_access_token);
            logMessage("Payment request sent to user $userId: " . json_encode($paymentResult));
            
            return null; // Don't send text response since we sent flex message
            
        } else {
            logMessage("Payment request failed for user $userId: " . $paymentResult['error']);
            return "Sorry, I couldn't create a payment request right now. Please try again later.";
        }
        
    } catch (Exception $e) {
        logMessage("Payment request error for user $userId: " . $e->getMessage());
        return "Sorry, there was an error processing your payment request.";
    }
}
?>
