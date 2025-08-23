# Admin Section - USSD Application

## Overview

This document describes the comprehensive Admin section that has been implemented for the USSD application. The admin section provides role-based access control and business approval functionality.

## Features Implemented

### 1. Role-Based Access Control (RBAC)
- **Roles System**: Admin, User, and Moderator roles
- **Permissions**: Role-based permissions for different actions
- **User Management**: Assign/remove roles from users

### 2. Business Approval System
- **Business Review**: Admins can review all business registrations
- **Document Verification**: View and download CAC and director documents
- **Approval/Rejection**: Approve or reject businesses with reasons
- **Status Tracking**: Track verification status and timeline

### 3. Admin Dashboard
- **Statistics**: Overview of users, businesses, and pending approvals
- **Recent Activity**: Latest business registrations and pending approvals
- **Quick Actions**: Fast access to common admin tasks

### 4. User Management
- **User List**: View all registered users
- **Role Assignment**: Assign roles to users
- **Status Management**: Enable/disable user accounts
- **Search & Filter**: Find users by name or email

### 5. Analytics
- **Business Statistics**: Total, verified, pending, and rejected businesses
- **Monthly Registrations**: Chart showing registration trends
- **Recent Activity**: Latest business activities

## Database Changes

### New Tables
1. **roles** - Stores system roles
2. **role_user** - Many-to-many relationship between users and roles

### Modified Tables
1. **users** - Added `is_active` field
2. **businesses** - Added `rejection_reason` field

### Migrations Created
- `2025_01_01_000000_create_roles_table.php`
- `2025_01_01_000001_add_rejection_reason_to_businesses_table.php`
- `2025_01_01_000002_add_is_active_to_users_table.php`

## Models

### Role Model (`app/Models/Role.php`)
- Manages role information and permissions
- Has many-to-many relationship with users
- Includes permission checking methods

### Updated User Model (`app/Models/User.php`)
- Added role relationships and methods
- Role checking methods (`hasRole`, `hasAnyRole`, etc.)
- Permission checking methods
- Role assignment/removal methods

### Updated Business Model (`app/Models/Business.php`)
- Added `rejection_reason` to fillable fields
- Verification status tracking

## Controllers

### AdminController (`app/Http/Controllers/AdminController.php`)
Comprehensive admin functionality including:

#### Dashboard Methods
- `dashboard()` - Admin dashboard with statistics
- `analytics()` - System analytics and charts

#### Business Management
- `businesses()` - List all businesses with filtering
- `showBusiness()` - Detailed business view
- `approveBusiness()` - Approve a business
- `rejectBusiness()` - Reject a business with reason
- `downloadDocument()` - Download business documents

#### User Management
- `users()` - List all users
- `updateUserRoles()` - Update user roles
- `toggleUserStatus()` - Enable/disable users

#### Settings
- `settings()` - Admin settings page

## Middleware

### AdminMiddleware (`app/Http/Middleware/AdminMiddleware.php`)
- Protects admin routes
- Checks for admin role
- Redirects unauthorized users

## Frontend Components

### Admin Layout (`resources/js/Layouts/AdminLayout.vue`)
- Dedicated admin navigation
- Responsive design
- User menu with logout

### Admin Pages
1. **Dashboard** (`resources/js/Pages/Admin/Dashboard.vue`)
   - Statistics cards
   - Recent businesses
   - Pending approvals

2. **Businesses** (`resources/js/Pages/Admin/Businesses.vue`)
   - Business listing with search/filter
   - Approval/rejection actions
   - Pagination

3. **Business Detail** (`resources/js/Pages/Admin/BusinessDetail.vue`)
   - Complete business information
   - Document downloads
   - Approval/rejection with reason

4. **Users** (`resources/js/Pages/Admin/Users.vue`)
   - User management
   - Role assignment
   - Status toggling

5. **Analytics** (`resources/js/Pages/Admin/Analytics.vue`)
   - Business statistics
   - Monthly registration chart
   - Recent activity

6. **Settings** (`resources/js/Pages/Admin/Settings.vue`)
   - Role management
   - System information
   - Quick actions

## Routes

### Admin Routes (Protected by admin middleware)
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/businesses', [AdminController::class, 'businesses'])->name('businesses');
    Route::get('/businesses/{business}', [AdminController::class, 'showBusiness'])->name('businesses.show');
    Route::patch('/businesses/{business}/approve', [AdminController::class, 'approveBusiness'])->name('businesses.approve');
    Route::patch('/businesses/{business}/reject', [AdminController::class, 'rejectBusiness'])->name('businesses.reject');
    Route::get('/businesses/{business}/documents/{documentType}', [AdminController::class, 'downloadDocument'])->name('businesses.documents.download');
    
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/roles', [AdminController::class, 'updateUserRoles'])->name('users.roles');
    Route::patch('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('users.toggle-status');
    
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});
```

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed the Database
```bash
php artisan db:seed
```

This will create:
- Default roles (admin, user, moderator)
- Admin user (admin@example.com)
- Regular user (test@example.com)

### 3. Access Admin Section
- Login with admin credentials: `admin@example.com`
- Navigate to `/admin/dashboard`

## Default Admin Credentials
- **Email**: admin@example.com
- **Password**: password (from factory)

## Security Features

1. **Role-Based Access**: Only users with admin role can access admin routes
2. **Middleware Protection**: All admin routes protected by AdminMiddleware
3. **Permission Checking**: Methods check for specific permissions
4. **Document Security**: Document downloads are protected and logged

## Business Approval Workflow

1. **Business Registration**: User registers business
2. **Document Upload**: CAC and director documents uploaded
3. **Admin Review**: Admin reviews business details and documents
4. **Approval/Rejection**: Admin approves or rejects with reason
5. **Status Update**: Business status updated accordingly

## User Management Workflow

1. **User Registration**: Users register normally
2. **Role Assignment**: Admin assigns appropriate roles
3. **Status Management**: Admin can enable/disable users
4. **Permission Control**: Users only access features based on roles

## Future Enhancements

1. **Advanced Permissions**: Granular permission system
2. **Audit Logging**: Track all admin actions
3. **Email Notifications**: Notify users of approval/rejection
4. **Bulk Operations**: Bulk approve/reject businesses
5. **Advanced Analytics**: More detailed reporting
6. **API Endpoints**: REST API for admin operations

## Troubleshooting

### Common Issues

1. **Admin Access Denied**
   - Ensure user has admin role
   - Check middleware registration
   - Verify role assignment

2. **Missing Routes**
   - Check route registration in `web.php`
   - Ensure middleware is properly registered

3. **Database Issues**
   - Run migrations: `php artisan migrate`
   - Seed database: `php artisan db:seed`
   - Check database connection

4. **Frontend Issues**
   - Clear cache: `php artisan cache:clear`
   - Rebuild assets: `npm run build`

## Support

For issues or questions about the admin section, please refer to:
- Laravel documentation
- Vue.js documentation
- Inertia.js documentation
