# LINE Bot Webhook Setup Guide

## Files Created

1. **webhook.php** - Main webhook endpoint that receives messages from LINE
2. **config.example.php** - Configuration template

## Setup Instructions

### 1. LINE Developers Console Setup

1. Go to [LINE Developers Console](https://developers.line.biz/)
2. Create a new Provider (if you don't have one)
3. Create a new Messaging API Channel
4. Get your credentials:
   - **Channel Access Token**: Messaging API > Channel Access Token
   - **Channel Secret**: Basic Settings > Channel Secret

### 2. Server Setup

1. Copy `config.example.php` to `config.php`
2. Edit `config.php` and fill in your LINE Bot credentials
3. Upload `webhook.php` to your web server
4. Make sure your server supports PHP and CURL

### 3. Configure Webhook URL

1. In LINE Developers Console, go to Messaging API settings
2. Set Webhook URL to: `https://yourdomain.com/webhook.php`
3. Enable "Use webhook"
4. Verify the webhook URL

### 4. Test Your Bot

1. Add your bot as a friend using the QR code from LINE Developers Console
2. Send a message to test
3. Check `webhook.log` for debugging information

## Supported Features

- **Text Messages**: Responds to various commands (hello, help, time, etc.)
- **Image Messages**: Acknowledges image uploads
- **Audio Messages**: Acknowledges audio uploads
- **Follow/Unfollow Events**: Handles user following/unfollowing
- **Postback Events**: Handles interactive button responses

## Commands

- `hello` / `hi` / `สวัสดี` - Greeting
- `help` / `ช่วยเหลือ` - Show help
- `time` / `เวลา` - Get current time
- `bye` / `goodbye` / `ลาก่อน` - Farewell

## Security Notes

- The signature verification is commented out initially for testing
- Uncomment the signature verification in production
- Keep your Channel Secret secure
- Use HTTPS for your webhook URL

## Debugging

- Check `webhook.log` for incoming requests and responses
- Enable debug mode in config for more detailed logging
- Use LINE Bot SDK for more advanced features

## Next Steps

- Customize the `processTextMessage()` function for your needs
- Add database integration for user data
- Implement rich messages (cards, carousels, etc.)
- Add natural language processing (NLP)
- Integrate with external APIs
