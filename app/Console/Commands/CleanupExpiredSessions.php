<?php

namespace App\Console\Commands;

use App\Models\USSDSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ussd:cleanup-expired-sessions 
                            {--days=30 : Number of days to keep expired sessions}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired and old USSD sessions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Cleaning up expired sessions older than {$days} days...");
        
        // Find expired sessions that are older than the specified days
        $cutoffDate = now()->subDays($days);
        
        $query = USSDSession::where(function($q) use ($cutoffDate) {
            // Sessions that are expired (expires_at < now) AND older than cutoff date
            $q->where('expires_at', '<', $cutoffDate)
              ->orWhere(function($q2) use ($cutoffDate) {
                  // Or sessions that are not active and older than cutoff date
                  $q2->where('status', '!=', 'active')
                     ->where('created_at', '<', $cutoffDate);
              });
        });
        
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No expired sessions found to clean up.');
            return 0;
        }
        
        $this->info("Found {$count} expired sessions to clean up.");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No sessions will be deleted.');
            $this->table(
                ['ID', 'Session ID', 'Phone', 'Status', 'Expires At', 'Created At'],
                $query->limit(10)->get()->map(function($session) {
                    return [
                        $session->id,
                        substr($session->session_id ?? '', 0, 20) . '...',
                        substr($session->phone_number ?? '', 0, 10) . '...',
                        $session->status,
                        $session->expires_at?->format('Y-m-d H:i:s') ?? 'N/A',
                        $session->created_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            );
            if ($count > 10) {
                $this->info("... and " . ($count - 10) . " more sessions.");
            }
            return 0;
        }
        
        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete {$count} expired sessions?")) {
            $this->info('Cleanup cancelled.');
            return 0;
        }
        
        // Delete in batches to avoid memory issues
        $deleted = 0;
        $batchSize = 1000;
        
        $this->info("Deleting sessions in batches of {$batchSize}...");
        
        do {
            $batch = $query->limit($batchSize)->pluck('id');
            $batchCount = $batch->count();
            
            if ($batchCount > 0) {
                USSDSession::whereIn('id', $batch)->delete();
                $deleted += $batchCount;
                $this->info("Deleted {$deleted} / {$count} sessions...");
            }
        } while ($batchCount > 0);
        
        $this->info("Successfully deleted {$deleted} expired sessions.");
        
        Log::info('Expired USSD sessions cleaned up', [
            'deleted_count' => $deleted,
            'days_old' => $days,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);
        
        return 0;
    }
}
