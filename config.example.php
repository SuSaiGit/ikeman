<?php
/**
 * LINE Bot Configuration
 * Copy this file to config.php and fill in your actual credentials
 */

return [
    // LINE Bot Channel Access Token
    // Get this from LINE Developers Console > Your Bot > Messaging API > Channel Access Token
    'channel_access_token' => 'l00u1TIZSKGXSAi4occtJt9NOqTUULyyfNIKOjRVgMFyOPZGD35nBKWP85HbrV9DG2NytACYjqkXBCKpBmVmnY9PhDa8HGfqpI2D9cASTZtJmhsTcdeeQy3wRWDINBRn2hJUqGggyrUaBI79nmSJjAdB04t89/1O/w1cDnyilFU=',
    
    // LINE Bot Channel Secret
    // Get this from LINE Developers Console > Your Bot > Basic Settings > Channel Secret
    'channel_secret' => '09cc97b54fab9f912a40f40136fca303',
    
    // Webhook URL (this should point to your webhook.php file)
    'webhook_url' => 'https://ikeman.zhuge.jp/webhook.php',
    
    // Debug mode (set to false in production)
    'debug' => true,
    
    // Log file path
    'log_file' => 'webhook.log'
];
?>
