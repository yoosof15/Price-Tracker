<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // <--- نقش Super Admin
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'مدیر کل']
        );
        $superAdmin->permissions()->sync(Permission::pluck('id')); // <--- Super Admin به همه دسترسی ها را دارد

        // <--- نقش Price Entry User
        $priceEntryUser = Role::firstOrCreate(
            ['name' => 'price_entry_user'],
            ['display_name' => 'کاربر ثبت قیمت']
        );

        // دسترسی های کاربر ثبت قیمت
        $priceEntryUserPermissions = [
            'view-products',
            'view-locations',
            'view-daily-price-settings',
            'add-to-daily-price-settings',
            'remove-from-daily-price-settings',
            'save-daily-prices',
            'create-product',
            'edit-product',
            'create-location',
            'edit-location',
        ];

        $permissionIds = Permission::whereIn('name', $priceEntryUserPermissions)->pluck('id');
        $priceEntryUser->permissions()->sync($permissionIds);
    }
}
