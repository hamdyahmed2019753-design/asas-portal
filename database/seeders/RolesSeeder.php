<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Create the three application roles (admin / investor / member).
     *
     * Roles are kept idempotent via firstOrCreate so the seeder can be
     * re-run safely.
     */
    public function run(): void
    {
        // Make sure the package's permission cache is fresh before seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = ['admin', 'investor', 'member'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
