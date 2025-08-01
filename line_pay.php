<?php
/**
 * LINE Pay Integration for LINE Bot
 * Handles payment requests and confirmations
 */

require_once 'config.php';

class LinePayHandler {
    private $config;
    private $linePayApiUrl;
    
    public function __construct($config) {
        $this->config = $config;
        // LINE Pay API endpoint (sandbox for testing, production for live)
        $this->linePayApiUrl = $config['line_pay_sandbox'] ? 
            'https://sandbox-api-pay.line.me' : 
            'https://api-pay.line.me';
    }
    
    /**
     * Create a payment request
     */
    public function requestPayment($amount, $currency, $orderId, $productName, $confirmUrl, $cancelUrl) {
        $url = $this->linePayApiUrl . '/v3/payments/request';
        
        $data = [
            'amount' => (int)$amount,
            'currency' => $currency,
            'orderId' => $orderId,
            'packages' => [
                [
                    'id' => 'package-' . $orderId,
                    'amount' => (int)$amount,
                    'name' => $productName,
                    'products' => [
                        [
                            'id' => 'product-' . $orderId,
                            'name' => $productName,
                            'imageUrl' => $this->config['product_image_url'] ?? '',
                            'quantity' => 1,
                            'price' => (int)$amount
                        ]
                    ]
                ]
            ],
            'redirectUrls' => [
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl
            ],
            'options' => [
                'payment' => [
                    'capture' => true
                ]
            ]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'X-LINE-ChannelId: ' . $this->config['line_pay_channel_id'],
            'X-LINE-ChannelSecret: ' . $this->config['line_pay_channel_secret']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);
        
        if ($httpCode === 200 && $response['returnCode'] === '0000') {
            return [
                'success' => true,
                'transactionId' => $response['info']['transactionId'],
                'paymentUrl' => $response['info']['paymentUrl']['web'],
                'paymentAccessToken' => $response['info']['paymentAccessToken']
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['returnMessage'] ?? 'Payment request failed',
                'code' => $response['returnCode'] ?? $httpCode
            ];
        }
    }
    
    /**
     * Confirm payment after user approval
     */
    public function confirmPayment($transactionId, $amount, $currency) {
        $url = $this->linePayApiUrl . '/v3/payments/' . $transactionId . '/confirm';
        
        $data = [
            'amount' => (int)$amount,
            'currency' => $currency
        ];
        
        $headers = [
            'Content-Type: application/json',
            'X-LINE-ChannelId: ' . $this->config['line_pay_channel_id'],
            'X-LINE-ChannelSecret: ' . $this->config['line_pay_channel_secret']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);
        
        if ($httpCode === 200 && $response['returnCode'] === '0000') {
            return [
                'success' => true,
                'orderId' => $response['info']['orderId'],
                'transactionId' => $response['info']['transactionId'],
                'payInfo' => $response['info']['payInfo']
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['returnMessage'] ?? 'Payment confirmation failed',
                'code' => $response['returnCode'] ?? $httpCode
            ];
        }
    }
    
    /**
     * Check payment status
     */
    public function checkPaymentStatus($transactionId) {
        $url = $this->linePayApiUrl . '/v3/payments/' . $transactionId;
        
        $headers = [
            'X-LINE-ChannelId: ' . $this->config['line_pay_channel_id'],
            'X-LINE-ChannelSecret: ' . $this->config['line_pay_channel_secret']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);
        
        if ($httpCode === 200 && $response['returnCode'] === '0000') {
            return [
                'success' => true,
                'info' => $response['info']
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['returnMessage'] ?? 'Status check failed'
            ];
        }
    }
    
    /**
     * Create payment flex message for LINE Bot
     */
    public function createPaymentFlexMessage($productName, $amount, $currency, $paymentUrl) {
        return [
            'type' => 'flex',
            'altText' => "Payment for $productName",
            'contents' => [
                'type' => 'bubble',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'Payment Request',
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#00C851'
                        ]
                    ]
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $productName,
                            'weight' => 'bold',
                            'size' => 'lg'
                        ],
                        [
                            'type' => 'text',
                            'text' => "Amount: $currency " . number_format($amount),
                            'size' => 'md',
                            'color' => '#666666'
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'md'
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Click the button below to proceed with LINE Pay',
                            'size' => 'sm',
                            'color' => '#999999',
                            'margin' => 'md'
                        ]
                    ]
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'Pay with LINE Pay',
                                'uri' => $paymentUrl
                            ],
                            'style' => 'primary',
                            'color' => '#00C851'
                        ]
                    ]
                ]
            ]
        ];
    }
}
?>
