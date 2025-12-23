# Dynamic Authentication System Guide

## 🎯 Overview

The API Configuration Wizard now supports dynamic authentication based on the `auth_type` stored in the database. This system automatically handles different authentication methods without requiring manual header configuration.

## 🔧 Supported Authentication Types

### 1. **API Key Authentication**
```javascript
auth_type: 'api_key'
auth_config: {
  api_key: 'your-api-key-here',
  header_name: 'X-API-Key' // Optional, defaults to 'X-API-Key'
}
```

**Generated Headers:**
```javascript
{
  'X-API-Key': 'your-api-key-here'
}
```

### 2. **Bearer Token Authentication**
```javascript
auth_type: 'bearer_token'
auth_config: {
  bearer_token: 'your-bearer-token-here'
}
```

**Generated Headers:**
```javascript
{
  'Authorization': 'Bearer your-bearer-token-here'
}
```

### 3. **Basic Authentication**
```javascript
auth_type: 'basic'
auth_config: {
  username: 'your-username',
  password: 'your-password'
}
```

**Generated Headers:**
```javascript
{
  'Authorization': 'Basic base64-encoded-credentials'
}
```

### 4. **OAuth 2.0 Authentication**
```javascript
auth_type: 'oauth'
auth_config: {
  access_token: 'your-access-token',
  client_id: 'your-client-id', // Optional
  client_secret: 'your-client-secret' // Optional
}
```

**Generated Headers:**
```javascript
{
  'Authorization': 'Bearer your-access-token',
  'X-Client-ID': 'your-client-id' // If provided
}
```

### 5. **Custom Headers Authentication**
```javascript
auth_type: 'custom'
auth_config: {
  custom_headers: {
    'X-Custom-Header': 'custom-value',
    'X-Another-Header': 'another-value'
  }
}
```

**Generated Headers:**
```javascript
{
  'X-Custom-Header': 'custom-value',
  'X-Another-Header': 'another-value'
}
```

### 6. **No Authentication**
```javascript
auth_type: 'none'
// No auth_config needed
```

**Generated Headers:**
```javascript
{} // No authentication headers
```

## 🚀 How It Works

### 1. **Database Storage**
Authentication configuration is stored in the `external_api_configurations` table:

```sql
CREATE TABLE external_api_configurations (
  id BIGINT PRIMARY KEY,
  auth_type VARCHAR(50) DEFAULT 'api_key',
  auth_config JSON,
  -- other fields...
);
```

### 2. **Dynamic Header Generation**
The `buildAuthHeaders()` function automatically generates the correct headers based on the stored `auth_type`:

```javascript
const buildAuthHeaders = (apiConfig) => {
  const headers = {}
  
  if (!apiConfig.auth_type || apiConfig.auth_type === 'none') {
    return headers
  }

  const authConfig = apiConfig.auth_config || {}
  
  switch (apiConfig.auth_type) {
    case 'api_key':
      if (authConfig.header_name) {
        headers[authConfig.header_name] = authConfig.api_key
      } else {
        headers['X-API-Key'] = authConfig.api_key
      }
      break
      
    case 'bearer_token':
      headers['Authorization'] = `Bearer ${authConfig.bearer_token}`
      break
      
    // ... other cases
  }
  
  return headers
}
```

### 3. **Request Body Generation**
The `buildRequestBody()` function creates appropriate test payloads based on the API category:

```javascript
const buildRequestBody = (apiConfig) => {
  const category = apiConfig.marketplace_category || 'custom'
  
  switch (category) {
    case 'payment':
      return {
        amount: 1000,
        email: 'test@example.com',
        reference: `test_${Date.now()}`
      }
      
    case 'airtime':
      return {
        phone: '+2348012345678',
        amount: 100,
        network: 'MTN'
      }
      
    // ... other categories
  }
}
```

## 📱 User Experience

### 1. **API Selection**
- User selects an API from the marketplace
- System automatically detects the `auth_type` from the database
- Authentication configuration is loaded from `auth_config`

