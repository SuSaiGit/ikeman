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

// Load configuration and shared functions
$config = require_once 'config.php';
require_once 'line_bot_functions.php';

// LINE Bot configuration
$channel_access_token = $config['channel_access_token'];
$channel_secret = $config['channel_secret'];

// Gemini API configuration
$gemini_api_key = $config['gemini_api_key'];
$gemini_api_url = $config['gemini_api_url'];

try {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    
    // Log the incoming request
    logMessage("Received webhook: " . $input);
    
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
    processWebhookEvents($data['events'], $channel_access_token);
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
