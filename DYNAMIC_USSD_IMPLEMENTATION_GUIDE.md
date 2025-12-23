# Complete Guide: Building Dynamic USSD Applications

## Table of Contents
1. [System Architecture Overview](#system-architecture-overview)
2. [Core Concepts](#core-concepts)
3. [Building a Complete USSD Service: Mobile Money Transfer Example](#building-a-complete-ussd-service)
4. [Implementation Details](#implementation-details)
5. [Advanced Features](#advanced-features)
6. [Common Complications & Solutions](#common-complications--solutions)
7. [Best Practices](#best-practices)

---

## System Architecture Overview

Your USSD system is built on a **flow-based architecture** where:

- **USSD Services** (`ussds` table) - Top-level service configuration
- **Flows** (`ussd_flows` table) - Individual menu screens/steps
- **Flow Options** (`ussd_flow_options` table) - Menu items that connect flows
- **Sessions** (`ussd_sessions` table) - Track user interactions
- **External APIs** (`external_api_configurations` table) - Integrate with third-party services

### Flow Types

1. **Static Flows** - Predefined menu options
2. **Dynamic Flows** - Menu options generated from API responses
3. **Input Flows** - Collect user data (phone numbers, amounts, PINs, etc.)

---

## Core Concepts

### 1. Flow Structure

```
USSD Service
  └── Root Flow (is_root = true)
       ├── Flow Option 1 → Next Flow A
       ├── Flow Option 2 → Next Flow B
       └── Flow Option 3 → Next Flow C
```

### 2. Session Management

- Each user interaction creates/updates a session
- Sessions track: current flow, collected data, API responses
- Sessions expire after 30 minutes of inactivity
- Session data stored in JSON format for flexibility

### 3. Action Types

Flow options can have different action types:

- `navigate` - Move to another flow
- `message` - Display a message
- `end_session` - End the USSD session
- `api_call` - Make external API call
- `input_text`, `input_number`, `input_phone`, `input_amount`, `input_pin` - Collect user input
- `process_registration`, `process_feedback`, etc. - Process collected data

### 4. Dynamic Flows

Dynamic flows fetch data from external APIs and generate menu options dynamically:

- API response is cached in session for pagination
- Supports pagination (next/back)
- Template variables for dynamic content
- Configurable response parsing

---

## Building a Complete USSD Service: Mobile Money Transfer Example

We'll build a **Mobile Money Transfer Service** that demonstrates:
- Static menus
- Dynamic data (balance, transaction history)
- Input collection (amount, phone number, PIN)
- External API integration
- Error handling
- Session management

### Use Case: Mobile Money Transfer Service

**Features:**
1. Check balance
2. Send money
3. Buy airtime
4. Transaction history
5. Change PIN
6. Account information

---

## Implementation Details

### Step 1: Create the USSD Service

**For Tinker (Command Line):**
```php
// Start tinker: php artisan tinker

// Get the authenticated user (replace with your user ID or email)
$user = \App\Models\User::find(3);

// Get the user's primary business
$business = $user->primaryBusiness;

// If no primary business exists, create one or get the first business
if (!$business) {
    $business = $user->businesses()->first();
    if (!$business) {
        throw new \Exception('User has no business. Please create a business first.');
    }
}

// Get testing environment
$testingEnvironment = \App\Models\Environment::where('name', 'testing')->first();
if (!$testingEnvironment) {
    // Create testing environment if it doesn't exist
    $testingEnvironment = \App\Models\Environment::create([
        'name' => 'testing',
        'label' => 'Testing',
        'description' => 'Real API calls in test/sandbox mode',
        'color' => 'yellow',
        'allows_real_api_calls' => true,
        'is_default' => true,
        'is_active' => true,
    ]);
}

// Create the USSD service
$ussd = \App\Models\USSD::create([
    'name' => 'Mobile Money Service',
    'description' => 'Send money, buy airtime, check balance',
    'pattern' => '*300#', // Testing code
    'testing_ussd_code' => '*123#',
    'live_ussd_code' => '*456#', // Production code
    'user_id' => $user->id,
    'business_id' => $business->id,
    'environment_id' => $testingEnvironment->id,
    'is_active' => true
]);

// Create default root flow
$ussd->createDefaultRootFlow();
```

**For Controller/Application Code:**
```php
// In USSDController or via admin panel
use Illuminate\Support\Facades\Auth;

// Get the authenticated user's primary business
$business = Auth::user()->primaryBusiness;

// Ensure the business belongs to the authenticated user
if (!$business) {
    abort(403, 'No business found. Please register a business first.');
}

// Get testing environment (default for new USSDs)
$testingEnvironment = \App\Models\Environment::where('name', 'testing')->first();
if (!$testingEnvironment) {
    // Fallback: create testing environment if it doesn't exist
    $testingEnvironment = \App\Models\Environment::create([
        'name' => 'testing',
        'label' => 'Testing',
        'description' => 'Real API calls in test/sandbox mode',
        'color' => 'yellow',
        'allows_real_api_calls' => true,
        'is_default' => true,
        'is_active' => true,
    ]);
}

$ussd = USSD::create([
    'name' => 'Mobile Money Service',
    'description' => 'Send money, buy airtime, check balance',
    'pattern' => '*123#', // Testing code
    'testing_ussd_code' => '*123#',
    'live_ussd_code' => '*456#', // Production code
    'user_id' => Auth::id(),
    'business_id' => $business->id,
    'environment_id' => $testingEnvironment->id,
    'is_active' => true,
]);

// Create default root flow (this creates a root flow automatically)
$ussd->createDefaultRootFlow();
```

### Step 2: Update Root Flow (Main Menu)

Since `createDefaultRootFlow()` already creates a root flow, we need to **update** it instead of creating a new one:

```php
// Get the existing root flow (created by createDefaultRootFlow())
$rootFlow = $ussd->rootFlow();

// If root flow doesn't exist, create it
if (!$rootFlow) {
    $rootFlow = $ussd->createDefaultRootFlow();
}

// Update the root flow with our custom menu
$rootFlow->update([
    'name' => 'main_menu',
    'title' => 'Mobile Money Service',
    'menu_text' => "Welcome to Mobile Money\n\n1. Check Balance\n2. Send Money\n3. Buy Airtime\n4. Transaction History\n5. Change PIN\n6. Account Info\n0. Exit",
    'flow_type' => 'static',
]);

// Delete existing default options and parse new menu text to create options
$rootFlow->options()->delete(); // Remove default options
$rootFlow->parseMenuTextToOptions(); // Create new options from menu text
```

**This automatically creates 6 options:**
- Option 1 → Check Balance
- Option 2 → Send Money
- Option 3 → Buy Airtime
- Option 4 → Transaction History
- Option 5 → Change PIN
- Option 6 → Account Info
- Option 0 → Exit

### Step 3: Create "Check Balance" Flow

```php
// Create balance check flow
$balanceFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'check_balance',
    'title' => 'Account Balance',
    'menu_text' => 'Loading balance...',
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'static',
]);

// Create option that triggers API call
USSDFlowOption::create([
    'flow_id' => $rootFlow->id,
    'option_text' => 'Check Balance',
    'option_value' => '1',
    'action_type' => 'api_call',
    'action_data' => [
        'api_configuration_id' => $balanceApiConfig->id, // External API config
        'success_message' => 'Your balance is: {{api_response.balance}}',
        'error_message' => 'Unable to fetch balance. Please try again.',
        'next_flow_id' => $rootFlow->id, // Return to main menu
    ],
    'sort_order' => 1,
    'is_active' => true,
]);
```

### Step 4: Create "Send Money" Flow (Multi-Step with Input Collection)

**Step 4a: Create Amount Input Flow**

```php
$amountInputFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'send_money_amount',
    'title' => 'Send Money',
    'menu_text' => 'Enter amount to send:',
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'static',
]);

// Create option in root flow that requests amount input
USSDFlowOption::create([
    'flow_id' => $rootFlow->id,
    'option_text' => 'Send Money',
    'option_value' => '2',
    'action_type' => 'input_amount',
    'action_data' => [
        'input_prompt' => 'Enter amount to send:',
        'input_validation' => [
            'min' => 1,
            'max' => 10000,
            'type' => 'number',
        ],
        'store_as' => 'amount',
        'next_flow_id' => $phoneInputFlow->id, // Next: collect phone number
    ],
    'sort_order' => 2,
    'is_active' => true,
]);
```

**Step 4b: Create Phone Number Input Flow**

```php
$phoneInputFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'send_money_phone',
    'title' => 'Send Money',
    'menu_text' => 'Enter recipient phone number:',
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'static',
]);

// This flow is automatically triggered after amount is collected
// Create option that requests phone input
USSDFlowOption::create([
    'flow_id' => $amountInputFlow->id,
    'option_text' => 'Continue',
    'option_value' => '*', // Any input continues
    'action_type' => 'input_phone',
    'action_data' => [
        'input_prompt' => 'Enter recipient phone number:',
        'input_validation' => [
            'min_length' => 10,
            'max_length' => 15,
            'pattern' => '^[0-9+]+$',
        ],
        'store_as' => 'recipient_phone',
        'next_flow_id' => $pinInputFlow->id, // Next: collect PIN
    ],
    'sort_order' => 1,
    'is_active' => true,
]);
```

**Step 4c: Create PIN Input Flow**

```php
$pinInputFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'send_money_pin',
    'title' => 'Send Money',
    'menu_text' => 'Enter your PIN:',
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'static',
]);

USSDFlowOption::create([
    'flow_id' => $phoneInputFlow->id,
    'option_text' => 'Continue',
    'option_value' => '*',
    'action_type' => 'input_pin',
    'action_data' => [
        'input_prompt' => 'Enter your PIN:',
        'input_validation' => [
            'length' => 4,
            'type' => 'numeric',
        ],
        'store_as' => 'pin',
        'next_flow_id' => $confirmFlow->id, // Next: confirmation
    ],
    'sort_order' => 1,
    'is_active' => true,
]);
```

**Step 4d: Create Confirmation Flow**

```php
$confirmFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'send_money_confirm',
    'title' => 'Confirm Transaction',
    'menu_text' => "Confirm transaction:\n\nAmount: {{session.amount}}\nTo: {{session.recipient_phone}}\n\n1. Confirm\n2. Cancel",
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'static',
]);

// Confirm option
USSDFlowOption::create([
    'flow_id' => $confirmFlow->id,
    'option_text' => 'Confirm',
    'option_value' => '1',
    'action_type' => 'api_call',
    'action_data' => [
        'api_configuration_id' => $sendMoneyApiConfig->id,
        'success_message' => 'Money sent successfully!\n\nAmount: {{session.amount}}\nTo: {{session.recipient_phone}}\nTransaction ID: {{api_response.transaction_id}}',
        'error_message' => 'Transaction failed: {{api_response.error}}',
        'next_flow_id' => $rootFlow->id,
    ],
    'sort_order' => 1,
    'is_active' => true,
]);

// Cancel option
USSDFlowOption::create([
    'flow_id' => $confirmFlow->id,
    'option_text' => 'Cancel',
    'option_value' => '2',
    'action_type' => 'navigate',
    'action_data' => [
        'message' => 'Transaction cancelled.',
    ],
    'next_flow_id' => $rootFlow->id,
    'sort_order' => 2,
    'is_active' => true,
]);
```

### Step 5: Create "Transaction History" Flow (Dynamic)

This demonstrates dynamic flows that fetch data from APIs:

```php
$historyFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'transaction_history',
    'title' => 'Transaction History',
    'menu_text' => 'Loading transactions...',
    'is_root' => false,
    'is_active' => true,
    'flow_type' => 'dynamic', // Dynamic flow!
    'dynamic_config' => [
        'api_configuration_id' => $historyApiConfig->id,
        'response_path' => 'data.transactions', // JSON path to array
        'item_label_template' => '{{item.date}} - {{item.type}} - {{item.amount}}',
        'item_value_template' => '{{item.id}}',
        'items_per_page' => 5,
        'empty_message' => 'No transactions found.',
        'pagination_enabled' => true,
        'next_flow_id' => $transactionDetailFlow->id, // Show details when selected
    ],
]);

// Link from main menu
USSDFlowOption::create([
    'flow_id' => $rootFlow->id,
    'option_text' => 'Transaction History',
    'option_value' => '4',
    'action_type' => 'navigate',
    'next_flow_id' => $historyFlow->id,
    'sort_order' => 4,
    'is_active' => true,
]);
```

### Step 6: Create External API Configuration

```php
$balanceApiConfig = ExternalAPIConfiguration::create([
    'ussd_id' => $ussd->id,
    'name' => 'Balance Check API',
    'api_url' => 'https://api.mobilemoney.com/v1/balance',
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer {{api_key}}',
    ],
    'request_body' => [
        'phone_number' => '{{session.phone_number}}',
        'account_id' => '{{session.account_id}}',
    ],
    'response_mapping' => [
        'balance' => 'data.balance',
        'currency' => 'data.currency',
    ],
    'success_condition' => 'response.status === "success"',
    'timeout' => 10,
    'retry_count' => 2,
]);
```

### Step 7: Complete Flow Diagram

```
Root Flow (Main Menu)
│
├─ 1. Check Balance
│   └─ API Call → Display Balance → Return to Main Menu
│
├─ 2. Send Money
│   ├─ Input Amount Flow
│   │   └─ Input Phone Flow
│   │       └─ Input PIN Flow
│   │           └─ Confirmation Flow
│   │               ├─ 1. Confirm → API Call → Success Message → Main Menu
│   │               └─ 2. Cancel → Main Menu
│
├─ 3. Buy Airtime
│   ├─ Input Amount Flow
│   │   └─ Input Phone Flow (optional - can use own number)
│   │       └─ Confirmation Flow
│   │           └─ API Call → Success → Main Menu
│
├─ 4. Transaction History (Dynamic)
│   ├─ API Call → Display List (paginated)
│   │   ├─ Select Transaction → Detail Flow
│   │   ├─ Next Page → More Transactions
│   │   └─ Back → Main Menu
│
├─ 5. Change PIN
│   ├─ Input Current PIN
│   │   └─ Input New PIN
│   │       └─ Confirm New PIN
│   │           └─ API Call → Success → Main Menu
│
└─ 6. Account Info
    └─ API Call → Display Info → Main Menu
```

---

## Advanced Features

### 1. Template Variables

Use template variables in flow text and API configurations:

**In Flow Menu Text:**
```
"Welcome {{session.phone_number}}!\nYour balance is: {{api_response.balance}}"
```

**In API Request Body:**
```json
{
  "amount": "{{session.amount}}",
  "recipient": "{{session.recipient_phone}}",
  "sender": "{{session.phone_number}}"
}
```

**Available Variables:**
- `{{session.*}}` - Any data stored in session
- `{{api_response.*}}` - Data from last API call
- `{{session.phone_number}}` - User's phone number
- `{{session.amount}}` - Collected amount
- `{{session.recipient_phone}}` - Collected phone number

### 2. Input Validation

```php
'input_validation' => [
    'type' => 'number', // number, text, phone, email, pin
    'min' => 1,
    'max' => 10000,
    'min_length' => 10,
    'max_length' => 15,
    'pattern' => '^[0-9+]+$',
    'required' => true,
]
```

### 3. Conditional Navigation

```php
'action_data' => [
    'conditions' => [
        [
            'field' => 'session.balance',
            'operator' => '>=',
            'value' => 100,
            'next_flow_id' => $successFlow->id,
        ],
        [
            'field' => 'session.balance',
            'operator' => '<',
            'value' => 100,
            'next_flow_id' => $insufficientFundsFlow->id,
        ],
    ],
]
```

### 4. Dynamic Flow Pagination

Dynamic flows automatically support pagination:

```php
'dynamic_config' => [
    'items_per_page' => 5,
    'pagination_enabled' => true,
    // System automatically adds "Next" and "Back" options
]
```

### 5. Error Handling

```php
'action_data' => [
    'api_configuration_id' => $apiConfig->id,
    'success_message' => 'Transaction successful!',
    'error_message' => 'Transaction failed. Please try again.',
    'timeout_message' => 'Request timed out. Please try again.',
    'retry_count' => 2,
    'next_flow_id' => $rootFlow->id, // Always return to main menu on error
]
```

---

## Common Complications & Solutions

### 1. **Session Timeout Issues**

**Problem:** Users take too long between steps, session expires.

**Solution:**
- Set appropriate `expires_at` (default: 30 minutes)
- Implement session refresh on each interaction
- Show timeout warnings: "Session will expire in 2 minutes"

```php
// In USSDSessionService
$session->update([
    'expires_at' => now()->addMinutes(30),
    'last_activity' => now(),
]);
```

### 2. **API Timeout During USSD Session**

**Problem:** External API takes too long, USSD session times out (typically 2-3 minutes).

**Solution:**
- Set API timeout to 5-10 seconds max
- Use async processing for long operations
- Show "Processing..." message immediately
- Store transaction and send SMS confirmation later

```php
'action_data' => [
    'api_configuration_id' => $apiConfig->id,
    'timeout' => 5, // 5 seconds max
    'async_processing' => true, // For long operations
]
```

### 3. **Cumulative Input from AfricasTalking**

**Problem:** AfricasTalking sends cumulative input like "1*1*1*2" instead of just "2".

**Solution:** System already handles this with `extractLastSelection()`:

```php
// In USSDSessionService
private function extractLastSelection(string $input): string
{
    $parts = explode('*', $input);
    return end($parts) ?: $input;
}
```

### 4. **Dynamic Flow API Failures**

**Problem:** API fails, dynamic menu can't be generated.

**Solution:**
- Implement fallback to cached data
- Show error message with retry option
- Log failures for monitoring

```php
'dynamic_config' => [
    'api_configuration_id' => $apiConfig->id,
    'fallback_to_cache' => true,
    'error_message' => 'Unable to load data. Please try again.',
    'retry_flow_id' => $historyFlow->id, // Allow retry
]
```

### 5. **Input Validation Errors**

**Problem:** User enters invalid data (wrong format, out of range).

**Solution:**
- Clear validation messages
- Allow retry with same input flow
- Show examples: "Enter amount (1-10000)"

```php
'input_validation' => [
    'type' => 'number',
    'min' => 1,
    'max' => 10000,
    'error_message' => 'Invalid amount. Enter between 1 and 10000.',
    'example' => 'Example: 500',
]
```

### 6. **Large Dynamic Lists**

**Problem:** API returns 100+ items, can't show all in USSD.

**Solution:**
- Implement pagination (5-10 items per page)
- Add search/filter functionality
- Use "Next" and "Back" navigation

```php
'dynamic_config' => [
    'items_per_page' => 5,
    'pagination_enabled' => true,
    'max_items' => 50, // Limit total items
]
```

### 7. **Session Data Loss**

**Problem:** Collected input data gets lost between flows.

**Solution:**
- Always store in `session_data` JSON field
- Use consistent keys: `amount`, `recipient_phone`, `pin`
- Verify data before API calls

```php
// Store collected data
$sessionData = $session->session_data ?? [];
$sessionData['amount'] = $input;
$session->update(['session_data' => $sessionData]);

// Retrieve later
$amount = $session->session_data['amount'] ?? null;
```

### 8. **Concurrent Sessions**

**Problem:** User starts new session while old one is active.

**Solution:**
- Check for existing active sessions
- End old session or merge data
- Prevent duplicate transactions

```php
// In startSession()
$existingSession = USSDSession::where('ussd_id', $ussd->id)
    ->where('phone_number', $phoneNumber)
    ->where('status', 'active')
    ->where('expires_at', '>', now())
    ->first();

if ($existingSession) {
    // End old session
    $existingSession->update(['status' => 'expired']);
}
```

### 9. **API Response Format Changes**

**Problem:** External API changes response structure.

**Solution:**
- Use flexible response mapping
- Test API responses regularly
- Implement versioning for API configs
- Add response validation

```php
'response_mapping' => [
    'balance' => 'data.balance', // Flexible path
    'currency' => 'data.currency',
],
'response_validation' => [
    'required_fields' => ['data.balance'],
    'type_check' => ['data.balance' => 'numeric'],
]
```

### 10. **USSD Character Limits**

**Problem:** USSD messages limited to ~160 characters.

**Solution:**
- Keep messages concise
- Split long content across multiple flows
- Use abbreviations: "Bal" instead of "Balance"
- Paginate long lists

```php
// Keep messages short
$flow->menu_text = "Bal: {{api_response.balance}}\n1. Send\n2. Buy\n0. Back";

// Split long content
if (strlen($message) > 150) {
    // Split into multiple flows
}
```

---

## Best Practices

### 1. **Flow Design**

- ✅ Keep menu options to 5-7 items max
- ✅ Use clear, concise text
- ✅ Number options consistently (1, 2, 3...)
- ✅ Always provide "Back" or "0. Exit" option
- ✅ Group related functions together

### 2. **Input Collection**

- ✅ Collect one piece of data per flow
- ✅ Show what you're collecting: "Enter amount:"
- ✅ Provide examples: "Example: 500"
- ✅ Validate immediately
- ✅ Allow cancellation at any step

### 3. **Error Messages**

- ✅ Be specific: "Amount must be between 1 and 10000"
- ✅ Provide next steps: "Please try again or press 0 to cancel"
- ✅ Don't expose technical errors to users
- ✅ Log all errors for debugging

### 4. **API Integration**

- ✅ Set reasonable timeouts (5-10 seconds)
- ✅ Implement retry logic
- ✅ Cache responses when possible
- ✅ Handle all error scenarios
- ✅ Test with real APIs in testing environment

### 5. **Session Management**

- ✅ Refresh session on each interaction
- ✅ Store all collected data
- ✅ Clear sensitive data (PINs) after use
- ✅ Set appropriate expiration times
- ✅ Handle session conflicts

### 6. **Testing**

- ✅ Test all flows end-to-end
- ✅ Test error scenarios
- ✅ Test with slow/failing APIs
- ✅ Test input validation
- ✅ Test session expiration
- ✅ Test concurrent sessions

### 7. **Security**

- ✅ Never log PINs or passwords
- ✅ Encrypt sensitive session data
- ✅ Validate all user input
- ✅ Sanitize API responses
- ✅ Use HTTPS for API calls
- ✅ Implement rate limiting

### 8. **Performance**

- ✅ Minimize API calls
- ✅ Cache frequently accessed data
- ✅ Use pagination for large lists
- ✅ Optimize database queries
- ✅ Monitor response times

---

## Example: Complete Mobile Money Service Implementation

### Database Seeding Example

```php
// Create USSD Service
$ussd = USSD::create([...]);

// Create Root Flow
$rootFlow = USSDFlow::create([...]);
$rootFlow->parseMenuTextToOptions();

// Create Check Balance Flow
$balanceFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'check_balance',
    'title' => 'Account Balance',
    'menu_text' => 'Checking balance...',
    'is_root' => false,
    'flow_type' => 'static',
]);

// Update root option 1 to call balance API
$option1 = USSDFlowOption::where('flow_id', $rootFlow->id)
    ->where('option_value', '1')
    ->first();
$option1->update([
    'action_type' => 'api_call',
    'action_data' => [
        'api_configuration_id' => $balanceApiConfig->id,
        'success_message' => 'Balance: {{api_response.balance}} {{api_response.currency}}',
        'next_flow_id' => $rootFlow->id,
    ],
]);

// Create Send Money Flows (Amount → Phone → PIN → Confirm)
// ... (as shown in Step 4 above)

// Create Transaction History (Dynamic Flow)
$historyFlow = USSDFlow::create([
    'ussd_id' => $ussd->id,
    'name' => 'transaction_history',
    'title' => 'Transaction History',
    'menu_text' => 'Loading...',
    'flow_type' => 'dynamic',
    'dynamic_config' => [
        'api_configuration_id' => $historyApiConfig->id,
        'response_path' => 'data.transactions',
        'item_label_template' => '{{item.date}} - {{item.type}} - {{item.amount}}',
        'items_per_page' => 5,
        'pagination_enabled' => true,
    ],
]);
```

### Testing the Service

1. **Start Session:**
   ```
   User dials: *123#
   System: Shows main menu
   ```

2. **Check Balance:**
   ```
   User selects: 1
   System: Calls API → Shows balance → Returns to main menu
   ```

3. **Send Money:**
   ```
   User selects: 2
   System: "Enter amount:"
   User enters: 500
   System: "Enter recipient phone:"
   User enters: 08012345678
   System: "Enter PIN:"
   User enters: 1234
   System: Shows confirmation
   User selects: 1 (Confirm)
   System: Calls API → Shows success → Returns to main menu
   ```

4. **Transaction History:**
   ```
   User selects: 4
   System: Calls API → Shows 5 transactions
   User selects: 1 (First transaction)
   System: Shows details
   User selects: 0 (Back)
   System: Shows main menu
   ```

---

## Conclusion

This guide covers building a complete, production-ready USSD service with:

- ✅ Static and dynamic flows
- ✅ Input collection and validation
- ✅ External API integration
- ✅ Error handling
- ✅ Session management
- ✅ Pagination
- ✅ Template variables

The Mobile Money Transfer example demonstrates all major features and patterns you'll need for any USSD application.

**Next Steps:**
1. Start with a simple flow (main menu)
2. Add one feature at a time
3. Test thoroughly at each step
4. Monitor logs and user feedback
5. Iterate and improve

For questions or issues, check the logs in `storage/logs/laravel.log` and session data in the `ussd_sessions` table.

