# USSD Testing Guide - Free Testing Options

## Overview

You have **multiple free options** to test your USSD implementation from development to production. Here's a complete guide:

## 🆓 Free Testing Options

### 1. **AfricasTalking Sandbox (FREE - Recommended)**

AfricasTalking provides a **completely free sandbox environment** for testing.

#### Features:
- ✅ **100% Free** - No charges for sandbox usage
- ✅ **Real USSD Codes** - Get actual USSD codes for testing
- ✅ **Real Phone Testing** - Test with actual phone numbers
- ✅ **Full API Access** - All features available
- ✅ **Production-like** - Same format as production

#### Setup Steps:

1. **Sign Up for Sandbox**:
   - Visit: https://sandbox.africastalking.com/
   - Create a free account
   - No credit card required

2. **Get Sandbox Credentials**:
   - Login to sandbox dashboard
   - Go to **Settings** → **API Key**
   - Copy your **Sandbox API Key** and **Username**

3. **Configure in Your App**:
   ```env
   AFRICASTALKING_API_KEY=your_sandbox_api_key
   AFRICASTALKING_USERNAME=your_sandbox_username
   AFRICASTALKING_ENVIRONMENT=sandbox
   ```

4. **Create USSD Application in Sandbox**:
   - Go to **USSD** → **Create Application**
   - **Service Code**: Choose a code (e.g., `*384*123#`)
   - **Callback URL**: `https://yourdomain.com/api/ussd/gateway`
     - For local testing, use: `https://your-ngrok-url.ngrok.io/api/ussd/gateway`
   - **Callback Method**: POST

5. **Test with Real Phone**:
   - Dial the USSD code from your phone
   - Test all flows and interactions
   - Monitor in your dashboard

#### AfricasTalking Simulator:
- **URL**: https://simulator.africastalking.com:1517/
- **Purpose**: Test without using a real phone
- **Features**: Simulates user interactions

#### Limitations:
- ⚠️ Sandbox is for testing only
- ⚠️ Cannot be used for production traffic
- ⚠️ Some features may have limitations

---

### 2. **Your Built-in Simulator (FREE)**

Your application already has a **built-in USSD simulator**!

#### Access:
- **Route**: `/ussd/{ussd}/simulator`
- **URL**: `http://yourdomain.com/ussd/{ussd_id}/simulator`
- **Features**:
  - Visual phone interface
  - Test all flows
  - View session logs
  - Analytics
  - Multiple environment modes (simulation, testing, production)

#### How to Use:

1. **Navigate to Simulator**:
   - Go to your USSD service page
   - Click "Simulator" or "Test" button
   - Or visit: `/ussd/{ussd_id}/simulator`

2. **Start Testing**:
   - Enter phone number
   - Select environment (Simulation/Testing/Production)
   - Start session
   - Interact with your USSD flows

3. **Test Different Scenarios**:
   - Test menu navigation
   - Test input collection
   - Test API calls
   - Test error handling

#### Advantages:
- ✅ No external service needed
- ✅ Test locally
- ✅ Full control
- ✅ Debug easily
- ✅ View logs in real-time

---

### 3. **Local Test Endpoint (FREE)**

Your app has a test endpoint for development.

#### Access:
- **URL**: `GET /api/ussd/test`
- **Only works in**: `local` or `testing` environment

#### Usage:
```bash
# Test first request (empty text)
curl "http://localhost:8000/api/ussd/test?sessionId=test123&serviceCode=*123#&phoneNumber=+2348012345678&text="

# Test menu selection
curl "http://localhost:8000/api/ussd/test?sessionId=test123&serviceCode=*123#&phoneNumber=+2348012345678&text=1"

# Test multi-step
curl "http://localhost:8000/api/ussd/test?sessionId=test123&serviceCode=*123#&phoneNumber=+2348012345678&text=1*2"
```

#### Response Format:
```json
{
  "request": {
    "sessionId": "test123",
    "serviceCode": "*123#",
    "phoneNumber": "+2348012345678",
    "text": "1"
  },
  "response": {
    "response": "CON",
    "message": "Welcome!\n1. Option 1\n2. Option 2",
    "freeFlow": "FC"
  }
}
```

---

### 4. **Ngrok for Local Testing (FREE)**

Test your local server with AfricasTalking sandbox.

#### Setup:

1. **Install Ngrok**:
   ```bash
   # Download from https://ngrok.com/
   # Or use: npm install -g ngrok
   ```

