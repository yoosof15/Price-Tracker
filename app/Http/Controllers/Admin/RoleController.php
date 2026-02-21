<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission; // <--- برای دسترسی به Permission ها
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Policyها را در متدهای مربوطه اعمال میکنیم
    }

    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view-roles')) { abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.'); }
        return Inertia::render('Admin/RoleList', [
            'roles' => Role::with('permissions')->get(),
            'allPermissions' => Permission::all()
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create-role')) { abort(403, 'شما اجازه ایجاد نقش جدید را ندارید.'); }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id', // مطمئن شو که IDهای دسترسی معتبر هستند
        ]);

        $role = Role::create($request->only('name', 'display_name'));
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        if (!auth()->user()->hasPermissionTo('view-roles')) { abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.'); }

        return response()->json($role->load('permissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        if (!auth()->user()->hasPermissionTo('edit-role')) { abort(403, 'شما اجازه ویرایش نقش را ندارید.'); }

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update($request->only('name', 'display_name'));
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach(); // اگر هیچ دسترسی ارسال نشد، همه را حذف کن
        }

        return response()->json($role->load('permissions'), 200);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        if (!auth()->user()->hasPermissionTo('delete-role')) { abort(403, 'شما اجازه حذف نقش را ندارید.'); }
      // <--- اعمال Policy (اجازه حذف نقش Super Admin را نمیدهد)

        $role->delete();
        return response()->json(null, 204);
    }

    /**
     * Sync permissions to a role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        if (!auth()->user()->hasPermissionTo('assign-permissions-to-role')) { abort(403, 'شما اجازه اختصاص دسترسی به نقش را ندارید.'); }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);

        return response()->json(['message' => 'دسترسی‌ها با موفقیت اختصاص یافتند.'], 200);
    }
}
