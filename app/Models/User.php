<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    // <--- برای اینکه Role و permissions آن همیشه با User لود شود
    protected $with = ['role.permissions'];

    // <--- Accessor های جدید برای فرانت اند
    protected $appends = [
        'is_admin', // <--- این برای چک isSuperAdmin هست
        'can_view_dashboard',
        'can_view_products',
        'can_create_product',
        'can_edit_product',
        'can_delete_product',
        'can_view_locations',
        'can_create_location',
        'can_edit_location',
        'can_delete_location',
        'can_view_users',
        'can_create_user',
        'can_edit_user',
        'can_delete_user',
        'can_view_roles',
        'can_create_role',
        'can_edit_role',
        'can_delete_role',
        'can_assign_permissions_to_role',
        'can_edit_user_role',
        // <--- Accessor های جدید برای Daily Price Settings
        'can_view_daily_price_settings',
        'can_add_to_daily_price_settings',
        'can_remove_from_daily_price_settings',
        'can_save_daily_prices',
        'has_access_admin_panel' // <--- برای دسترسی کلی به پنل ادمین
    ];


    // <--- رابطه با Role Model
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // <--- توابع کمکی برای بررسی نقش و دسترسی
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->name === 'super_admin';
    }

    public function isPriceEntryUser(): bool
    {
        return $this->role && $this->role->name === 'price_entry_user';
    }

    public function hasPermissionTo($permissionName): bool
    {
        // اگر نقش ندارد، دسترسی ندارد
        if (!$this->role) {
            return false;
        }

        // چک کردن دسترسی بر اساس نام
        return $this->role->permissions->contains('name', $permissionName);
    }

    // <--- Accessor ها (برای فرانت اند)
    protected function isAdmin(): Attribute {
        return Attribute::make(
            get: fn () => $this->isSuperAdmin(),
        );
    }
    protected function canViewDashboard(): Attribute {
        return Attribute::make(
            get: fn () => $this->hasPermissionTo('view-dashboard'),
        );
    }

    // Accessor ها برای محصولات
    protected function canViewProducts(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('view-products')); }
    protected function canCreateProduct(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('create-product')); }
    protected function canEditProduct(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('edit-product')); }
    protected function canDeleteProduct(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('delete-product')); }

    // Accessor ها برای مکان ها
    protected function canViewLocations(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('view-locations')); }
    protected function canCreateLocation(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('create-location')); }
    protected function canEditLocation(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('edit-location')); }
    protected function canDeleteLocation(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('delete-location')); }

    // Accessor ها برای کاربران
    protected function canViewUsers(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('view-users')); }
    protected function canCreateUser(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('create-user')); }
    protected function canEditUser(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('edit-user')); }
    protected function canDeleteUser(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('delete-user')); }
    protected function canEditUserRole(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('edit-user-role')); }

    // Accessor ها برای سمت ها
    protected function canViewRoles(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('view-roles')); }
    protected function canCreateRole(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('create-role')); }
    protected function canEditRole(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('edit-role')); }
    protected function canDeleteRole(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('delete-role')); }
    protected function canAssignPermissionsToRole(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('assign-permissions-to-role')); }

    // Accessor ها برای تنظیمات قیمت روزانه
    protected function canViewDailyPriceSettings(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('view-daily-price-settings')); }
    protected function canAddToDailyPriceSettings(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('add-to-daily-price-settings')); }
    protected function canRemoveFromDailyPriceSettings(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('remove-from-daily-price-settings')); }
    protected function canSaveDailyPrices(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('save-daily-prices')); }

    // Accessor برای دسترسی کلی به پنل ادمین
    protected function hasAccessAdminPanel(): Attribute { return Attribute::make(get: fn () => $this->hasPermissionTo('access-admin-panel')); }
}