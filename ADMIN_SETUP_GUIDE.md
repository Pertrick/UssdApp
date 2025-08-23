# Admin Setup Guide

## Overview

This guide explains how to set up and use the admin system for the USSD application.

## Features

### 1. Admin Seeder
- Creates multiple admin users with different roles
- Includes demo credentials for testing
- Prevents duplicate admin creation

### 2. Admin Authentication
- Separate admin login system
- Minimal and clean login interface
- Role-based access control
- Secure authentication flow

### 3. Admin Users Created

#### Super Admin
- **Email**: admin@ussd.com
- **Password**: password
- **Role**: admin
- **Permissions**: Full system access

#### System Admin
- **Email**: system@ussd.com
- **Password**: password
- **Role**: admin
- **Permissions**: Full system access

#### Moderator
- **Email**: moderator@ussd.com
- **Password**: password
- **Role**: moderator
- **Permissions**: Limited admin access

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed the Database
```bash
php artisan db:seed
```

Or run specific seeders:
```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AdminSeeder
```

### 3. Access Admin Panel
Navigate to: `/admin/login`

## Admin Routes

### Authentication Routes
- `GET /admin/login` - Admin login page
- `POST /admin/login` - Process admin login
- `POST /admin/logout` - Admin logout

### Protected Admin Routes
- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/businesses` - Business management
- `GET /admin/users` - User management
- `GET /admin/analytics` - Analytics
- `GET /admin/settings` - Settings

## Admin Login Features

### Clean Interface
- Minimal design with focus on functionality
- Responsive layout
- Clear error messages
- Loading states

### Security Features
- Role-based authentication
- Account status checking
- Session management
- CSRF protection

### Demo Credentials Display
The login page shows demo credentials for easy testing:
- Super Admin: admin@ussd.com / password
- System Admin: system@ussd.com / password
- Moderator: moderator@ussd.com / password

## Usage

### 1. Login
1. Navigate to `/admin/login`
2. Enter admin credentials
3. Click "Sign in"

### 2. Dashboard
After login, you'll be redirected to the admin dashboard with:
- System statistics
- Recent business registrations
- Pending approvals
- Quick actions

### 3. Business Management
- View all business registrations
- Approve or reject businesses
- Download business documents
- Search and filter businesses

### 4. User Management
- View all users
- Assign roles to users
- Enable/disable user accounts
- Search users

### 5. Analytics
- Business verification statistics
- Monthly registration trends
- Recent activity

### 6. Settings
- View system roles
- System information
- Quick actions

## Security Considerations

### 1. Role-Based Access
- Only users with 'admin' role can access admin panel
- Different permission levels for different roles
- Automatic role checking on all admin routes

### 2. Account Status
- Inactive accounts cannot log in
- Clear error messages for disabled accounts
- Admin can enable/disable user accounts

### 3. Session Management
- Secure session handling
- Automatic logout on inactivity
- CSRF protection on all forms

## Customization

### 1. Adding New Admin Users
Edit the `AdminSeeder.php` file to add more admin users:

```php
$adminUsers = [
    [
        'name' => 'New Admin',
        'email' => 'newadmin@ussd.com',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_active' => true,
    ],
    // Add more users...
];
```

### 2. Customizing Login Page
Edit `resources/js/Pages/Admin/Auth/Login.vue` to customize:
- Colors and styling
- Form fields
- Error messages
- Demo credentials display

### 3. Adding New Roles
Edit the `RoleSeeder.php` file to add new roles:

```php
$roles = [
    [
        'name' => 'new_role',
        'display_name' => 'New Role',
        'description' => 'Description of new role',
    ],
    // Add more roles...
];
```

## Troubleshooting

### 1. Admin Seeder Errors
If you encounter duplicate entry errors when running the admin seeder:

```bash
# Option 1: Reset admin data and reseed
php artisan admin:reset --force
php artisan db:seed --class=AdminSeeder

# Option 2: Run seeders individually
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AdminSeeder

# Option 3: Clear database and start fresh
php artisan migrate:fresh --seed
```

### 2. Admin Login Issues
- Ensure roles are seeded: `php artisan db:seed --class=RoleSeeder`
- Ensure admin users are seeded: `php artisan db:seed --class=AdminSeeder`
- Check if user has admin role: `$user->hasRole('admin')`
- Check if user is active: `$user->is_active`

### 2. Route Issues
- Ensure admin routes are loaded: Check `routes/admin.php`
- Verify middleware registration: Check `bootstrap/app.php`
- Check route caching: `php artisan route:clear`

### 3. Database Issues
- Run migrations: `php artisan migrate`
- Check database connection
- Verify table structure

## Future Enhancements

### 1. Two-Factor Authentication
- Add 2FA for admin accounts
- SMS or email verification
- Backup codes

### 2. Audit Logging
- Log all admin actions
- Track user changes
- Export audit logs

### 3. Advanced Permissions
- Granular permission system
- Permission groups
- Custom permission rules

### 4. Admin Notifications
- Email notifications for new registrations
- Dashboard notifications
- Real-time updates

## Support

For issues or questions about the admin system:
1. Check the troubleshooting section
2. Review the logs: `storage/logs/laravel.log`
3. Verify database state
4. Test with demo credentials
