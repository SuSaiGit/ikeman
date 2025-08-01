<?php
/**
 * LINE Pay Payment Confirmation Handler
 * Handles payment confirmations from LINE Pay
 */

require_once 'config.php';
require_once 'line_pay.php';

$config = require_once 'config.php';
$linePayHandler = new LinePayHandler($config);

// Log the incoming request
function logPayment($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] PAYMENT: $message" . PHP_EOL;
    file_put_contents('payment.log', $logEntry, FILE_APPEND | LOCK_EX);
}

try {
    // Get parameters from LINE Pay callback
    $transactionId = $_GET['transactionId'] ?? '';
    $orderId = $_GET['orderId'] ?? '';
    
    if (empty($transactionId)) {
        throw new Exception('Missing transaction ID');
    }
    
    logPayment("Confirming payment - Transaction ID: $transactionId, Order ID: $orderId");
    
    // You should store the original payment details in a database
    // For this example, we'll use session or a simple file storage
    session_start();
    $paymentData = $_SESSION['payment_' . $transactionId] ?? null;
    
    if (!$paymentData) {
        throw new Exception('Payment data not found');
    }
    
    // Confirm the payment
    $confirmResult = $linePayHandler->confirmPayment(
        $transactionId,
        $paymentData['amount'],
        $paymentData['currency']
    );
    
    if ($confirmResult['success']) {
        logPayment("Payment confirmed successfully: " . json_encode($confirmResult));
        
        // Update your database with successful payment
        // Send confirmation message to user via LINE Bot
        
        // Clear the session data
        unset($_SESSION['payment_' . $transactionId]);
        
        // Redirect to success page
        header('Location: payment_success.html');
        exit;
        
    } else {
        logPayment("Payment confirmation failed: " . $confirmResult['error']);
        header('Location: payment_failed.html');
        exit;
    }
    
} catch (Exception $e) {
    logPayment("Payment confirmation error: " . $e->getMessage());
    header('Location: payment_failed.html');
    exit;
}
?>
