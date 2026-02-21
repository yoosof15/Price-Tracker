<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-roles');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-role');
    }

    /**
     * Determine whether the user can update roles.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('edit-role');
    }

    /**
     * Determine whether the user can delete roles.
     */
    public function delete(User $user, Role $role): bool
    {
        // جلوگیری از حذف نقش super_admin
        if ($role->name === 'super_admin') {
            return Response::deny('شما نمیتوانید نقش مدیر کل را حذف کنید.');
        }
        return $user->hasPermissionTo('delete-role');
    }

    /**
     * Determine whether the user can assign permissions to roles.
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('assign-permissions-to-role');
    }
}
