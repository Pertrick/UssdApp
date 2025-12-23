<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initialize payment with selected gateway
     */
    public function initializePayment(Business $business, float $amount, string $gateway, array $metadata = []): array
    {
        try {
            DB::beginTransaction();
            
            // Create payment record
            $payment = Payment::create([
                'business_id' => $business->id,
                'amount' => $amount,
                'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                'gateway' => $gateway,
                'status' => 'pending',
                'reference' => $this->generatePaymentReference(),
                'metadata' => $metadata
            ]);
            
            // Initialize payment based on gateway
            switch ($gateway) {
                case 'stripe':
                    $result = $this->initializeStripePayment($payment);
                    break;
                case 'paypal':
                    $result = $this->initializePayPalPayment($payment);
                    break;
                case 'flutterwave':
                    $result = $this->initializeFlutterwavePayment($payment);
                    break;
                case 'paystack':
                    $result = $this->initializePaystackPayment($payment);
                    break;
                case 'manual':
                    $result = $this->initializeManualPayment($payment);
                    break;
                default:
                    throw new \Exception("Unsupported payment gateway: {$gateway}");
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'gateway_data' => $result
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initialization failed', [
                'business_id' => $business->id,
                'amount' => $amount,
                'gateway' => $gateway,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment callback/webhook
     */
    public function processPaymentCallback(string $reference, array $callbackData): bool
    {
        try {
            $payment = Payment::where('reference', $reference)->first();
            
            if (!$payment) {
                Log::error('Payment not found', ['reference' => $reference]);
                return false;
            }
            
            DB::beginTransaction();
            
            // Verify payment based on gateway
            $isValid = $this->verifyPayment($payment, $callbackData);
            
            if ($isValid) {
                // Update payment status
                $payment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'gateway_response' => $callbackData
                ]);
                
                // Add funds to business account
                $billingService = app(BillingService::class);
                $billingService->addFunds($payment->business, $payment->amount, $payment->gateway);
                
                // Log successful payment
                \App\Services\ActivityService::log(
                    $payment->business->user_id,
                    'payment_completed',
                    "Payment completed: \${$payment->amount} via {$payment->gateway}",
                    'App\Models\Payment',
                    $payment->id,
                    [
                        'amount' => $payment->amount,
                        'gateway' => $payment->gateway,
                        'reference' => $payment->reference
                    ]
                );
                
                DB::commit();
                return true;
            } else {
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => $callbackData
                ]);
                
                DB::commit();
                return false;
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment callback processing failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Initialize Stripe payment
     */
    protected function initializeStripePayment(Payment $payment): array
    {
        // Stripe implementation
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => [
                        'name' => 'USSD Service Credits',
                    ],
                    'unit_amount' => (int)($payment->amount * 100), // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success', ['reference' => $payment->reference]),
            'cancel_url' => route('payment.cancel', ['reference' => $payment->reference]),
            'metadata' => [
                'payment_id' => $payment->id,
                'business_id' => $payment->business_id
            ]
        ]);
        
        return [
            'session_id' => $session->id,
            'checkout_url' => $session->url
        ];
    }

    /**
     * Initialize PayPal payment
     */
    protected function initializePayPalPayment(Payment $payment): array
    {
        // PayPal implementation
        $paypal = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            )
        );
        
        $payment_data = new \PayPal\Api\Payment();
        $payment_data->setIntent('sale');
        
        $item = new \PayPal\Api\Item();
        $item->setName('USSD Service Credits')
             ->setCurrency($payment->currency)
             ->setQuantity(1)
             ->setPrice($payment->amount);
        
        $itemList = new \PayPal\Api\ItemList();
        $itemList->setItems([$item]);
        
        $amount = new \PayPal\Api\Amount();
        $amount->setCurrency($payment->currency)
               ->setTotal($payment->amount);
        
        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount)
                   ->setItemList($itemList)
                   ->setDescription('USSD Service Credits')
                   ->setInvoiceNumber($payment->reference);
        
        $redirectUrls = new \PayPal\Api\RedirectUrls();
        $redirectUrls->setReturnUrl(route('payment.success', ['reference' => $payment->reference]))
                    ->setCancelUrl(route('payment.cancel', ['reference' => $payment->reference]));
        
        $payment_data->setTransactions([$transaction])
                    ->setRedirectUrls($redirectUrls);
        
        try {
            $payment_data->create($paypal);
            return [
                'payment_id' => $payment_data->getId(),
                'approval_url' => $payment_data->getApprovalLink()
            ];
        } catch (\Exception $e) {
            throw new \Exception('PayPal payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize Flutterwave payment (Popular in Africa)
     */
    protected function initializeFlutterwavePayment(Payment $payment): array
    {
        $flutterwave = new \Flutterwave\Flutterwave(
            config('services.flutterwave.public_key'),
            config('services.flutterwave.secret_key')
        );
        
        $payload = [
            'tx_ref' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'redirect_url' => route('payment.success', ['reference' => $payment->reference]),
            'customer' => [
                'email' => $payment->business->user->email,
                'name' => $payment->business->business_name
            ],
            'customizations' => [
                'title' => 'USSD Service Credits',
                'description' => 'Add funds to your USSD service account'
            ]
        ];
        
        $response = $flutterwave->payment->initialize($payload);
        
        return [
            'payment_url' => $response['data']['link'],
            'transaction_id' => $response['data']['id']
        ];
    }

    /**
     * Initialize Paystack payment (Popular in Nigeria)
     */
    protected function initializePaystackPayment(Payment $payment): array
    {
        $secretKey = config('services.paystack.secret_key');
        
        if (!$secretKey) {
            throw new \Exception('Paystack secret key not configured. Please set PAYSTACK_SECRET_KEY in your .env file.');
        }
        
        $amountInKobo = (int)($payment->amount * 100); // Convert to kobo (smallest currency unit)
        
        $data = [
            'amount' => $amountInKobo,
            'email' => $payment->business->user->email,
            'reference' => $payment->reference,
            'callback_url' => route('payment.success', ['reference' => $payment->reference]),
            'metadata' => [
                'payment_id' => $payment->id,
                'business_id' => $payment->business_id,
                'business_name' => $payment->business->business_name
            ],
            'currency' => $payment->currency ?? 'NGN'
        ];
        
        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('Paystack cURL error', ['error' => $error]);
            throw new \Exception('Failed to initialize Paystack payment: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !$result['status']) {
            $errorMessage = $result['message'] ?? 'Unknown error';
            Log::error('Paystack initialization failed', [
                'response' => $result,
                'payment_id' => $payment->id
            ]);
            throw new \Exception('Paystack payment initialization failed: ' . $errorMessage);
        }
        
        return [
            'authorization_url' => $result['data']['authorization_url'],
            'access_code' => $result['data']['access_code'],
            'reference' => $result['data']['reference']
        ];
    }

    /**
     * Initialize manual payment (Bank transfer, etc.)
     */
    protected function initializeManualPayment(Payment $payment): array
    {
        return [
            'bank_details' => [
                'bank_name' => config('services.manual.bank_name'),
                'account_number' => config('services.manual.account_number'),
                'account_name' => config('services.manual.account_name'),
                'reference' => $payment->reference
            ],
            'instructions' => 'Please transfer the amount and include the reference number in the transfer description.'
        ];
    }

    /**
     * Verify payment with gateway
     */
    protected function verifyPayment(Payment $payment, array $callbackData): bool
    {
        switch ($payment->gateway) {
            case 'stripe':
                return $this->verifyStripePayment($payment, $callbackData);
            case 'paypal':
                return $this->verifyPayPalPayment($payment, $callbackData);
            case 'flutterwave':
                return $this->verifyFlutterwavePayment($payment, $callbackData);
            case 'paystack':
                return $this->verifyPaystackPayment($payment, $callbackData);
            case 'manual':
                return $this->verifyManualPayment($payment, $callbackData);
            default:
                return false;
        }
    }

    /**
     * Generate unique payment reference
     */
    protected function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(8)) . '-' . time();
    }

    // Verification methods for each gateway
    protected function verifyStripePayment(Payment $payment, array $callbackData): bool
    {
        // Implement Stripe webhook verification
        return true; // Simplified for now
    }

    protected function verifyPayPalPayment(Payment $payment, array $callbackData): bool
    {
        // Implement PayPal IPN verification
        return true; // Simplified for now
    }

    protected function verifyFlutterwavePayment(Payment $payment, array $callbackData): bool
    {
        // Implement Flutterwave verification
        return true; // Simplified for now
    }

    protected function verifyPaystackPayment(Payment $payment, array $callbackData): bool
    {
        $secretKey = config('services.paystack.secret_key');
        
        if (!$secretKey) {
            Log::error('Paystack secret key not configured for verification');
            return false;
        }
        
        // Get reference from callback data or payment
        $reference = $callbackData['reference'] ?? $payment->reference;
        
        if (!$reference) {
            Log::error('Paystack reference not found', ['payment_id' => $payment->id]);
            return false;
        }
        
        // Verify transaction with Paystack API
        $ch = curl_init("https://api.paystack.co/transaction/verify/{$reference}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('Paystack verification cURL error', ['error' => $error, 'reference' => $reference]);
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !$result['status']) {
            Log::error('Paystack verification failed', [
                'response' => $result,
                'reference' => $reference,
                'payment_id' => $payment->id
            ]);
            return false;
        }
        
        $transaction = $result['data'];
        
        // Verify transaction details match payment
        $amountMatches = (int)($payment->amount * 100) === (int)$transaction['amount'];
        $statusSuccess = $transaction['status'] === 'success';
        
        if (!$amountMatches) {
            Log::warning('Paystack amount mismatch', [
                'payment_amount' => $payment->amount,
                'transaction_amount' => $transaction['amount'] / 100,
                'reference' => $reference
            ]);
        }
        
        // Store transaction details in payment record
        $payment->update([
            'gateway_response' => array_merge($payment->gateway_response ?? [], [
                'verification_response' => $result,
                'transaction_id' => $transaction['id'] ?? null,
                'customer_email' => $transaction['customer']['email'] ?? null
            ])
        ]);
        
        return $statusSuccess && $amountMatches;
    }

    protected function verifyManualPayment(Payment $payment, array $callbackData): bool
    {
        // Manual verification by admin
        return isset($callbackData['verified']) && $callbackData['verified'] === true;
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array
    {
        return [
            'paystack' => [
                'name' => 'Paystack',
                'description' => 'Pay with cards or bank transfer (Nigeria)',
                'icon' => 'bank',
                'enabled' => config('services.paystack.enabled', true)
            ],
            'stripe' => [
                'name' => 'Credit/Debit Card',
                'description' => 'Pay with Visa, Mastercard, or other cards',
                'icon' => 'credit-card',
                'enabled' => config('services.stripe.enabled', false)
            ],
            'paypal' => [
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'icon' => 'paypal',
                'enabled' => config('services.paypal.enabled', false)
            ],
            'flutterwave' => [
                'name' => 'Flutterwave',
                'description' => 'Pay with mobile money or cards (Africa)',
                'icon' => 'mobile',
                'enabled' => config('services.flutterwave.enabled', false)
            ],
            'manual' => [
                'name' => 'Bank Transfer',
                'description' => 'Manual bank transfer',
                'icon' => 'bank-transfer',
                'enabled' => true
            ]
        ];
    }
}
