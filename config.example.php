<?php
/**
 * LINE Bot Configuration
 * Copy this file to config.php and fill in your actual credentials
 */

return [
    // LINE Bot Channel Access Token
    // Get this from LINE Developers Console > Your Bot > Messaging API > Channel Access Token
    'channel_access_token' => 'YOUR_CHANNEL_ACCESS_TOKEN_HERE',
    
    // LINE Bot Channel Secret
    // Get this from LINE Developers Console > Your Bot > Basic Settings > Channel Secret
    'channel_secret' => 'YOUR_CHANNEL_SECRET_HERE',
    
    // Gemini API Key
    // Get this from Google AI Studio: https://aistudio.google.com/app/apikey
    'gemini_api_key' => 'YOUR_GEMINI_API_KEY_HERE',
    
    // Gemini API URL
    'gemini_api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent',
    
    // Webhook URL (this should point to your webhook.php file)
    'webhook_url' => 'https://yourdomain.com/webhook.php',
    
    // Debug mode (set to false in production)
    'debug' => true,
    
    // Log file path
    'log_file' => 'webhook.log',
    
    // Kimutaku Bot Configuration (duplicate configs with kimutaku_ prefix)
    // LINE Bot Channel Access Token for Kimutaku
    'kimutaku_channel_access_token' => 'YOUR_CHANNEL_ACCESS_TOKEN_HERE',
    
    // LINE Bot Channel Secret for Kimutaku
    'kimutaku_channel_secret' => 'YOUR_CHANNEL_SECRET_HERE',
    
    // Gemini API Key for Kimutaku
    'kimutaku_gemini_api_key' => 'YOUR_GEMINI_API_KEY_HERE',
    
    // Gemini API URL for Kimutaku
    'kimutaku_gemini_api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent',
    
    // Webhook URL for Kimutaku (this should point to your webhook-kimutaku.php file)
    'kimutaku_webhook_url' => 'https://yourdomain.com/webhook-kimutaku.php',
    
    // Debug mode for Kimutaku (set to false in production)
    'kimutaku_debug' => true,
    
    // Log file path for Kimutaku
    'kimutaku_log_file' => 'webhook-kimutaku.log'
];
?>
