# LINE Pay Integration Guide

## Overview
Your LINE Bot now supports LINE Pay integration for collecting payments. Users can type "pay" to initiate a payment process.

## Files Created

### Core Payment Files:
1. **line_pay.php** - LINE Pay API handler class
2. **payment_confirm.php** - Handles payment confirmations
3. **payment_cancel.php** - Handles payment cancellations
4. **payment_success.html** - Success page
5. **payment_failed.html** - Failure page
6. **payment_cancelled.html** - Cancellation page

### Updated Files:
- **webhook.php** - Added payment command handling
- **config.php** - Added LINE Pay configuration
- **config.example.php** - Added LINE Pay template

## LINE Pay Setup

### 1. Get LINE Pay Credentials

1. Go to [LINE Pay Console](https://pay.line.me/portal/jp/main)
2. Create a new merchant account
3. Create a new channel for your application
4. Get your credentials:
   - **Channel ID**
   - **Channel Secret**

### 2. Configure Credentials

Update your `config.php` with LINE Pay credentials:

```php
'line_pay_channel_id' => 'your_actual_channel_id',
'line_pay_channel_secret' => 'your_actual_channel_secret',
'line_pay_sandbox' => true, // Use true for testing, false for production
```

### 3. Set Up Webhook URLs

Make sure these URLs are accessible:
- `https://ikeman.zhuge.jp/payment_confirm.php`
- `https://ikeman.zhuge.jp/payment_cancel.php`

## How It Works

### 1. User Initiates Payment
- User sends "pay" or "payment" to the LINE Bot
- Bot creates a payment request with LINE Pay
- Bot sends a Flex Message with payment button

### 2. Payment Process
- User clicks "Pay with LINE Pay" button
- Redirected to LINE Pay payment page
- User completes payment or cancels

### 3. Payment Completion
- **Success**: Redirected to `payment_confirm.php` → `payment_success.html`
- **Failure**: Redirected to `payment_failed.html`
- **Cancel**: Redirected to `payment_cancel.php` → `payment_cancelled.html`

## Customization

### Product Details
Edit the `handlePaymentRequest()` function in `webhook.php`:

```php
$productName = "Your Product Name";
$amount = 1000; // Amount in smallest currency unit (e.g., 1000 = ¥1000 for JPY)
$currency = "JPY"; // or "USD", "THB", etc.
```

### Payment Flow
1. **Sandbox Testing**: Set `line_pay_sandbox => true` in config
2. **Production**: Set `line_pay_sandbox => false` and use production credentials

## Security Considerations

1. **SSL Required**: LINE Pay requires HTTPS for all endpoints
2. **Session Management**: Payment data is stored in PHP sessions
3. **Logging**: All payment activities are logged to `payment.log`
4. **Configuration**: Sensitive credentials are in `config.php` (git-ignored)

## Testing

### 1. Test Commands
Send these messages to your LINE Bot:
- `pay` - Initiate payment
- `payment` - Alternative payment command
- `ชำระเงิน` - Thai payment command

### 2. Test Flow
1. Send "pay" to bot
2. Click payment button in Flex Message
3. Complete payment in LINE Pay (sandbox)
4. Verify success/failure pages

## Supported Features

✅ **Payment Request** - Create payment with LINE Pay
✅ **Payment Confirmation** - Handle successful payments
✅ **Payment Cancellation** - Handle cancelled payments
✅ **Flex Messages** - Rich payment UI in LINE
✅ **Multi-language** - English and Thai support
✅ **Logging** - Comprehensive payment logging
✅ **Error Handling** - Robust error management

## Production Checklist

- [ ] Get production LINE Pay credentials
- [ ] Set `line_pay_sandbox => false`
- [ ] Test all payment flows
- [ ] Set up proper database storage (replace session storage)
- [ ] Configure proper logging and monitoring
- [ ] Implement proper error handling and user notifications
- [ ] Set up webhook URL verification

## Troubleshooting

### Common Issues:
1. **"Payment data not found"** - Session expired or invalid transaction
2. **"Invalid credentials"** - Check LINE Pay Channel ID/Secret
3. **"SSL certificate error"** - Ensure HTTPS is properly configured
4. **"Webhook not reachable"** - Verify URL accessibility

### Debug Steps:
1. Check `payment.log` for detailed logs
2. Verify SSL certificate on webhook URLs
3. Test webhook URLs manually
4. Confirm LINE Pay credentials in sandbox

Your LINE Bot now has full payment capabilities! 💳✨
