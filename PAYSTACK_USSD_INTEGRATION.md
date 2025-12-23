# Paystack USSD Integration Guide

## Overview

This guide explains how to integrate Paystack USSD payment endpoints into your USSD application. The integration provides a complete payment solution that allows users to make payments via USSD codes on their mobile devices.

## Available Paystack USSD Endpoints

### 1. Paystack USSD Transaction Initialize
**Purpose**: Initialize a transaction with USSD payment channel
**Endpoint**: `POST /transaction/initialize`
**Use Case**: When you want to provide users with a USSD code to complete payment

**Request Mapping**:
```json
{
    "amount": "{{input.amount}}",
    "email": "{{input.email}}",
    "reference": "{{session.session_id}}",
    "channels": "[\"ussd\"]",
    "callback_url": "{{config.app_url}}/payment/callback"
}
```

**Response Mapping**:
```json
{
    "success": "status",
    "authorization_url": "data.authorization_url",
    "reference": "data.reference",
    "access_code": "data.access_code",
    "ussd_code": "data.ussd_code"
}
```

### 2. Paystack USSD Charge
**Purpose**: Direct USSD payment charge
**Endpoint**: `POST /charge`
**Use Case**: For direct USSD payments without redirect

**Request Mapping**:
```json
{
    "amount": "{{input.amount}}",
    "email": "{{input.email}}",
    "reference": "{{session.session_id}}",
    "ussd": "{\"type\": \"{{input.ussd_type}}\"}"
}
```

**Response Mapping**:
```json
{
    "success": "status",
    "reference": "data.reference",
    "status": "data.status",
    "display_text": "data.display_text",
    "ussd_code": "data.ussd_code"
}
```

### 3. Paystack Create Customer
**Purpose**: Create customer account in Paystack
**Endpoint**: `POST /customer`
**Use Case**: Register customers before processing payments

**Request Mapping**:
```json
{
    "email": "{{input.email}}",
    "first_name": "{{input.first_name}}",
    "last_name": "{{input.last_name}}",
    "phone": "{{session.phone_number}}"
}
```

**Response Mapping**:
```json
{
    "success": "status",
    "customer_code": "data.customer_code",
    "customer_id": "data.id",
    "email": "data.email",
    "phone": "data.phone"
}
```

### 4. Paystack Dedicated Virtual Account
**Purpose**: Create dedicated virtual account for customer
**Endpoint**: `POST /dedicated_account/assign`
**Use Case**: Assign unique virtual accounts for USSD payments

**Request Mapping**:
```json
{
    "customer": "{{input.customer_code}}",
    "preferred_bank": "{{input.preferred_bank}}"
}
```

**Response Mapping**:
```json
{
    "success": "status",
    "account_number": "data.account_number",
    "bank_name": "data.bank.name",
    "bank_code": "data.bank.code",
    "customer_code": "data.customer"
}
```

### 5. Paystack Transaction Verify
**Purpose**: Verify transaction status
**Endpoint**: `GET /transaction/verify/{{input.reference}}`
**Use Case**: Check payment status after USSD completion

**Request Mapping**:
```json
{
    "reference": "{{input.reference}}"
}
```

**Response Mapping**:
```json
{
    "success": "status",
    "transaction_status": "data.status",
    "amount": "data.amount",
    "currency": "data.currency",
    "reference": "data.reference",
    "gateway_response": "data.gateway_response"
}
```

## Real-World USSD Payment Flow Example

### Scenario: Mobile Payment Service
A business wants to create a USSD service for mobile payments where users can:
1. Register for the service
2. Make payments via USSD
3. Check payment status

### Step 1: Create USSD Service
```php
$ussd = USSD::create([
    'name' => 'Mobile Payment Service',
    'pattern' => '*999#',
    'description' => 'Mobile payment service via USSD',
    'is_active' => true
]);
```

