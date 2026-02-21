<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Location;
use App\Models\DailyPriceSetting;
use App\Models\Price;
use App\Models\Permission; // <--- برای دسترسی به permissions
use App\Policies\UserPolicy;
use App\Policies\ProductPolicy;
use App\Policies\LocationPolicy;
use App\Policies\DailyPriceSettingPolicy;
use App\Policies\PricePolicy;
use App\Policies\RolePolicy;
use App\Policies\PermissionPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class,
        Location::class => LocationPolicy::class,
        DailyPriceSetting::class => DailyPriceSettingPolicy::class,
        Price::class => PricePolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {

    }
}
