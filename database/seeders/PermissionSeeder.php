<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // دسترسی‌های پایه
            ['name' => 'access-admin-panel', 'display_name' => 'دسترسی به پنل ادمین'],
            ['name' => 'view-dashboard', 'display_name' => 'دیدن صفحه اصلی داشبورد (ثبت قیمت)'],

            // مدیریت محصولات
            ['name' => 'view-products', 'display_name' => 'دیدن لیست محصولات'],
            ['name' => 'create-product', 'display_name' => 'ثبت محصول جدید'],
            ['name' => 'edit-product', 'display_name' => 'ویرایش محصولات'],
            ['name' => 'delete-product', 'display_name' => 'حذف محصولات'],

            // مدیریت مکان‌ها
            ['name' => 'view-locations', 'display_name' => 'دیدن لیست مکان‌ها'],
            ['name' => 'create-location', 'display_name' => 'ثبت مکان جدید'],
            ['name' => 'edit-location', 'display_name' => 'ویرایش مکان‌ها'],
            ['name' => 'delete-location', 'display_name' => 'حذف مکان‌ها'],

            // مدیریت کاربران
            ['name' => 'view-users', 'display_name' => 'دیدن لیست کاربران'],
            ['name' => 'create-user', 'display_name' => 'ایجاد کاربر جدید'],
            ['name' => 'edit-user', 'display_name' => 'ویرایش کاربران'],
            ['name' => 'edit-user-role', 'display_name' => 'ویرایش نقش کاربران'], // برای آینده
            ['name' => 'delete-user', 'display_name' => 'حذف کاربران'],

            // مدیریت سمت‌ها
            ['name' => 'view-roles', 'display_name' => 'دیدن لیست سمت‌ها'],
            ['name' => 'create-role', 'display_name' => 'ایجاد سمت جدید'],
            ['name' => 'edit-role', 'display_name' => 'ویرایش سمت‌ها'],
            ['name' => 'delete-role', 'display_name' => 'حذف سمت‌ها'],
            ['name' => 'assign-permissions-to-role', 'display_name' => 'اختصاص دسترسی به سمت'], // <--- اضافه شد

            // مدیریت تنظیمات قیمت روزانه
            ['name' => 'view-daily-price-settings', 'display_name' => 'دیدن لیست محصولات/مکان‌های فعال امروز'],
            ['name' => 'add-to-daily-price-settings', 'display_name' => 'افزودن محصول/مکان به لیست فعال امروز'],
            ['name' => 'remove-from-daily-price-settings', 'display_name' => 'حذف محصول/مکان از لیست فعال امروز'],

            // مدیریت قیمت‌ها (ثبت/بروزرسانی)
            ['name' => 'save-daily-prices', 'display_name' => 'ثبت/بروزرسانی قیمت‌های روزانه'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
