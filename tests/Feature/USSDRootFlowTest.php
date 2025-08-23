<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class USSDRootFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ussd_gets_default_root_flow_when_created()
    {
        // Create a user and business
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        // Create a USSD
        $ussd = USSD::create([
            'name' => 'Test USSD',
            'description' => 'Test USSD Description',
            'pattern' => '123#',
            'user_id' => $user->id,
            'business_id' => $business->id,
            'is_active' => true,
        ]);

        // Create the default root flow
        $ussd->createDefaultRootFlow();

        // Assert that a root flow was created
        $this->assertTrue($ussd->hasFlows());
        $this->assertNotNull($ussd->rootFlow());
        $this->assertTrue($ussd->rootFlow()->is_root);

        // Assert that the root flow has the correct properties
        $rootFlow = $ussd->rootFlow();
        $this->assertEquals('Main Menu', $rootFlow->name);
        $this->assertEquals('Main menu for Test USSD', $rootFlow->description);
        $this->assertStringContainsString('Welcome to Test USSD', $rootFlow->menu_text);
        $this->assertTrue($rootFlow->is_active);

        // Assert that the root flow has default options
        $this->assertTrue($rootFlow->options()->exists());
        $this->assertEquals(4, $rootFlow->options()->count());

        // Check specific options
        $options = $rootFlow->options()->orderBy('sort_order')->get();
        
        $this->assertEquals('Option 1', $options[0]->option_text);
        $this->assertEquals('1', $options[0]->option_value);
        $this->assertEquals('message', $options[0]->action_type);

        $this->assertEquals('Option 2', $options[1]->option_text);
        $this->assertEquals('2', $options[1]->option_value);
        $this->assertEquals('message', $options[1]->action_type);

        $this->assertEquals('Option 3', $options[2]->option_text);
        $this->assertEquals('3', $options[2]->option_value);
        $this->assertEquals('message', $options[2]->action_type);

        $this->assertEquals('Exit', $options[3]->option_text);
        $this->assertEquals('0', $options[3]->option_value);
        $this->assertEquals('end_session', $options[3]->action_type);
    }

    public function test_ensure_root_flow_creates_one_if_not_exists()
    {
        // Create a user and business
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        // Create a USSD without a root flow
        $ussd = USSD::create([
            'name' => 'Test USSD',
            'description' => 'Test USSD Description',
            'pattern' => '123#',
            'user_id' => $user->id,
            'business_id' => $business->id,
            'is_active' => true,
        ]);

        // Initially, there should be no root flow
        $this->assertNull($ussd->rootFlow());

        // Call ensureRootFlow
        $rootFlow = $ussd->ensureRootFlow();

        // Now there should be a root flow
        $this->assertNotNull($ussd->rootFlow());
        $this->assertTrue($ussd->rootFlow()->is_root);
        $this->assertEquals($rootFlow->id, $ussd->rootFlow()->id);
    }

    public function test_ensure_root_flow_returns_existing_one_if_exists()
    {
        // Create a user and business
        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        // Create a USSD
        $ussd = USSD::create([
            'name' => 'Test USSD',
            'description' => 'Test USSD Description',
            'pattern' => '123#',
            'user_id' => $user->id,
            'business_id' => $business->id,
            'is_active' => true,
        ]);

        // Create a root flow
        $originalRootFlow = $ussd->createDefaultRootFlow();

        // Call ensureRootFlow again
        $ensuredRootFlow = $ussd->ensureRootFlow();

        // Should return the same flow, not create a new one
        $this->assertEquals($originalRootFlow->id, $ensuredRootFlow->id);
        $this->assertEquals(1, $ussd->flows()->where('is_root', true)->count());
    }
}
