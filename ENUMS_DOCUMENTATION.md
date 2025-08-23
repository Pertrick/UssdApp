# Enums Documentation - USSD Application

## Overview

This document provides comprehensive documentation for all enum classes implemented in the USSD application. These enums provide type safety, consistency, and maintainability for various statuses and types throughout the application.

## Implemented Enums

### 1. UserRole Enum
**File**: `app/Enums/UserRole.php`

**Purpose**: Manages user roles and permissions in the system.

**Values**:
- `ADMIN` - Full system administrator
- `USER` - Regular user (excluded from admin stats)
- `MODERATOR` - Limited admin access

**Key Methods**:
- `displayName()` - Human-readable role names
- `isAdmin()` - Check if role is admin (admin or moderator)
- `isUser()` - Check if role is regular user
- `toArray()` - Get all role values
- `adminRoles()` - Get admin roles only

**Usage Examples**:
```php
// Check user role
if ($user->hasRole(UserRole::ADMIN->value)) {
    // Admin logic
}

// Using enum-based methods
if ($user->isAdmin()) {
    // Admin or moderator logic
}

// Assign role
$user->assignRoleEnum(UserRole::ADMIN);
```

### 2. BusinessRegistrationStatus Enum
**File**: `app/Enums/BusinessRegistrationStatus.php`

**Purpose**: Manages the business registration workflow status.

**Values**:
- `EMAIL_VERIFICATION_PENDING` - Initial status after business registration
- `CAC_INFO_PENDING` - After email verification, CAC info required
- `DIRECTOR_INFO_PENDING` - After CAC info, director info required
- `COMPLETED_UNVERIFIED` - Registration complete, awaiting admin verification
- `VERIFIED` - Business verified by admin
- `REJECTED` - Business verification rejected by admin

**Key Methods**:
- `displayName()` - Human-readable status names
- `colorClass()` - CSS classes for UI display
- `isPending()` - Check if status is pending
- `isCompleted()` - Check if status is completed
- `isVerified()` - Check if status is verified
- `nextStatus()` - Get next status in workflow
- `pendingStatuses()` - Get all pending statuses

**Usage Examples**:
```php
// Check business status
if ($business->registration_status->isPending()) {
    // Show pending message
}

// Move to next status
$nextStatus = $business->registration_status->nextStatus();
if ($nextStatus) {
    $business->update(['registration_status' => $nextStatus]);
}

// Get display info
$statusName = $business->registration_status->displayName();
$colorClass = $business->registration_status->colorClass();
```

### 3. BusinessType Enum
**File**: `app/Enums/BusinessType.php`

**Purpose**: Defines different types of business entities.

**Values**:
- `SOLE_PROPRIETORSHIP` - Single owner business
- `PARTNERSHIP` - Multiple partners business
- `LIMITED_LIABILITY` - Limited liability company

**Key Methods**:
- `displayName()` - Human-readable business type names
- `legalRequirements()` - Get required documents for this type
- `requiresMultipleOwners()` - Check if multiple owners required
- `hasLimitedLiability()` - Check if limited liability protection
- `toArrayWithDisplayNames()` - Get types with display names

**Usage Examples**:
```php
// Check business type requirements
if ($business->business_type->requiresMultipleOwners()) {
    // Show multiple owner fields
}

// Get legal requirements
$requirements = $business->business_type->legalRequirements();

// Display business type
$typeName = $business->business_type->displayName();
```

### 4. DirectorIdType Enum
**File**: `app/Enums/DirectorIdType.php`

**Purpose**: Defines valid identification document types for directors.

**Values**:
- `NATIONAL_ID` - Government-issued national ID
- `DRIVERS_LICENSE` - Valid driver's license
- `INTERNATIONAL_PASSPORT` - International passport

**Key Methods**:
- `displayName()` - Human-readable ID type names
- `validationRules()` - Get validation rules for this ID type
- `documentRequirements()` - Get document submission requirements
- `isInternationallyRecognized()` - Check if internationally recognized
- `toArrayWithDisplayNames()` - Get types with display names

