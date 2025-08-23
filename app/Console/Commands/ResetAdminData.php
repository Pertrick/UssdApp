<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAdminData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset {--force : Force reset without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin data (roles and admin users)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete all roles and admin users. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Resetting admin data...');

        try {
            // Delete admin users
            $adminEmails = ['admin@ussd.com', 'system@ussd.com', 'moderator@ussd.com'];
            $deletedUsers = User::whereIn('email', $adminEmails)->delete();
            $this->info("Deleted {$deletedUsers} admin users.");

            // Delete roles
            $deletedRoles = Role::whereIn('name', UserRole::toArray())->delete();
            $this->info("Deleted {$deletedRoles} roles.");

            // Clear role_user pivot table
            DB::table('role_user')->truncate();
            $this->info('Cleared role assignments.');

            $this->info('Admin data reset successfully!');
            $this->info('You can now run: php artisan db:seed --class=AdminSeeder');

        } catch (\Exception $e) {
            $this->error('Error resetting admin data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

