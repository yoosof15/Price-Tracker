<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // معمولا دیدن دسترسی ها به کسی که نقش ها را مدیریت میکند داده میشود
        return $user->hasPermissionTo('view-roles');
    }
}
