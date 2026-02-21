<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LocationSeeder::class,
            ProductSeeder::class,
            PermissionSeeder::class,
            RoleHasPermissionSeeder::class,
        ]);

        // یوزر تستی
        $superAdminRole = Role::where('name', 'super_admin')->first();
        User::create([
            'name' => 'اتگل',
            'phone' => '09108102750',
            'password' => bcrypt('12345678'),
            'role_id' => $superAdminRole ? $superAdminRole->id : null,
        ]);
    }
}
