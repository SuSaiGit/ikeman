<?php
/**
 * LINE Pay Payment Cancellation Handler
 */

require_once 'config.php';

// Log the cancellation
function logPayment($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] PAYMENT: $message" . PHP_EOL;
    file_put_contents('payment.log', $logEntry, FILE_APPEND | LOCK_EX);
}

$transactionId = $_GET['transactionId'] ?? '';
$orderId = $_GET['orderId'] ?? '';

logPayment("Payment cancelled - Transaction ID: $transactionId, Order ID: $orderId");

// Clean up session data
session_start();
if (isset($_SESSION['payment_' . $transactionId])) {
    unset($_SESSION['payment_' . $transactionId]);
}

// Redirect to cancellation page
header('Location: payment_cancelled.html');
exit;
?>