2. **Start Your Laravel Server**:
   ```bash
   php artisan serve
   # Server runs on http://localhost:8000
   ```

3. **Start Ngrok Tunnel**:
   ```bash
   ngrok http 8000
   # You'll get: https://abc123.ngrok.io
   ```

4. **Configure AfricasTalking Sandbox**:
   - Use callback URL: `https://abc123.ngrok.io/api/ussd/gateway`
   - Now AfricasTalking can reach your local server!

#### Advantages:
- ✅ Test locally with real AfricasTalking
- ✅ No deployment needed
- ✅ Free tier available
- ✅ Real-time testing

---

## Testing Workflow (Recommended)

### Phase 1: Local Development
1. Use **Built-in Simulator** (`/ussd/{id}/simulator`)
2. Test all flows and logic
3. Fix any bugs

### Phase 2: Local with AfricasTalking Format
1. Use **Test Endpoint** (`/api/ussd/test`)
2. Verify request/response format
3. Test with curl or Postman

### Phase 3: Sandbox Testing
1. Set up **AfricasTalking Sandbox** account
2. Configure webhook (use Ngrok for local)
3. Test with **real phone** or **AfricasTalking Simulator**
4. Verify end-to-end flow

### Phase 4: Production Testing
1. Switch to **AfricasTalking Live** account
2. Configure production USSD code
3. Test with real phone
4. Monitor and optimize

---

## Quick Start: AfricasTalking Sandbox

### Step-by-Step:

1. **Sign Up**:
   ```
   https://sandbox.africastalking.com/
   ```

2. **Get Credentials**:
   - API Key: Found in Dashboard → Settings
   - Username: Your sandbox username

3. **Configure Your App**:
   ```env
   AFRICASTALKING_API_KEY=your_sandbox_key
   AFRICASTALKING_USERNAME=sandbox_username
   AFRICASTALKING_ENVIRONMENT=sandbox
   ```

4. **Create USSD Application**:
   - Dashboard → USSD → Create Application
   - Service Code: `*384*123#` (or your choice)
   - Callback URL: `https://yourdomain.com/api/ussd/gateway`
   - Save

5. **Test**:
   - Dial the code from your phone
   - Or use: https://simulator.africastalking.com:1517/

---

## Cost Comparison

| Option | Cost | Best For |
|--------|------|----------|
| **Built-in Simulator** | FREE | Local development, debugging |
| **Test Endpoint** | FREE | API format testing |
| **AfricasTalking Sandbox** | FREE | Integration testing, real phone testing |
| **Ngrok** | FREE (tier) | Local testing with external services |
| **AfricasTalking Live** | Pay per session | Production |

---

## Testing Checklist

### Before Going Live:

- [ ] Test all flows in built-in simulator
- [ ] Test with local test endpoint
- [ ] Set up AfricasTalking sandbox account
- [ ] Configure sandbox USSD application
- [ ] Test with real phone (sandbox)
- [ ] Test with AfricasTalking simulator
- [ ] Verify webhook receives requests
- [ ] Verify responses are formatted correctly
- [ ] Test error scenarios
- [ ] Test session timeout
- [ ] Monitor logs
- [ ] Test production environment

---

## Troubleshooting

### Sandbox Not Working?
- ✅ Verify API credentials are correct
- ✅ Check webhook URL is accessible (use Ngrok if local)
- ✅ Ensure USSD code matches in dashboard and database
- ✅ Check AfricasTalking dashboard for errors

### Webhook Not Receiving Requests?
- ✅ Verify URL is publicly accessible
- ✅ Check SSL certificate (HTTPS required)
- ✅ Test with health check: `GET /api/ussd/health`
- ✅ Check server logs for incoming requests

### Response Format Issues?
- ✅ Verify response starts with "CON " or "END "
- ✅ Check Content-Type is `text/plain`
- ✅ Test with local test endpoint first

---

## Resources

- **AfricasTalking Sandbox**: https://sandbox.africastalking.com/
- **AfricasTalking Simulator**: https://simulator.africastalking.com:1517/
- **AfricasTalking Docs**: https://docs.africastalking.com
- **Ngrok**: https://ngrok.com/

---

## Summary

**✅ You can test completely FREE using:**
1. Your built-in simulator (no setup needed)
2. Local test endpoint (for API testing)
3. AfricasTalking Sandbox (for real phone testing)
4. Ngrok (for local + sandbox integration)

**No payment required until you go to production!**

