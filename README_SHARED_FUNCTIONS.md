# Shared LINE Bot Functions Architecture

## Overview
The LINE Bot functions have been refactored to use a shared library approach, allowing multiple webhook endpoints to reuse common functionality while maintaining their own customizations.

## File Structure

### Core Files:
- **`line_bot_functions.php`** - Shared functions library
- **`webhook.php`** - Main webhook endpoint
- **`webhook-kimura.php`** - Kimura-specific webhook endpoint
- **`config.php`** - Shared configuration
- **`line_pay.php`** - LINE Pay integration

### Shared Functions in `line_bot_functions.php`:

#### 1. **Core LINE API Functions**
- `verifySignature($body, $signature, $secret)` - Verify LINE webhook signatures
- `replyMessage($replyToken, $message, $accessToken)` - Send text messages
- `sendFlexMessage($replyToken, $flexMessage, $accessToken)` - Send flex messages

#### 2. **AI Integration**
- `callGeminiAPI($message, $apiKey, $apiUrl)` - Call Gemini AI API
- `processTextMessage($message, $userId, $replyToken)` - Process text with AI

#### 3. **Payment Integration**
- `handlePaymentRequest($userId, $replyToken)` - Handle payment requests

#### 4. **Utility Functions**
- `logMessage($message, $logFile)` - Logging with custom log files
- `parseMessageContext($event)` - Parse message source context
- `processWebhookEvents($events, $channel_access_token)` - Generic event processing

## Webhook Implementations

### Main Webhook (`webhook.php`)
```php
// Uses shared functions directly
processWebhookEvents($data['events'], $channel_access_token);
```

**Features:**
- Standard AI responses in English/Thai
- Payment integration
- Standard logging to `webhook.log`

### Kimura Webhook (`webhook-kimura.php`)
```php
// Custom event processing
processWebhookEventsKimura($data['events'], $channel_access_token);
```

**Features:**
- Japanese-focused responses
- Custom Kimura Bot personality
- Separate logging to `webhook-kimura.log`
- Japanese command support

## Customization Examples

### Different AI Personalities
```php
// In webhook-kimura.php
$prompt = "You are a helpful Japanese assistant named Kimura Bot...";

// In webhook.php  
$prompt = "You are a helpful and friendly chatbot assistant...";
```

### Custom Commands
```php
// Kimura-specific commands
case 'kimura':
case 'キムラ':
    return "はい、私がKimura Botです！";
```

### Different Log Files
```php
// Main webhook
logMessage("Standard log message"); // → webhook.log

// Kimura webhook  
logMessage("KIMURA - Custom log", 'webhook-kimura.log'); // → webhook-kimura.log
```

## Benefits

### 1. **Code Reusability**
- Common functions shared between webhooks
- Reduces code duplication
- Easier maintenance

### 2. **Customization**
- Each webhook can have unique personality
- Different response styles
- Separate logging and monitoring

### 3. **Scalability**
- Easy to add new webhook variants
- Shared core functionality
- Independent customization

### 4. **Maintainability**
- Bug fixes in shared functions benefit all webhooks
- Central configuration management
- Consistent API handling

## Adding New Webhook Variants

### Step 1: Create New Webhook File
```php
<?php
require_once 'line_bot_functions.php';

// Custom event processing function
function processWebhookEventsCustom($events, $channel_access_token) {
    // Your custom logic here
}
```

### Step 2: Customize Message Processing
```php
function processTextMessageCustom($message, $userId, $replyToken) {
    // Your custom AI prompts and responses
}
```

### Step 3: Use Unique Log File
```php
logMessage("CUSTOM - Log message", 'webhook-custom.log');
```

## Configuration

All webhooks share the same configuration from `config.php`:
```php
$config = require_once 'config.php';
$channel_access_token = $config['channel_access_token'];
$gemini_api_key = $config['gemini_api_key'];
// etc.
```

## Testing

### Test Main Webhook:
- URL: `https://ikeman.zhuge.jp/webhook.php`
- Logs: `webhook.log`
- Personality: Standard English/Thai assistant

### Test Kimura Webhook:
- URL: `https://ikeman.zhuge.jp/webhook-kimura.php`
- Logs: `webhook-kimura.log`
- Personality: Japanese-focused Kimura Bot

## Security

- All webhooks use the same signature verification
- Shared configuration keeps credentials centralized
- Individual log files for better monitoring

## Future Enhancements

- Add webhook routing based on user preferences
- Database integration for user-specific webhook assignments
- A/B testing framework for different personalities
- Webhook management dashboard

This architecture provides flexibility while maintaining code efficiency and security! 🚀
