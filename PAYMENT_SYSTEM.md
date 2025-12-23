# Payment System for Wallet Funding

## Overview

Your USSD SaaS platform now supports multiple payment gateways for users to fund their wallets. This system provides flexibility, security, and global accessibility.

## Supported Payment Methods

### **💳 Credit/Debit Cards (Stripe)**
- **Coverage**: Global
- **Currencies**: USD, EUR, GBP, and 135+ currencies
- **Processing Time**: Instant
- **Fees**: 2.9% + 30¢ per transaction
- **Best For**: International customers, high-value transactions

### **🅿️ PayPal**
- **Coverage**: 200+ countries
- **Currencies**: 25+ currencies
- **Processing Time**: Instant
- **Fees**: 2.9% + fixed fee per currency
- **Best For**: PayPal users, international payments

### **🌍 Flutterwave (Africa)**
- **Coverage**: 30+ African countries
- **Currencies**: Local currencies (NGN, KES, GHS, etc.)
- **Processing Time**: Instant
- **Fees**: 1.4% - 3.8% depending on country
- **Best For**: African customers, mobile money

### **🇳🇬 Paystack (Nigeria)**
- **Coverage**: Nigeria, Ghana, South Africa
- **Currencies**: NGN, GHS, ZAR
- **Processing Time**: Instant
- **Fees**: 1.5% + ₦100 for local cards
- **Best For**: Nigerian customers, bank transfers

### **🏦 Bank Transfer (Manual)**
- **Coverage**: Global
- **Currencies**: USD, EUR, GBP
- **Processing Time**: 1-3 business days
- **Fees**: Bank transfer fees only
- **Best For**: Large amounts, corporate clients

## User Experience Flow

### **Step 1: Amount Selection**
```
┌─────────────────────────────────────┐
│ Add Funds to Account                │
│                                     │
│ Amount (USD): [_______]             │
│                                     │
│ Pricing: $0.02 per USSD session     │
│ Estimated sessions: 50 sessions     │
│                                     │
│ [Cancel] [Continue]                 │
└─────────────────────────────────────┘
```

### **Step 2: Payment Method Selection**
```
┌─────────────────────────────────────┐
│ Select Payment Method               │
│                                     │
│ [💳] Credit/Debit Card              │
│     Pay with Visa, Mastercard, etc. │
│                                     │
│ [🅿️] PayPal                        │
│     Pay with your PayPal account    │
│                                     │
│ [🌍] Flutterwave                    │
│     Pay with mobile money (Africa)  │
│                                     │
│ [🇳🇬] Paystack                     │
│     Pay with cards (Nigeria)        │
│                                     │
│ [🏦] Bank Transfer                  │
│     Manual bank transfer            │
│                                     │
│ [Back] [Pay $10.00]                │
└─────────────────────────────────────┘
```

### **Step 3: Payment Processing**
```
┌─────────────────────────────────────┐
│ Processing Payment...               │
│                                     │
│     [⏳ Spinner]                    │
│                                     │
│ Redirecting to payment gateway...   │
│ Please wait...                      │
└─────────────────────────────────────┘
```

## Payment Gateway Integration

### **Stripe Integration**
```php
// Initialize Stripe payment
$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

$session = $stripe->checkout->sessions->create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => 'USSD Service Credits'],
            'unit_amount' => 1000, // $10.00 in cents
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => route('payment.success'),
    'cancel_url' => route('payment.cancel'),
]);
```

### **PayPal Integration**
```php
// Initialize PayPal payment
$payment = new \PayPal\Api\Payment();
$payment->setIntent('sale');

$item = new \PayPal\Api\Item();
$item->setName('USSD Service Credits')
     ->setCurrency('USD')
     ->setQuantity(1)
     ->setPrice(10.00);

$amount = new \PayPal\Api\Amount();
$amount->setCurrency('USD')->setTotal(10.00);

$transaction = new \PayPal\Api\Transaction();
$transaction->setAmount($amount)->setItemList($itemList);
```

### **Flutterwave Integration**
```php
// Initialize Flutterwave payment
$payload = [
    'tx_ref' => 'PAY-ABC123-' . time(),
    'amount' => 10.00,
    'currency' => 'USD',
    'redirect_url' => route('payment.success'),
    'customer' => [
        'email' => $user->email,
        'name' => $business->business_name
    ],
    'customizations' => [
        'title' => 'USSD Service Credits',
        'description' => 'Add funds to your USSD service account'
    ]
];
```

## Security Features

### **🔒 Payment Security**
- **SSL Encryption**: All payment data encrypted in transit
- **PCI Compliance**: Stripe handles card data securely
- **Webhook Verification**: Payment confirmations verified via webhooks
- **Fraud Detection**: Built-in fraud prevention systems

### **🛡️ Transaction Security**
- **Unique References**: Each payment has a unique reference number
- **Signature Verification**: Webhook signatures verified
- **Idempotency**: Prevents duplicate payments
- **Audit Trail**: Complete payment history logged

## Payment Flow

### **1. Payment Initialization**
```
User clicks "Add Funds" → Selects amount → Chooses payment method → System creates payment record → Redirects to gateway
```

### **2. Payment Processing**
```
Gateway processes payment → User completes payment → Gateway sends webhook → System verifies payment → Funds added to wallet
```

