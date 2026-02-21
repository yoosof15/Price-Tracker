<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // پاک کردن تمام رابطه‌های قبلی
        DB::table('role_has_permissions')->truncate();

        // دریافت تمام سمت‌ها و دسترسی‌ها
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $operatorRole = Role::where('name', 'operator')->first();
        $viewerRole = Role::where('name', 'viewer')->first();

        $allPermissions = Permission::all();

        // super_admin: تمام دسترسی‌ها
        if ($superAdminRole) {
            foreach ($allPermissions as $permission) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        // admin: تمام دسترسی‌ها به جز نقش‌ها و دسترسی‌ها
        if ($adminRole) {
            $adminPermissions = $allPermissions->filter(function ($permission) {
                return !in_array($permission->name, [
                    'view-roles',
                    'create-role',
                    'edit-role',
                    'delete-role',
                    'assign-permissions-to-role',
                ]);
            });

            foreach ($adminPermissions as $permission) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        // operator: دسترسی به ثبت قیمت و مدیریت پایه
        if ($operatorRole) {
            $operatorPermissions = [
                'view-dashboard',
                'view-products',
                'view-locations',
                'view-daily-price-settings',
                'add-to-daily-price-settings',
                'remove-from-daily-price-settings',
                'save-daily-prices',
            ];

            $permissions = Permission::whereIn('name', $operatorPermissions)->get();
            foreach ($permissions as $permission) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $operatorRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        // viewer: فقط مشاهده
        if ($viewerRole) {
            $viewerPermissions = [
                'view-products',
                'view-locations',
            ];

            $permissions = Permission::whereIn('name', $viewerPermissions)->get();
            foreach ($permissions as $permission) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $viewerRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
