# UserRole Enum Documentation

## Overview

The `UserRole` enum provides type-safe role management for the USSD application. It centralizes role definitions and provides helper methods for role-based operations.

## Enum Values

```php
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
}
```

## Features

### 1. Type Safety
- Prevents typos in role names
- IDE autocomplete support
- Compile-time error checking

### 2. Helper Methods

#### Display Information
```php
$role = UserRole::ADMIN;
echo $role->displayName(); // "Administrator"
echo $role->description(); // "Full system administrator with all permissions"
```

#### Role Classification
```php
$role = UserRole::ADMIN;
$role->isAdmin(); // true
$role->isUser(); // false

UserRole::USER->isUser(); // true
UserRole::MODERATOR->isAdmin(); // true (moderator is considered admin)
```

#### Array Conversions
```php
UserRole::toArray(); // ['admin', 'user', 'moderator']
UserRole::toArrayWithDisplayNames(); // Array with value, display_name, description
UserRole::adminRoles(); // [ADMIN, MODERATOR]
UserRole::userRoles(); // [USER]
```

## Usage Examples

### 1. In Controllers
```php
use App\Enums\UserRole;

// Check user role
if ($user->hasRole(UserRole::ADMIN->value)) {
    // Admin logic
}

// Using enum-based methods
if ($user->hasRoleEnum(UserRole::ADMIN)) {
    // Admin logic
}

if ($user->isAdmin()) {
    // Admin or moderator logic
}
```

### 2. In Seeders
```php
use App\Enums\UserRole;

$user->assignRole(UserRole::ADMIN->value);
// or
$user->assignRoleEnum(UserRole::ADMIN);
```

### 3. In Queries
```php
// Count users with specific role
$userCount = User::whereHas('roles', function($query) {
    $query->where('name', UserRole::USER->value);
})->count();

// Get admin users
$adminUsers = User::whereHas('roles', function($query) {
    $query->whereIn('name', UserRole::adminRoles());
})->get();
```

### 4. In Middleware
```php
if (!$user->hasRole(UserRole::ADMIN->value)) {
    abort(403, 'Access denied');
}
```

## User Model Integration

The `User` model includes enum-based helper methods:

```php
// Enum-based role checking
$user->hasRoleEnum(UserRole::ADMIN);
$user->isAdmin(); // true for admin or moderator
$user->isRegularUser(); // true for user role only

// Enum-based role assignment
$user->assignRoleEnum(UserRole::ADMIN);
$user->removeRoleEnum(UserRole::MODERATOR);
```

## Benefits

### 1. Consistency
- All role references use the same enum
- No hardcoded strings scattered throughout code
- Centralized role definitions

### 2. Maintainability
- Easy to add new roles
- Easy to modify role properties
- Clear documentation of available roles

### 3. Type Safety
- Prevents runtime errors from typos
- IDE support for autocomplete
- Refactoring support

### 4. Performance
- Enum values are cached
- No database queries for role validation
- Efficient role checking

## Migration from String-based Roles

### Before (String-based)
```php
$user->hasRole('admin');
$user->assignRole('admin');
if ($role === 'admin') { ... }
```

### After (Enum-based)
```php
$user->hasRoleEnum(UserRole::ADMIN);
$user->assignRoleEnum(UserRole::ADMIN);
if ($role === UserRole::ADMIN) { ... }
```

## Best Practices

### 1. Always Use Enum Values
```php
// ✅ Good
$user->hasRole(UserRole::ADMIN->value);

// ❌ Avoid
$user->hasRole('admin');
```

### 2. Use Enum-based Methods When Available
```php
// ✅ Good
$user->hasRoleEnum(UserRole::ADMIN);

// ❌ Avoid
$user->hasRole(UserRole::ADMIN->value);
```

### 3. Use Helper Methods for Common Checks
```php
// ✅ Good
if ($user->isAdmin()) { ... }

// ❌ Avoid
if ($user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::MODERATOR->value)) { ... }
```

## Future Enhancements

### 1. Permissions System
```php
enum Permission: string
{
    case MANAGE_USERS = 'manage_users';
    case APPROVE_BUSINESSES = 'approve_businesses';
    case VIEW_ANALYTICS = 'view_analytics';
}
```

### 2. Role Hierarchies
```php
public function hasHigherRoleThan(UserRole $otherRole): bool
{
    $hierarchy = [
        UserRole::ADMIN => 3,
        UserRole::MODERATOR => 2,
        UserRole::USER => 1,
    ];
    
    return $hierarchy[$this] > $hierarchy[$otherRole];
}
```

### 3. Dynamic Role Loading
```php
public static function fromDatabase(): array
{
    return Role::all()->map(fn($role) => self::from($role->name))->toArray();
}
```

## Troubleshooting

### 1. Enum Not Found
```bash
# Clear autoload cache
composer dump-autoload
```

### 2. Type Errors
```php
// Ensure proper import
use App\Enums\UserRole;

// Use correct syntax
UserRole::ADMIN->value // string
UserRole::ADMIN // enum instance
```

### 3. Database Consistency
```bash
# Ensure database roles match enum values
php artisan db:seed --class=RoleSeeder
```
