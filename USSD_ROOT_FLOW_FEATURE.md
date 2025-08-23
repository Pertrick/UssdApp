# USSD Root Flow Feature

## Overview

This feature automatically creates a default root flow when a USSD is created, ensuring that every USSD has a starting point for user interaction.

## What's New

### 1. Automatic Root Flow Creation
When a USSD is created, it now automatically gets a default root flow with:
- **Name**: "Main Menu"
- **Description**: "Main menu for [USSD Name]"
- **Menu Text**: A welcome message with 4 default options
- **Status**: Active and marked as root flow

### 2. Default Options
The root flow comes with 4 default options:
1. **Option 1** - Placeholder message action
2. **Option 2** - Placeholder message action  
3. **Option 3** - Placeholder message action
4. **Exit** - End session action

### 3. Helper Methods
The USSD model now includes several helper methods:
- `createDefaultRootFlow()` - Creates a new root flow with default options
- `ensureRootFlow()` - Creates a root flow if one doesn't exist, or returns existing one
- `rootFlow()` - Gets the root flow for the USSD
- `hasFlows()` - Checks if the USSD has any flows
- `firstFlow()` - Gets the first flow (usually root flow)

## Implementation Details

### USSD Model Updates
```php
// New methods added to App\Models\USSD
public function rootFlow()
public function createDefaultRootFlow()
private function createDefaultRootFlowOptions(USSDFlow $rootFlow)
public function ensureRootFlow()
public function hasFlows()
public function firstFlow()
```

### Controller Updates
The following controller methods now ensure root flows exist:
- `USSDController::store()` - Creates root flow when USSD is created
- `USSDController::show()` - Ensures root flow exists before displaying
- `USSDController::configure()` - Ensures root flow exists before configuration
- `USSDController::simulator()` - Ensures root flow exists before simulation

### Database Structure
The root flow is stored in the `ussd_flows` table with:
- `is_root = true`
- `parent_flow_id = null`
- `sort_order = 1`
- `is_active = true`

## Usage Examples

### Creating a USSD (Automatic Root Flow)
```php
$ussd = USSD::create([
    'name' => 'My USSD',
    'description' => 'My USSD Description',
    'pattern' => '123#',
    'user_id' => $user->id,
    'business_id' => $business->id,
    'is_active' => true,
]);

// Root flow is automatically created
$rootFlow = $ussd->rootFlow();
echo $rootFlow->name; // "Main Menu"
```

### Ensuring Root Flow Exists
```php
$ussd = USSD::find(1);

// This will create a root flow if one doesn't exist
$rootFlow = $ussd->ensureRootFlow();
```

### Checking for Flows
```php
$ussd = USSD::find(1);

if ($ussd->hasFlows()) {
    echo "USSD has flows";
}

$rootFlow = $ussd->rootFlow();
if ($rootFlow) {
    echo "USSD has a root flow";
}
```

## Migration for Existing USSDs

If you have existing USSDs without root flows, you can use the provided Artisan command:

```bash
php artisan ussd:create-root-flows
```

This command will:
1. Find all USSDs without root flows
2. Create default root flows for each one
3. Show progress and results

## Testing

Run the tests to verify the functionality:

```bash
php artisan test --filter=USSDRootFlowTest
```

The tests verify:
- Root flow creation when USSD is created
- Default options are created correctly
- `ensureRootFlow()` method works properly
- No duplicate root flows are created

## Benefits

1. **Consistency**: Every USSD now has a starting point
2. **User Experience**: Users can immediately interact with new USSDs
3. **Development**: Developers don't need to manually create root flows
4. **Testing**: Simulator and configuration pages work immediately
5. **Backward Compatibility**: Existing USSDs can be updated with the command

## Customization

You can customize the default root flow by modifying the `createDefaultRootFlow()` method in the USSD model. The method creates:

- A root flow with customizable name, description, and menu text
- Default options with customizable text, values, and actions
- Proper relationships between flows and options

## Future Enhancements

Potential improvements could include:
- Configurable default options per business type
- Template-based root flows
- Import/export of flow templates
- Advanced flow validation
- Flow versioning system
