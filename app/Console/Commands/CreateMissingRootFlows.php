<?php

namespace App\Console\Commands;

use App\Models\USSD;
use Illuminate\Console\Command;

class CreateMissingRootFlows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ussd:create-root-flows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create root flows for USSDs that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for USSDs without root flows...');

        $ussdsWithoutRootFlows = USSD::whereDoesntHave('flows', function($query) {
            $query->where('is_root', true);
        })->get();

        if ($ussdsWithoutRootFlows->isEmpty()) {
            $this->info('All USSDs already have root flows!');
            return 0;
        }

        $this->info("Found {$ussdsWithoutRootFlows->count()} USSD(s) without root flows.");

        $bar = $this->output->createProgressBar($ussdsWithoutRootFlows->count());
        $bar->start();

        foreach ($ussdsWithoutRootFlows as $ussd) {
            try {
                $ussd->createDefaultRootFlow();
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nFailed to create root flow for USSD '{$ussd->name}': " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Root flows created successfully!');

        return 0;
    }
}