### 2. **Configuration Display**
- Authentication type is displayed in the configuration summary
- Required fields are shown with their status (configured/not set)
- Visual indicators show which fields are missing

### 3. **API Testing**
- Test button automatically applies the correct authentication
- Headers are generated dynamically based on `auth_type`
- Request body is created based on API category
- Success/failure feedback is provided

## 🔒 Security Features

### 1. **Secure Storage**
- Authentication credentials are stored encrypted in the database
- Sensitive fields (passwords, tokens) are masked in the UI
- No credentials are logged in console output

### 2. **Validation**
- Required fields are validated before API testing
- Authentication configuration is checked for completeness
- Error messages guide users to fix missing configurations

### 3. **Error Handling**
- Graceful handling of missing authentication configurations
- Clear error messages for authentication failures
- Fallback to no authentication if configuration is incomplete

## 🛠️ Implementation Details

### 1. **Frontend (Vue.js)**
```javascript
// Dynamic authentication header builder
const buildAuthHeaders = (apiConfig) => {
  // Implementation based on auth_type
}

// Request body builder
const buildRequestBody = (apiConfig) => {
  // Implementation based on API category
}

// Authentication field getter
const getAuthConfigFields = (authType) => {
  // Returns required fields for each auth type
}
```

### 2. **Backend (Laravel)**
```php
// Model with encrypted auth_config
class ExternalAPIConfiguration extends Model
{
    protected $casts = [
        'auth_config' => 'array',
    ];
    
    // Encryption/decryption handled automatically
}
```

### 3. **Database Schema**
```sql
-- Authentication types supported
auth_type ENUM(
  'api_key',
  'bearer_token', 
  'basic',
  'oauth',
  'custom',
  'none'
) DEFAULT 'api_key'

-- Encrypted authentication configuration
auth_config JSON
```

## 🧪 Testing

### 1. **API Connection Test**
```javascript
const testApiConnection = async () => {
  // Get dynamic authentication headers
  const authHeaders = buildAuthHeaders(selectedApi.value)
  
  // Build request configuration
  const requestConfig = {
    method: selectedApi.value.method,
    headers: {
      'Content-Type': 'application/json',
      ...authHeaders
    }
  }
  
  // Make API call with dynamic authentication
  const response = await fetch(selectedApi.value.endpoint_url, requestConfig)
}
```

### 2. **Authentication Validation**
- Check if required fields are present
- Validate authentication configuration completeness
- Test actual API connection with generated headers

## 📋 Configuration Examples

### Paystack API (Bearer Token)
```javascript
{
  auth_type: 'bearer_token',
  auth_config: {
    bearer_token: 'sk_test_...'
  }
}
```

### Custom API (API Key)
```javascript
{
  auth_type: 'api_key',
  auth_config: {
    api_key: 'your-api-key',
    header_name: 'X-API-Key'
  }
}
```

### Banking API (Basic Auth)
```javascript
{
  auth_type: 'basic',
  auth_config: {
    username: 'bank_username',
    password: 'bank_password'
  }
}
```

## 🎯 Benefits

1. **Dynamic Configuration**: No manual header configuration needed
2. **Type Safety**: Authentication is handled based on stored type
3. **Security**: Credentials are encrypted and masked
4. **Flexibility**: Supports multiple authentication methods
5. **User Experience**: Clear configuration status and testing
6. **Maintainability**: Centralized authentication logic

## 🚀 Future Enhancements

1. **OAuth 2.0 Flow**: Automatic token refresh
2. **JWT Support**: JSON Web Token authentication
3. **Certificate Auth**: Client certificate authentication
4. **Multi-Factor**: Support for MFA authentication
5. **Token Management**: Automatic token expiration handling

---

## 🎉 Conclusion

The dynamic authentication system provides a robust, secure, and user-friendly way to handle API authentication in the USSD platform. It automatically adapts to different authentication methods while maintaining security and providing clear feedback to users.
