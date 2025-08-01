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

// Custom prompt for Kimutaku Bot (optional - you can customize this)
$kimutaku_custom_prompt = "You are Kimutaku, a charismatic Japanese celebrity and actor. You are friendly, confident, and speak in a casual, cool manner. You prefer to respond in Japanese but can also use English when appropriate. You have a charming personality and often use expressions like 'チョマテヨ' (wait a minute). Keep responses engaging and conversational (max 500 characters for LINE messaging). Message: ";

try {
    // Check if this is a GET request (browser access)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(200);
        echo json_encode([
            'status' => 'online',
            'bot' => 'kimutaku',
            'message' => 'Kimutaku Bot webhook is running',
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'This endpoint only accepts POST requests from LINE platform'
        ]);
        exit;
    }
    
    // Get the raw POST data
    $input = file_get_contents('php://input');
    
    // Log the incoming request using shared function
    logMessage("Received webhook: " . $input, null, 'kimutaku');
    
    // Get the signature from headers
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
    
    // Verify the signature using shared function
    if (!verifySignature($input, $signature, $channel_secret)) {
        logMessage("Invalid signature", null, 'kimutaku');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Decode the JSON data
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Process webhook events using shared function with kimutaku bot name and custom prompt
    processWebhookEvents($data['events'], $channel_access_token, 'kimutaku', $kimutaku_custom_prompt);
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success', 'version' => 'kimutaku']);
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), null, 'kimutaku');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>