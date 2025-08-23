# Verified Business Middleware

## Overview

The `VerifiedBusinessMiddleware` prevents users with pending business registrations from accessing USSD services, analytics, and activity features. This ensures that only verified businesses can create and manage USSD services.

## How It Works

### 1. Authentication Check
- Verifies that the user is authenticated
- Redirects to login if not authenticated

### 2. Business Check
- Checks if the user has a primary business registered
- Redirects to business registration if no business exists

### 3. Verification Status Check
- Checks the business registration status
- Only allows access if status is `VERIFIED`
- Provides specific error messages for different statuses

## Protected Routes

The middleware is applied to all USSD-related routes:

### USSD Management
- `/ussd` - USSD listing
- `/ussd/create` - Create new USSD
- `/ussd/{ussd}` - View USSD details
- `/ussd/{ussd}/edit` - Edit USSD
- `/ussd/{ussd}/configure` - Configure USSD flows
- `/ussd/{ussd}/simulator` - USSD simulator

### USSD Flow Management
- `/ussd/{ussd}/flows` - Manage USSD flows
- `/ussd/{ussd}/flows/{flow}/options` - Manage flow options

### Analytics & Activities
- `/analytics` - Analytics dashboard
- `/analytics/ussd/{ussd}` - USSD-specific analytics
- `/activities` - Activity logs

## Business Status Messages

The middleware provides specific error messages based on business status:

| Status | Message |
|--------|---------|
| `EMAIL_VERIFICATION_PENDING` | "Please complete email verification for your business." |
| `CAC_INFO_PENDING` | "Please complete CAC information for your business." |
| `DIRECTOR_INFO_PENDING` | "Please complete director information for your business." |
| `COMPLETED_UNVERIFIED` | "Your business is pending admin approval. You cannot access USSD services until approved." |
| `UNDER_REVIEW` | "Your business is currently under review. You cannot access USSD services until approved." |
| `REJECTED` | "Your business has been rejected. Please contact support for assistance." |
| `SUSPENDED` | "Your business has been suspended. Please contact support for assistance." |

## Implementation

### Middleware Registration
```php
// bootstrap/app.php
$middleware->alias([
    'verified.business' => \App\Http\Middleware\VerifiedBusinessMiddleware::class,
]);
```

### Route Application
```php
// routes/web.php
Route::middleware(['auth', 'verified.business'])->group(function () {
    // All USSD routes here
});
```

## User Flow

1. **User tries to access USSD service**
2. **Middleware checks business status**
3. **If verified**: Access granted
4. **If pending**: Redirected to dashboard with error message
5. **If no business**: Redirected to business registration

## Benefits

- **Security**: Prevents unauthorized USSD creation
- **Quality Control**: Ensures only verified businesses can use services
- **User Experience**: Clear error messages guide users through verification
- **Compliance**: Enforces business verification requirements

## Testing

To test the middleware:

1. **Create a user with pending business**
2. **Try to access `/ussd`**
3. **Should be redirected with appropriate error message**
4. **Verify business through admin panel**
5. **Try to access `/ussd` again**
6. **Should now have access**

## Error Handling

The middleware gracefully handles:
- Unauthenticated users
- Users without businesses
- All business registration statuses
- Provides clear, actionable error messages
