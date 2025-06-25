# Business Verification System

## Overview

The business verification system allows administrators to verify businesses after they complete the registration process. This ensures that only legitimate businesses can access USSD services.

## Database Changes

### New Columns Added to `businesses` Table

```sql
ALTER TABLE businesses ADD COLUMN verified BOOLEAN DEFAULT FALSE;
ALTER TABLE businesses ADD COLUMN verified_at TIMESTAMP NULL;
```

### Column Descriptions

- **`verified`**: Boolean flag indicating if the business has been verified
- **`verified_at`**: Timestamp when the business was verified (NULL if not verified)

## Model Updates

### Business Model Methods

```php
// Check if business is verified
$business->isVerified();

// Mark business as verified
$business->markAsVerified();

// Mark business as unverified
$business->markAsUnverified();

// Query scopes
Business::verified();    // Get all verified businesses
Business::unverified();  // Get all unverified businesses
```

## Registration Status Flow

1. **`cac_info_pending`** - Initial status after business registration
2. **`email_verification_pending`** - After CAC and director info submitted
3. **`completed_unverified`** - Registration complete, awaiting verification
4. **`verified`** - Business verified by admin

## API Endpoints

### Admin Verification Routes

```php
// Verify a business
PATCH /business/{business}/verify
Route: business.verify

// Unverify a business
PATCH /business/{business}/unverify
Route: business.unverify
```

### Usage Examples

```php
// Verify a business
$business = Business::find(1);
$business->markAsVerified();

// Check verification status
if ($business->isVerified()) {
    echo "Business is verified";
}

// Get verification date
if ($business->verified_at) {
    echo "Verified on: " . $business->verified_at->format('Y-m-d H:i:s');
}
```

## Frontend Integration

### Dashboard Display

The dashboard now shows:
- Verification status in the welcome section
- Verification status card in statistics
- Verification date when available

### Status Indicators

- **✓ Verified** (Green) - Business has been verified
- **⚠ Pending** (Orange) - Business is awaiting verification

## Security

### Admin Authorization

Business verification requires admin privileges:

```php
// Check if user has admin role
if (!auth()->user()->hasRole('admin')) {
    abort(403, 'Unauthorized action.');
}
```

### Verification Process

1. Business completes registration
2. Admin reviews submitted documents
3. Admin verifies business using verification endpoint
4. Business status updated to "verified"
5. Business can now access USSD services

## Migration

Run the migration to add verification columns:

```bash
php artisan migrate
```

## Future Enhancements

### Planned Features

1. **Automated Verification**: AI-powered document verification
2. **Verification Levels**: Different levels of verification (basic, enhanced, premium)
3. **Verification History**: Track all verification changes
4. **Email Notifications**: Notify businesses when verified/unverified
5. **Verification Dashboard**: Admin interface for managing verifications

### Additional Statuses

- **`verification_rejected`** - Business verification rejected
- **`verification_pending_review`** - Under admin review
- **`verification_expired`** - Verification expired, needs renewal

## Usage in Controllers

### Example: USSD Controller

```php
public function store(StoreUSSDRequest $request)
{
    $business = auth()->user()->primaryBusiness();
    
    // Check if business is verified before allowing USSD creation
    if (!$business->isVerified()) {
        return back()->withErrors(['error' => 'Business must be verified to create USSD services.']);
    }
    
    // Proceed with USSD creation
    // ...
}
```

### Example: Dashboard Controller

```php
public function dashboard()
{
    $user = Auth::user();
    $business = $user->primaryBusiness();
    
    // Include verification status in dashboard data
    $businessData = $business ? [
        'id' => $business->id,
        'business_name' => $business->business_name,
        'verified' => $business->verified,
        'verified_at' => $business->verified_at,
        'registration_status' => $business->registration_status,
    ] : null;
    
    return Inertia::render('Dashboard', [
        'user' => $user,
        'business' => $businessData,
        'ussdStats' => $ussdStats,
    ]);
}
```

## Testing

### Factory Updates

Update the BusinessFactory to include verification fields:

```php
public function definition(): array
{
    return [
        // ... existing fields
        'verified' => $this->faker->boolean(20), // 20% chance of being verified
        'verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
    ];
}

// Create verified business
public function verified(): static
{
    return $this->state(fn (array $attributes) => [
        'verified' => true,
        'verified_at' => now(),
    ]);
}

// Create unverified business
public function unverified(): static
{
    return $this->state(fn (array $attributes) => [
        'verified' => false,
        'verified_at' => null,
    ]);
}
```

### Test Examples

```php
// Test verification methods
public function test_business_verification()
{
    $business = Business::factory()->create();
    
    $this->assertFalse($business->isVerified());
    
    $business->markAsVerified();
    
    $this->assertTrue($business->isVerified());
    $this->assertNotNull($business->verified_at);
}

public function test_verification_scopes()
{
    Business::factory()->verified()->count(3)->create();
    Business::factory()->unverified()->count(2)->create();
    
    $this->assertEquals(3, Business::verified()->count());
    $this->assertEquals(2, Business::unverified()->count());
}
``` 