**Usage Examples**:
```php
// Get validation rules
$rules = $business->director_id_type->validationRules();

// Check if internationally recognized
if ($business->director_id_type->isInternationallyRecognized()) {
    // Apply international validation
}

// Display ID type
$idTypeName = $business->director_id_type->displayName();
```

### 5. USSDActionType Enum
**File**: `app/Enums/USSDActionType.php`

**Purpose**: Defines different types of actions in USSD flows.

**Values**:
- `NAVIGATE` - Navigate to another USSD flow
- `MESSAGE` - Display a message to user
- `END_SESSION` - End the current USSD session
- `API_CALL` - Make an external API call
- `INPUT_COLLECTION` - Collect input from user

**Key Methods**:
- `displayName()` - Human-readable action type names
- `requiresInput()` - Check if action requires additional input
- `endsSession()` - Check if action ends the session
- `navigates()` - Check if action navigates to another flow
- `defaultActionData()` - Get default data structure for action
- `validationRules()` - Get validation rules for action data

**Usage Examples**:
```php
// Check action type
if ($option->action_type->requiresInput()) {
    // Show input collection form
}

if ($option->action_type->endsSession()) {
    // End session logic
}

// Get default data structure
$defaultData = $option->action_type->defaultActionData();
```

### 6. USSDSessionStatus Enum
**File**: `app/Enums/USSDSessionStatus.php`

**Purpose**: Manages USSD session lifecycle status.

**Values**:
- `ACTIVE` - Session is currently active
- `COMPLETED` - Session completed successfully
- `EXPIRED` - Session expired due to inactivity
- `TERMINATED` - Session was manually terminated
- `ERROR` - Session ended due to an error

**Key Methods**:
- `displayName()` - Human-readable status names
- `colorClass()` - CSS classes for UI display
- `isActive()` - Check if session is active
- `isEnded()` - Check if session is ended
- `endedSuccessfully()` - Check if session ended successfully
- `timeoutDuration()` - Get timeout duration for status
- `nextPossibleStatuses()` - Get possible next statuses

**Usage Examples**:
```php
// Check session status
if ($session->status->isActive()) {
    // Continue processing
}

if ($session->status->endedSuccessfully()) {
    // Show success message
}

// Get timeout duration
$timeout = $session->status->timeoutDuration();
```

## Model Integration

### Business Model
The Business model now includes enum casts and helper methods:

```php
protected $casts = [
    'registration_status' => BusinessRegistrationStatus::class,
    'business_type' => BusinessType::class,
    'director_id_type' => DirectorIdType::class,
];

// Helper methods
$business->isRegistrationPending();
$business->isRegistrationVerified();
$business->moveToNextRegistrationStatus();
$business->requiresMultipleOwners();
$business->getBusinessTypeDisplayName();
```

### User Model
The User model includes enum-based role methods:

```php
// Enum-based role checking
$user->hasRoleEnum(UserRole::ADMIN);
$user->isAdmin(); // true for admin or moderator
$user->isRegularUser(); // true for user role only

// Enum-based role assignment
$user->assignRoleEnum(UserRole::ADMIN);
$user->removeRoleEnum(UserRole::MODERATOR);
```

## Validation Integration

### Form Requests
Validation rules now use enum values:

```php
// StoreCACRequest
'businessType' => 'required|string|in:' . implode(',', BusinessType::toArray()),

// StoreDirectorRequest
'idType' => 'required|string|in:' . implode(',', DirectorIdType::toArray()),
```

### Database Migrations
Database enums match PHP enums:

```sql
-- Registration status enum
ENUM('email_verification_pending', 'cac_info_pending', 'director_info_pending', 'completed_unverified', 'verified', 'rejected')

-- Business type enum
ENUM('sole_proprietorship', 'partnership', 'limited_liability')

-- Director ID type enum
ENUM('national_id', 'drivers_license', 'international_passport')
```

## Frontend Integration

