<?php

namespace App\Policies;

use App\Models\DailyPriceSetting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DailyPriceSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-daily-price-settings');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('add-to-daily-price-settings');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DailyPriceSetting $dailyPriceSetting): bool
    {
        return $user->hasPermissionTo('remove-from-daily-price-settings');
    }
}
