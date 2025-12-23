# Go Live Process - Complete Guide

## Overview

When a user clicks "Go Live" in the Environment Management page, the system needs to:
1. Update the database to mark the service as live
2. Register the USSD code with AfricasTalking (or other gateway provider)
3. Configure webhooks to receive USSD requests
4. Verify the setup is working

## Current Implementation

### What Happens When "Go Live" is Clicked

1. **Frontend (Environment.vue)**
   - User clicks "Go Live" button
   - Modal confirmation is shown
   - `goLive()` function is called
   - Sends POST request to `/ussd/{ussd}/go-live`

2. **Backend (USSDController::goLive)**
   - Validates user ownership
   - Calls `EnvironmentManagementService::switchToProduction()`

3. **Service Layer (EnvironmentManagementService::switchToProduction)**
   - Validates all requirements are met
   - Performs safety checks (balance, credentials, webhook URL)
   - Calls `$ussd->goLive()` method
   - Updates environment_id to 'production'
   - Sets is_active to true
   - Logs the activity

### What's Currently Missing

The current implementation **only updates the database**. It does NOT:
- Register the USSD code with AfricasTalking
- Create the USSD application in AfricasTalking dashboard
- Verify the webhook URL is accessible
- Test the connection

## Required Steps

### 1. On AfricasTalking Dashboard (Admin Manual Steps)

**IMPORTANT**: USSD codes must be registered manually on AfricasTalking's platform. This cannot be done via API.

#### Steps for Admin:

1. **Login to AfricasTalking Dashboard**
   - Go to https://account.africastalking.com
   - Login with your credentials

2. **Navigate to USSD Section**
   - Go to **USSD** → **Applications**
   - Click **Create Application** or **Add USSD Service**

3. **Create USSD Application**
   - **Service Code**: Enter the USSD code (e.g., `*384*123#`)
     - This should match the `live_ussd_code` configured in the system
   - **Callback URL**: Enter your webhook URL
     - Format: `https://yourdomain.com/api/ussd/gateway/{ussd_id}`
     - Example: `https://api.example.com/api/ussd/gateway/27`
   - **Callback Method**: Select `POST`
   - **Session Timeout**: Set appropriate timeout (default: 60 seconds)

4. **Submit and Wait for Approval**
   - AfricasTalking may require approval for new USSD codes
   - This can take 24-48 hours for new codes
   - Existing codes are usually approved immediately

5. **Verify Application Status**
   - Check that the application shows as "Active"
   - Test the callback URL is reachable

### 2. Backend Implementation Needed

The backend needs to be enhanced to:

#### A. Verify AfricasTalking Registration

```php
// In EnvironmentManagementService::switchToProduction()
// After $ussd->goLive() succeeds:

// 1. Verify webhook URL is accessible
$webhookUrl = route('api.ussd.gateway', ['ussd' => $ussd->id]);
if (!$this->verifyWebhookAccessibility($webhookUrl)) {
    // Return error about webhook not being accessible
}

// 2. Send test request to verify AfricasTalking can reach us
// (Optional - can be done via health check endpoint)
```

#### B. Create Webhook Route

Ensure the webhook route exists in `routes/api.php`:

```php
Route::post('/ussd/gateway/{ussd}', [USSDGatewayController::class, 'handle'])
    ->name('api.ussd.gateway');
```

#### C. Add AfricasTalking Integration (Optional - for future automation)

If AfricasTalking provides an API to register USSD codes (they currently don't), you would add:

```php
// In AfricastalkingService.php
public function createUssdApplication(string $ussdCode, string $callbackUrl): array
{
    // Note: This API endpoint doesn't exist in AfricasTalking
    // USSD codes must be registered manually via dashboard
    // This is a placeholder for future implementation
    
    try {
        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($this->baseUrl . '/ussd/applications', [
            'serviceCode' => $ussdCode,
            'callbackUrl' => $callbackUrl
        ]);

        return [
            'success' => $response->successful(),
            'data' => $response->json()
        ];
    } catch (\Exception $e) {
        Log::error('Failed to create USSD application', [
            'error' => $e->getMessage(),
            'ussd_code' => $ussdCode
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

### 3. Post-Go-Live Verification

After going live, the system should:

1. **Send Notification to Admin**
   - Email/SMS notification that service is live
   - Include USSD code and webhook URL

2. **Create Test Session**
   - Optionally create a test session to verify everything works

3. **Monitor Health**
   - Set up monitoring for the webhook endpoint
   - Alert if webhook becomes unreachable

## Implementation Recommendations

### Immediate Actions Needed:

1. **Add Webhook Verification**
   - Before going live, verify webhook URL is accessible
   - Test that it responds correctly

2. **Add Admin Instructions Modal**
   - Show instructions after "Go Live" is clicked
   - List exact steps needed on AfricasTalking dashboard
   - Include the exact webhook URL to use

3. **Add Status Check**
   - After admin registers on AfricasTalking, add a way to verify
   - Could be a "Verify Connection" button that tests the webhook

4. **Add Webhook Route**
   - Ensure `/api/ussd/gateway/{ussd}` route exists and works
   - Handle AfricasTalking request format

### Future Enhancements:

1. **Automated Registration** (if AfricasTalking adds API)
   - Automatically register USSD code when going live
   - No manual steps required

2. **Status Monitoring**
   - Real-time status of AfricasTalking application
   - Webhook health checks

3. **Multi-Gateway Support**
   - Support for Hubtel, Twilio, etc.
   - Each with their own registration process

## Current Webhook Endpoint

The webhook should be accessible at:
```
POST https://yourdomain.com/api/ussd/gateway/{ussd_id}
```

Expected request format from AfricasTalking:
```json
{
  "sessionId": "ATUid_xxx",
  "serviceCode": "*384*123#",
  "phoneNumber": "+2348012345678",
  "text": "1*2*3"
}
```

Expected response format:
```
CON Welcome to our service!
1. Option 1
2. Option 2
0. Exit
```

Or:
```
END Thank you for using our service!
```

## Summary

**Current State:**
- ✅ Database is updated (environment_id, is_active)
- ✅ Requirements are validated
- ✅ Activity is logged
- ❌ AfricasTalking registration is NOT automated
- ❌ Webhook verification is NOT done
- ❌ Connection testing is NOT done

**Required Manual Steps:**
1. Admin must register USSD code on AfricasTalking dashboard
2. Admin must configure webhook URL on AfricasTalking
3. Admin must wait for approval (if new code)
4. Admin should test the connection

**Recommended Backend Enhancements:**
1. Add webhook URL verification before going live
2. Add connection testing after going live
3. Add admin instructions/guidance in the UI
4. Add status monitoring for webhook health