### Vue Components
Frontend components can use enum values for consistency:

```javascript
// Business type options
const businessTypes = [
    { value: 'sole_proprietorship', label: 'Sole Proprietorship' },
    { value: 'partnership', label: 'Partnership' },
    { value: 'limited_liability', label: 'Limited Liability Company' }
];

// Registration status colors
const statusColors = {
    'email_verification_pending': 'bg-yellow-100 text-yellow-800',
    'cac_info_pending': 'bg-orange-100 text-orange-800',
    'completed_unverified': 'bg-purple-100 text-purple-800',
    'verified': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
};
```

## Benefits Achieved

### 1. Type Safety
- ✅ No more typos in status/type strings
- ✅ IDE autocomplete support
- ✅ Compile-time error checking

### 2. Consistency
- ✅ All references use the same enum
- ✅ No hardcoded strings scattered throughout code
- ✅ Centralized definitions

### 3. Maintainability
- ✅ Easy to add new values
- ✅ Easy to modify properties
- ✅ Clear documentation of available values

### 4. Business Logic
- ✅ Helper methods for common operations
- ✅ Status transition logic
- ✅ Validation rules built into enums

### 5. UI Integration
- ✅ Color classes for status display
- ✅ Display names for user-friendly labels
- ✅ Consistent styling across the application

## Migration Guide

### Before (String-based)
```php
// Hardcoded strings everywhere
'registration_status' => 'cac_info_pending'
'business_type' => 'sole_proprietorship'
'director_id_type' => 'national_id'

// String comparisons
if ($status === 'pending') { ... }
if ($type === 'admin') { ... }
```

### After (Enum-based)
```php
// Type-safe enum values
'registration_status' => BusinessRegistrationStatus::CAC_INFO_PENDING
'business_type' => BusinessType::SOLE_PROPRIETORSHIP
'director_id_type' => DirectorIdType::NATIONAL_ID

// Enum comparisons
if ($status === BusinessRegistrationStatus::PENDING) { ... }
if ($type === UserRole::ADMIN) { ... }
```

## Best Practices

### 1. Always Use Enum Values
```php
// ✅ Good
$business->update(['registration_status' => BusinessRegistrationStatus::VERIFIED]);

// ❌ Avoid
$business->update(['registration_status' => 'verified']);
```

### 2. Use Enum Methods When Available
```php
// ✅ Good
if ($business->registration_status->isPending()) { ... }

// ❌ Avoid
if ($business->registration_status === 'pending') { ... }
```

### 3. Use Helper Methods for Common Checks
```php
// ✅ Good
if ($user->isAdmin()) { ... }

// ❌ Avoid
if ($user->hasRole('admin') || $user->hasRole('moderator')) { ... }
```

## Future Enhancements

### 1. Additional Enums
- `DocumentType` - For file upload types
- `VerificationStatus` - Enhanced verification system
- `NotificationType` - For system notifications

### 2. Enum Relationships
- Business type → Required documents
- Registration status → Required actions
- User role → Permissions

### 3. Dynamic Enum Loading
- Load enum values from database
- Cache enum values for performance
- Version control for enum changes

## Troubleshooting

### 1. Enum Not Found
```bash
# Clear autoload cache
composer dump-autoload
```

### 2. Type Errors
```php
// Ensure proper import
use App\Enums\BusinessRegistrationStatus;

// Use correct syntax
BusinessRegistrationStatus::VERIFIED->value // string
BusinessRegistrationStatus::VERIFIED // enum instance
```

### 3. Database Consistency
```bash
# Ensure database enums match PHP enums
php artisan migrate:fresh --seed
```

## Conclusion

The implementation of these enums significantly improves the codebase by providing:

1. **Type Safety** - Prevents runtime errors from typos
2. **Consistency** - Centralized definitions across the application
3. **Maintainability** - Easy to modify and extend
4. **Business Logic** - Built-in methods for common operations
5. **UI Integration** - Consistent display and styling

These enums serve as a foundation for a more robust and maintainable USSD application.