### Step 2: Create Payment Flow
```php
// Main Menu Flow
$mainFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'Main Menu',
    'title' => 'Welcome to Mobile Payment',
    'menu_text' => "1. Make Payment\n2. Check Balance\n3. Transaction History\n0. Exit",
    'is_root' => true,
    'is_active' => true
]);

// Payment Flow
$paymentFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'Payment Menu',
    'title' => 'Make Payment',
    'menu_text' => "Enter amount to pay:",
    'is_root' => false,
    'is_active' => true
]);
```

### Step 3: Add API Call Actions
```php
// Create customer action
USSDFlowOption::create([
    'flow_id' => $paymentFlow->id,
    'option_text' => 'Create Account',
    'option_value' => '1',
    'action_type' => 'external_api_call',
    'action_data' => [
        'api_configuration_id' => 'paystack_create_customer_id',
        'success_flow_id' => $paymentAmountFlow->id,
        'error_flow_id' => $errorFlow->id
    ]
]);

// Initialize USSD payment action
USSDFlowOption::create([
    'flow_id' => $paymentAmountFlow->id,
    'option_text' => 'Pay via USSD',
    'option_value' => '*',
    'action_type' => 'external_api_call',
    'action_data' => [
        'api_configuration_id' => 'paystack_ussd_initialize_id',
        'success_flow_id' => $ussdCodeFlow->id,
        'error_flow_id' => $errorFlow->id
    ]
]);
```

### Step 4: User Experience Flow

**User Journey**:
```
User dials: *999#
System: Welcome to Mobile Payment
        1. Make Payment
        2. Check Balance
        3. Transaction History
        0. Exit

User selects: 1
System: Enter amount to pay:

User enters: 1000
System: Creating your account...

System: Please dial this USSD code to complete payment:
        *737*33*4*18791#

User dials USSD code on their phone
System: Payment successful! Your transaction reference is ABC123
```

## Configuration Requirements

### 1. Paystack API Keys
You need to configure your Paystack API keys in the marketplace:

```json
{
    "secret_key": "sk_test_your_secret_key_here",
    "public_key": "pk_test_your_public_key_here"
}
```

### 2. USSD Type Configuration
For direct USSD charges, you need to specify the USSD type:

```json
{
    "ussd_type": "737"  // GTBank USSD code
}
```

### 3. Bank Codes for Virtual Accounts
Common Nigerian bank codes:
- GTBank: `058`
- Access Bank: `044`
- First Bank: `011`
- UBA: `033`
- Zenith Bank: `057`

## Error Handling

The system includes comprehensive error handling for:
- **Timeout**: "Payment service temporarily unavailable. Please try again."
- **Network Error**: "Network error. Please check your connection."
- **API Error**: Displays the actual error message from Paystack

## Testing

### 1. Test USSD Flow
```bash
php test_paystack_ussd_integration.php
```

### 2. Test Individual Endpoints
```bash
php artisan integration:test paystack_ussd_initialize
php artisan integration:test paystack_ussd_charge
php artisan integration:test paystack_create_customer
```

## Security Considerations

1. **API Key Protection**: Store Paystack API keys securely
2. **Webhook Verification**: Implement webhook signature verification
3. **Transaction Validation**: Always verify transactions before processing
4. **Rate Limiting**: Implement rate limiting for API calls

## Best Practices

1. **User Experience**: Provide clear instructions for USSD codes
2. **Error Messages**: Use user-friendly error messages
3. **Transaction Tracking**: Store transaction references for verification
4. **Timeout Handling**: Set appropriate timeouts for API calls
5. **Retry Logic**: Implement retry mechanisms for failed calls

## Support

For technical support:
- Paystack Documentation: https://paystack.com/docs
- USSD Integration Guide: https://support.paystack.com/en/articles/4885122
- API Reference: https://paystack.com/docs/api

## Conclusion

The Paystack USSD integration provides a complete payment solution for USSD applications. Users can make payments directly from their mobile devices using USSD codes, providing a seamless and secure payment experience.

The integration supports multiple payment flows:
- Direct USSD charges
- Transaction initialization with USSD codes
- Customer management
- Virtual account creation
- Transaction verification

This makes it ideal for businesses that want to offer mobile payment services through USSD technology.
