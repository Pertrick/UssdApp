# AfricasTalking Integration Guide

## Overview

This USSD SaaS application integrates with AfricasTalking for live USSD services. The integration uses direct API calls instead of SDK for better control and performance.

## Setup Instructions

### 1. Environment Variables

Add these variables to your `.env` file:

```env
# AfricasTalking Configuration
AFRICASTALKING_API_KEY=your_api_key_here
AFRICASTALKING_USERNAME=your_username_here
AFRICASTALKING_ENVIRONMENT=sandbox  # or 'live'
```

### 2. AfricasTalking Dashboard Setup

1. **Create Account**: Sign up at [AfricasTalking](https://africastalking.com)
2. **Get Credentials**: 
   - Go to Dashboard → API Key
   - Copy your API Key and Username
3. **Create USSD Application**:
   - Go to USSD → Create Application
   - Set callback URL: `https://yourdomain.com/api/ussd/gateway`
   - Note your USSD code (e.g., `*123#`)

### 3. Application Configuration

#### For Testing:
- Use sandbox environment
- USSD code: `*123#` (or your chosen code)
- Callback URL: `https://yourdomain.com/api/ussd/gateway`

#### For Production:
- Switch to live environment
- Use your assigned USSD code
- Ensure SSL certificate is valid

## API Endpoints

### USSD Gateway
- **URL**: `POST /api/ussd/gateway`
- **Purpose**: Main endpoint for processing USSD requests
- **Called by**: AfricasTalking when users dial USSD code

### Health Check
- **URL**: `GET /api/ussd/health`
- **Purpose**: Verify service is running
- **Response**: `OK`

### Test Endpoint (Development)
- **URL**: `GET /api/ussd/test`
- **Purpose**: Test USSD processing locally
- **Parameters**: `sessionId`, `serviceCode`, `phoneNumber`, `text`

## Request/Response Format

### Incoming Request (from AfricasTalking)
```json
{
  "sessionId": "unique_session_id",
  "serviceCode": "*123#",
  "phoneNumber": "+2348012345678",
  "text": "1*John*123456"
}
```

### Response (to AfricasTalking)
```
CON Welcome to our service!
1. Option 1
2. Option 2
0. Exit
```

## Production Workflow

### 1. Business Verification
- Business must be verified by admin
- Status: `verified`

### 2. Gateway Configuration
- Set gateway provider: `africastalking`
- Configure credentials in USSD settings
- Set webhook URL

### 3. Go Live Process
1. Test USSD flow in simulator
2. Configure AfricasTalking application
3. Click "Go Live" button
4. Service becomes available to users

### 4. Monitoring
- View analytics in dashboard
- Monitor session logs
- Check AfricasTalking dashboard for delivery reports

## Testing

### Local Testing
```bash
# Test USSD processing
curl "http://localhost:8000/api/ussd/test?sessionId=test123&serviceCode=*123#&phoneNumber=+2348012345678&text=1"
```

### AfricasTalking Sandbox
- Use sandbox environment for testing
- Test with real phone numbers
- Verify callback URL is accessible

## Error Handling

### Common Issues
1. **Invalid USSD Code**: Check pattern in database
2. **Callback URL Not Accessible**: Ensure SSL and public access
3. **Session Errors**: Check session management
4. **Gateway Errors**: Verify API credentials

### Logging
- All requests logged to Laravel logs
- Check `storage/logs/laravel.log`
- AfricasTalking dashboard for delivery status

## Security Considerations

1. **API Key Protection**: Store securely in environment variables
2. **HTTPS Required**: Production must use SSL
3. **Input Validation**: All inputs validated and sanitized
4. **Rate Limiting**: Implement if needed
5. **Session Management**: Secure session handling

## Pricing

### AfricasTalking Costs
- **SMS**: ~$0.01 per message
- **USSD**: ~$0.01 per session
- **API Calls**: Free (included in session cost)

### Your SaaS Pricing
- **Testing**: Free
- **Live**: Per session or subscription model
- **Enterprise**: Custom pricing

## Support

### AfricasTalking Support
- Documentation: [AfricasTalking Docs](https://docs.africastalking.com)
- Support: support@africastalking.com
- Status: [Status Page](https://status.africastalking.com)

### Application Support
- Check logs for errors
- Verify configuration
- Test with simulator first
- Contact support if issues persist

## Next Steps

1. **Set up AfricasTalking account**
2. **Configure environment variables**
3. **Test with simulator**
4. **Configure production USSD**
5. **Go live with verified business**
6. **Monitor and optimize**

## Troubleshooting

### USSD Not Responding
1. Check callback URL accessibility
2. Verify API credentials
3. Check application logs
4. Test with health check endpoint

### Session Issues
1. Verify session management
2. Check database connections
3. Review session logs
4. Test session flow

### Gateway Errors
1. Verify AfricasTalking credentials
2. Check API key permissions
3. Review AfricasTalking logs
4. Contact AfricasTalking support
