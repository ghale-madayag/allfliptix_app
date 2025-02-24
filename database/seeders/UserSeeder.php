<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // Create 20 fake users
        User::factory(20)->create()->each(function ($user, $index) use ($adminRole, $userRole) {
            // Assign Administrator role to the first 5 users, others as User
            $role = $index < 5 ? $adminRole : $userRole;
            $user->assignRole($role);
        });
    }
}
