<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Enums\BusinessRegistrationStatus;

class UpdateBusinessStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business:update-status {business_id} {status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update business registration status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessId = $this->argument('business_id');
        $newStatus = $this->argument('status');

        $business = Business::find($businessId);
        
        if (!$business) {
            $this->error("Business with ID {$businessId} not found!");
            return 1;
        }

        $this->info("Current status: {$business->registration_status->value}");
        
        try {
            $business->update([
                'registration_status' => $newStatus
            ]);
            
            $this->info("Business status updated to: {$newStatus}");
            $this->info("Business: {$business->business_name}");
            
        } catch (\Exception $e) {
            $this->error("Error updating status: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
