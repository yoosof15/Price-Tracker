<?php

namespace App\Policies;

use App\Models\Price;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PricePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // این Policy برای store کردن قیمت ها استفاده میشود
        return $user->hasPermissionTo('save-daily-prices');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Price $price): bool
    {
        // این Policy برای update کردن قیمت ها استفاده میشود
        return $user->hasPermissionTo('save-daily-prices');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Price $price): bool
    {
        // این Policy برای حذف قیمت ها (وقتی min/max null میشوند) استفاده میشود
        return $user->hasPermissionTo('save-daily-prices');
    }
}
