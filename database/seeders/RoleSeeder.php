<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vytvoření rolí (idempotentně — seeder lze pouštět opakovaně)
        foreach (['super_admin', 'admin', 'super_redaktor', 'redaktor'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
