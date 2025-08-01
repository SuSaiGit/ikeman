<?php
/**
 * LINE Bot Webhook Client - Kimutaku Version
 * Receives and processes messages from LINE
 * Uses shared functions from line_bot_functions.php with kimutaku_ configs
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Load configuration and shared functions
$config = require_once 'config.php';
require_once 'line_bot_functions.php';

// LINE Bot configuration (using kimutaku_ prefixed configs)
$channel_access_token = $config['kimutaku_channel_access_token'];
$channel_secret = $config['kimutaku_channel_secret'];

// Gemini API configuration (using kimutaku_ prefixed configs)
$gemini_api_key = $config['kimutaku_gemini_api_key'];
$gemini_api_url = $config['kimutaku_gemini_api_url'];

try {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    
    // Log the incoming request with Kimutaku prefix
    logMessage("KIMUTAKU - Received webhook: " . $input, $config['kimutaku_log_file']);
    
    // Get the signature from headers
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
    
    // Verify the signature
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
    
    // Process webhook events using shared function
    // You can customize this for Kimutaku-specific behavior
    processWebhookEventsKimutaku($data['events'], $channel_access_token);
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success', 'version' => 'kimutaku']);
    
} catch (Exception $e) {
    logMessage("KIMUTAKU - Error: " . $e->getMessage(), $config['kimutaku_log_file']);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Kimutaku-specific webhook event processing
 * This function can be customized for different behavior than the main webhook
 */
function processWebhookEventsKimutaku($events, $channel_access_token) {
    global $config;
    
    foreach ($events as $event) {
        logMessage("KIMUTAKU - Processing event: " . json_encode($event), $config['kimutaku_log_file']);
        
        $eventType = $event['type'];
        $replyToken = $event['replyToken'] ?? '';
        
        switch ($eventType) {
            case 'message':
                $messageType = $event['message']['type'];
                
                if ($messageType === 'text') {
                    $userMessage = $event['message']['text'];
                    $messageContext = parseMessageContext($event);
                    
                    logMessage("KIMUTAKU - Text message from {$messageContext['userId']} in {$messageContext['context']}: $userMessage", $config['kimutaku_log_file']);
                    
                    // Process the message with Kimutaku-specific handling
                    $response = processTextMessageKimutaku($userMessage, $messageContext['userId'], $replyToken);
                    
                    // Reply to the user (only if response is not null)
                    if ($replyToken && $response !== null) {
                        $result = replyMessage($replyToken, $response, $channel_access_token);
                        logMessage("KIMUTAKU - Reply sent to {$messageContext['context']}: " . json_encode($result), $config['kimutaku_log_file']);
                    }
                    
                } elseif ($messageType === 'image') {
                    logMessage("KIMUTAKU - Image message received", $config['kimutaku_log_file']);
                    // Handle image messages with Kimutaku-specific response
                    $response = "こんにちは！画像をありがとうございます！ (Kimutaku Bot)";
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                    
                } elseif ($messageType === 'audio') {
                    logMessage("KIMUTAKU - Audio message received", $config['kimutaku_log_file']);
                    // Handle audio messages with Kimutaku-specific response
                    $response = "音声メッセージをありがとうございます！ (Kimutaku Bot)";
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                    
                } else {
                    logMessage("KIMUTAKU - Unsupported message type: $messageType", $config['kimutaku_log_file']);
                    $response = "申し訳ございませんが、そのメッセージタイプはサポートしていません。 (Kimutaku Bot)";
                    if ($replyToken) {
                        replyMessage($replyToken, $response, $channel_access_token);
                    }
                }
                break;
                
            case 'follow':
                logMessage("KIMUTAKU - New follower", $config['kimutaku_log_file']);
                $response = "こんにちは！友達に追加していただき、ありがとうございます！私はKimutaku Botです。";
                if ($replyToken) {
                    replyMessage($replyToken, $response, $channel_access_token);
                }
                break;
                
            case 'unfollow':
                logMessage("KIMUTAKU - User unfollowed", $config['kimutaku_log_file']);
                // Handle unfollow event
                break;
                
            case 'postback':
                $postbackData = $event['postback']['data'];
                logMessage("KIMUTAKU - Postback received: $postbackData", $config['kimutaku_log_file']);
                // Handle postback data
                break;
                
            default:
                logMessage("KIMUTAKU - Unsupported event type: $eventType", $config['kimutaku_log_file']);
        }
    }
}

/**
 * Kimutaku-specific text message processing
 * This function can have different behavior than the main processTextMessage function
 */
