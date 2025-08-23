<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist first
        $this->call(RoleSeeder::class);

        // Create admin users
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@ussd.com',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'System Admin',
                'email' => 'system@ussd.com',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Moderator',
                'email' => 'moderator@ussd.com',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
                'role' => UserRole::MODERATOR,
            ],
        ];

        foreach ($adminUsers as $adminData) {
            // Check if user already exists
            $existingUser = User::where('email', $adminData['email'])->first();
            
            if ($existingUser) {
                $this->command->info("Admin user '{$adminData['email']}' already exists. Skipping...");
                continue;
            }

            try {
                // Create the user
                $user = User::create([
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => Hash::make($adminData['password']),
                    'email_verified_at' => $adminData['email_verified_at'],
                    'is_active' => $adminData['is_active'],
                ]);

                // Assign role
                $role = $adminData['role'];
                $user->assignRole($role->value);
                $this->command->info("Created {$adminData['name']}: {$adminData['email']} (Role: {$role->value})");
                
            } catch (\Exception $e) {
                $this->command->error("Failed to create admin user '{$adminData['email']}': " . $e->getMessage());
            }
        }

        $this->command->info('Admin users seeded successfully!');
        $this->command->info('Default credentials:');
        $this->command->info('- Super Admin: admin@ussd.com / password');
        $this->command->info('- System Admin: system@ussd.com / password');
        $this->command->info('- Moderator: moderator@ussd.com / password');
    }
}