### **3. Payment Completion**
```
Payment verified → Business account credited → Payment status updated → User notified → Transaction logged
```

## Error Handling

### **Payment Failures**
- **Insufficient Funds**: Card declined, insufficient balance
- **Network Issues**: Connection timeouts, gateway unavailable
- **Invalid Data**: Incorrect card details, expired cards
- **Fraud Detection**: Suspicious activity blocked

### **Recovery Actions**
- **Retry Payment**: User can retry failed payments
- **Alternative Methods**: Suggest different payment options
- **Manual Verification**: Admin can verify manual payments
- **Support Contact**: Direct support for complex issues

## Admin Features

### **Payment Management**
- **View All Payments**: Complete payment history
- **Manual Verification**: Verify bank transfer payments
- **Refund Processing**: Process refunds when needed
- **Payment Analytics**: Track payment success rates

### **Manual Payment Verification**
```php
// Admin verifies manual payment
public function verifyManualPayment(Request $request, Payment $payment)
{
    if ($request->verified) {
        $this->paymentService->processPaymentCallback(
            $payment->reference, 
            ['verified' => true]
        );
        return response()->json(['success' => true]);
    }
}
```

## Configuration

### **Environment Variables**
```env
# Stripe Configuration
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal Configuration
PAYPAL_CLIENT_ID=...
PAYPAL_SECRET=...
PAYPAL_MODE=sandbox

# Flutterwave Configuration
FLUTTERWAVE_PUBLIC_KEY=...
FLUTTERWAVE_SECRET_KEY=...
FLUTTERWAVE_ENCRYPTION_KEY=...

# Paystack Configuration
PAYSTACK_PUBLIC_KEY=...
PAYSTACK_SECRET_KEY=...

# Manual Bank Transfer
MANUAL_BANK_NAME="Your Bank Name"
MANUAL_ACCOUNT_NUMBER="1234567890"
MANUAL_ACCOUNT_NAME="Your Company Name"
```

### **Service Configuration**
```php
// config/services.php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],

'paypal' => [
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET'),
    'mode' => env('PAYPAL_MODE', 'sandbox'),
],

'flutterwave' => [
    'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
    'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
    'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
],

'paystack' => [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
],

'manual' => [
    'bank_name' => env('MANUAL_BANK_NAME'),
    'account_number' => env('MANUAL_ACCOUNT_NUMBER'),
    'account_name' => env('MANUAL_ACCOUNT_NAME'),
],
```

## Webhook Endpoints

### **Webhook URLs**
```
POST /payment/webhook/stripe
POST /payment/webhook/paypal
POST /payment/webhook/flutterwave
POST /payment/webhook/paystack
```

### **Webhook Verification**
```php
// Verify webhook signature
protected function verifyStripeWebhook(array $payload, string $signature): bool
{
    $endpoint_secret = config('services.stripe.webhook_secret');
    $sig_header = $signature;
    $event = null;

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        return false;
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        return false;
    }

    return true;
}
```

## Testing

### **Test Cards (Stripe)**
```
Success: 4242 4242 4242 4242
Decline: 4000 0000 0000 0002
Insufficient Funds: 4000 0000 0000 9995
```

### **Test Mode**
- **Stripe**: Use test keys for development
- **PayPal**: Use sandbox environment
- **Flutterwave**: Use test mode
- **Paystack**: Use test keys

## Monitoring & Analytics

### **Payment Metrics**
- **Success Rate**: Percentage of successful payments
- **Average Transaction Value**: Mean payment amount
- **Payment Method Distribution**: Usage by gateway
- **Geographic Distribution**: Payments by country

### **Alerts**
- **Failed Payments**: High failure rate notifications
- **Webhook Failures**: Payment verification issues
- **Low Success Rate**: Gateway performance issues
- **Suspicious Activity**: Fraud detection alerts

## Support & Documentation

### **User Support**
- **Payment FAQ**: Common payment questions
- **Troubleshooting Guide**: Fix payment issues
- **Contact Support**: Direct help for complex issues
- **Payment Status Check**: Real-time payment status

### **Developer Documentation**
- **API Reference**: Payment API endpoints
- **Webhook Guide**: Webhook integration
- **SDK Examples**: Code samples
- **Testing Guide**: Payment testing procedures

## Future Enhancements

### **Planned Features**
- **Recurring Payments**: Automatic monthly top-ups
- **Payment Plans**: Installment payments
- **Multi-Currency**: Support for local currencies
- **Mobile Money**: Direct mobile money integration
- **Crypto Payments**: Bitcoin/Ethereum support

### **Advanced Analytics**
- **Payment Patterns**: User payment behavior
- **Revenue Forecasting**: Predict future payments
- **Churn Analysis**: Payment failure patterns
- **Optimization**: Payment method recommendations

## Conclusion

The payment system provides:

✅ **Multiple Options**: 5+ payment methods for global coverage
✅ **Security**: PCI compliance and fraud protection
✅ **User-Friendly**: Simple 3-step payment process
✅ **Reliable**: Webhook verification and error handling
✅ **Scalable**: Easy to add new payment gateways
✅ **Transparent**: Clear pricing and transaction history

This comprehensive payment system ensures your users can easily fund their wallets using their preferred payment method, regardless of their location or payment preferences.