function processTextMessageKimutaku($message, $userId, $replyToken = null) {
    global $config, $channel_access_token;
    
    // Use kimutaku_ prefixed configs
    $gemini_api_key = $config['kimutaku_gemini_api_key'];
    $gemini_api_url = $config['kimutaku_gemini_api_url'];
    
    $originalMessage = trim($message);
    $lowerMessage = strtolower($originalMessage);
    
    // Handle special commands first (Kimutaku-specific responses)
    switch ($lowerMessage) {
        case 'help':
        case 'ヘルプ':
        case 'ช่วยเหลือ':
            return "こんにちは！私はKimutaku Botです。Gemini AIを搭載しています！\n- 質問をしてください\n- 会話を楽しみましょう\n- 様々なトピックでお手伝いします\n- 'time'で現在時刻を表示\n- 'pay'で支払いテスト\n\nメッセージを送ってください！";
            
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
            
        case 'pay':
        case 'payment':
        case '支払い':
        case 'ชำระเงิน':
            // Handle payment request using kimutaku configs
            if ($replyToken) {
                return handlePaymentRequestKimutaku($userId, $replyToken);
            } else {
                return "支払い機能にはリプライトークンが必要です。";
            }
    }
    
    // For all other messages, use Gemini AI with Japanese context
    if (empty($gemini_api_key) || $gemini_api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        logMessage("KIMUTAKU - Gemini API key not configured", $config['kimutaku_log_file']);
        return "申し訳ございませんが、AIの設定がまだ完了していません。管理者にGemini APIキーの設定を依頼してください。";
    }
    
    // Prepare context for Gemini with Japanese preference
    $prompt = "You are a helpful and friendly Japanese chatbot assistant named Kimutaku Bot. Please respond to the following message in a conversational and helpful way. Prefer Japanese responses when appropriate, but can respond in English or other languages if the user's message is in that language. Keep responses concise but informative (max 500 characters for LINE messaging). Message: " . $originalMessage;
    
    logMessage("KIMUTAKU - Calling Gemini API for user $userId with message: $originalMessage", $config['kimutaku_log_file']);
    
    // Call Gemini API
    $geminiResponse = callGeminiAPI($prompt, $gemini_api_key, $gemini_api_url);
    
    logMessage("KIMUTAKU - Gemini API response: " . $geminiResponse, $config['kimutaku_log_file']);
    
    // Add Kimutaku signature to response
    $geminiResponse = $geminiResponse . "\n\n- Kimutaku Bot";
    
    // Truncate response if too long for LINE (LINE has a 5000 character limit)
    if (strlen($geminiResponse) > 4900) {
        $geminiResponse = substr($geminiResponse, 0, 4900) . "...\n\n- Kimutaku Bot";
    }
    
    return $geminiResponse;
}

/**
 * Kimutaku-specific payment request handler
 */
function handlePaymentRequestKimutaku($userId, $replyToken) {
    global $config, $channel_access_token;
    
    try {
        // Check if LINE Pay is available
        if (!class_exists('LinePayHandler')) {
            require_once 'line_pay.php';
        }
        
        // Create config array with kimutaku_ prefixed values for LinePayHandler
        $kimutakuConfig = [
            'line_pay_channel_id' => $config['kimutaku_line_pay_channel_id'],
            'line_pay_channel_secret' => $config['kimutaku_line_pay_channel_secret'],
            'line_pay_sandbox' => $config['kimutaku_line_pay_sandbox'],
            'product_image_url' => $config['kimutaku_product_image_url']
        ];
        
        $linePayHandler = new LinePayHandler($kimutakuConfig);
        
        // Sample product details (customize as needed)
        $productName = "Kimutaku Special Product";
        $amount = 150; // Amount in smallest currency unit (e.g., cents for USD, yen for JPY)
        $currency = "JPY";
        $orderId = "kimutaku_order_" . time() . "_" . $userId;
        
        $confirmUrl = str_replace('/webhook-kimutaku.php', '/payment_confirm.php', $config['kimutaku_webhook_url']);
        $cancelUrl = str_replace('/webhook-kimutaku.php', '/payment_cancel.php', $config['kimutaku_webhook_url']);
        
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
            logMessage("KIMUTAKU - Payment request sent to user $userId: " . json_encode($paymentResult), $config['kimutaku_log_file']);
            
            return null; // Don't send text response since we sent flex message
            
        } else {
            logMessage("KIMUTAKU - Payment request failed for user $userId: " . $paymentResult['error'], $config['kimutaku_log_file']);
            return "申し訳ございませんが、現在お支払いリクエストを作成できません。後でもう一度お試しください。";
        }
        
    } catch (Exception $e) {
        logMessage("KIMUTAKU - Payment request error for user $userId: " . $e->getMessage(), $config['kimutaku_log_file']);
        return "申し訳ございませんが、お支払いリクエストの処理中にエラーが発生しました。";
    }
}
?>