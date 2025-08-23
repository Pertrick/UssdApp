<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = UserRole::toArrayWithDisplayNames();

        foreach ($roles as $role) {
            // Check if role already exists
            $existingRole = DB::table('roles')->where('name', $role['value'])->first();
            
            if (!$existingRole) {
                DB::table('roles')->insert([
                    'name' => $role['value'],
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created role: {$role['value']}");
            } else {
                $this->command->info("Role '{$role['value']}' already exists. Skipping...");
            }
        }
    }
}
