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

// LINE Bot configuration
// TODO: Replace with your actual LINE Bot credentials
$channel_access_token = 'l00u1TIZSKGXSAi4occtJt9NOqTUULyyfNIKOjRVgMFyOPZGD35nBKWP85HbrV9DG2NytACYjqkXBCKpBmVmnY9PhDa8HGfqpI2D9cASTZtJmhsTcdeeQy3wRWDINBRn2hJUqGggyrUaBI79nmSJjAdB04t89/1O/w1cDnyilFU=';
$channel_secret = '09cc97b54fab9f912a40f40136fca303';

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
    
    Verify the signature (uncomment when you have your channel secret)
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
                    $userId = $event['source']['userId'] ?? 'unknown';
                    
                    logMessage("Text message from $userId: $userMessage");
                    
                    // Process the message and generate a response
                    $response = processTextMessage($userMessage, $userId);
                    
                    // Reply to the user (uncomment when you have your access token)
                    // if ($replyToken) {
                    //     $result = replyMessage($replyToken, $response, $channel_access_token);
                    //     logMessage("Reply sent: " . json_encode($result));
                    // }
                    
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
 * Process text messages and generate responses
 */
function processTextMessage($message, $userId) {
    $message = strtolower(trim($message));
    
    // Simple response logic - you can expand this
    switch ($message) {
        case 'hello':
        case 'hi':
        case 'สวัสดี':
            return "Hello! How can I help you today?";
            
        case 'help':
        case 'ช่วยเหลือ':
            return "Available commands:\n- hello: Say hello\n- time: Get current time\n- help: Show this help";
            
        case 'time':
        case 'เวลา':
            return "Current time: " . date('Y-m-d H:i:s');
            
        case 'bye':
        case 'goodbye':
        case 'ลาก่อน':
            return "Goodbye! Have a nice day!";
            
        default:
            // Echo the message back with a prefix
            return "You said: " . $message;
    }
}
?>
