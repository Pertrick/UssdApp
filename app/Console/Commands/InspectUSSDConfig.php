<?php

namespace App\Console\Commands;

use App\Models\USSD;
use App\Models\USSDSharedCodeAllocation;
use Illuminate\Console\Command;

class InspectUSSDConfig extends Command
{
    protected $signature = 'ussd:inspect {--pattern= : Filter by pattern (partial match)}';

    protected $description = 'Inspect USSD records and shared-code allocations for debugging';

    public function handle(): int
    {
        $patternFilter = $this->option('pattern');

        $this->info('=== USSD Records ===');
        $query = USSD::with(['business', 'sharedCodeAllocations'])->orderBy('pattern');
        if ($patternFilter) {
            $query->where('pattern', 'like', '%' . $patternFilter . '%');
        }
        $ussds = $query->get();

        foreach ($ussds as $u) {
            $gw = $u->is_shared_gateway ? ' [GATEWAY]' : '';
            $this->line(sprintf(
                'id=%d | pattern=%s | name=%s | is_shared_gateway=%s%s',
                $u->id,
                $u->pattern,
                $u->name,
                $u->is_shared_gateway ? 'true' : 'false',
                $gw
            ));
            if ($u->sharedCodeAllocations->isNotEmpty()) {
                foreach ($u->sharedCodeAllocations as $a) {
                    $target = $a->targetUssd;
                    $this->line(sprintf(
                        '    → option "%s" → %s (id=%d, pattern=%s)',
                        $a->option_value,
                        $a->label,
                        $target->id ?? 0,
                        $target->pattern ?? '?'
                    ));
                }
            }
        }

        $this->newLine();
        $this->info('=== Direct-pattern check (what AT would match) ===');
        $codes = ['*384*36522#', '*384*36522*1#', '*384*36522*2#'];
        foreach ($codes as $code) {
            $match = USSD::where('pattern', $code)->where('is_active', true)->first();
            if ($match) {
                $this->line(sprintf('%s → FOUND: %s (id=%d)', $code, $match->name, $match->id));
            } else {
                $this->line(sprintf('%s → NOT FOUND', $code));
            }
        }

        return 0;
    }
}
