<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can manage (view, create, delete) any users.
     * This is the general gate for user management section.
     */
    public function manageUsers(User $user): bool
    {
        return $user->hasPermissionTo('view-users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-user');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // یک کاربر نمیتواند خودش را حذف کند
        if ($user->id === $model->id) {
            return Response::deny('شما نمیتوانید حساب کاربری خود را حذف کنید.');
        }
        // یک کاربر نمیتواند Super Admin دیگر را حذف کند
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return Response::deny('شما اجازه حذف مدیر کل را ندارید.');
        }
        return $user->hasPermissionTo('delete-user');
    }

    /**
     * Determine whether the user can update the role of another user.
     */
    public function updateRole(User $user, User $model): bool
    {
        // یک کاربر نمیتواند نقش خودش را تغییر دهد
        if ($user->id === $model->id) {
            return Response::deny('شما نمیتوانید نقش کاربری خود را تغییر دهید.');
        }
        // یک کاربر عادی نمیتواند نقش Super Admin را تغییر دهد
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return Response::deny('شما اجازه تغییر نقش مدیر کل را ندارید.');
        }
        return $user->hasPermissionTo('edit-user-role');
    }
}
