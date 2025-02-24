<?php

namespace Database\Seeders;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the roles are created only once
        Role::firstOrCreate(['name' => 'Administrator']);
        Role::firstOrCreate(['name' => 'User']);
    }
}
