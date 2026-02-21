<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // <--- Model User
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // <--- برای هش کردن پسورد
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Policy manageUsers به روت group در web.php اعمال شده.
        // اینجا authorize برای create و delete را مستقیما در متدها اعمال میکنیم.
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
         if (!auth()->user()->hasPermissionTo('view-users')) { abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.'); }


        return Inertia::render('Admin/UserList', [
            'users' => User::with('role')->get(),
            'allRoles' => Role::all() // <--- این خط اضافه شد
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
    if (!auth()->user()->hasPermissionTo('create-user')) { abort(403, 'شما اجازه ایجاد کاربر جدید را ندارید.'); }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|phone:IR|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id', // <--- تغییر: role_id به جای role
        ], [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید متن باشد.',
            'name.max' => 'نام نباید بیش از 255 کاراکتر باشد.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.string' => 'شماره موبایل باید متن باشد.',
            'phone.phone' => 'شماره موبایل معتبر نیست.',
            'phone.max' => 'شماره موبایل نباید بیش از 20 کاراکتر باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.string' => 'رمز عبور باید متن باشد.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'role_id.required' => 'نقش کاربر الزامی است.',
            'role_id.exists' => 'نقش انتخاب شده معتبر نیست.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id, // <--- تغییر: role_id
        ]);

        return response()->json($user->load('role'), 201); // <--- role را هم لود میکنیم
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
    if (!auth()->user()->hasPermissionTo('delete-user')) { abort(403, 'شما اجازه حذف کاربر را ندارید.'); }
        
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Display the specified user as JSON.
     */
    public function show(User $user)
    {
        if (!auth()->user()->hasPermissionTo('view-users')) { abort(403, 'شما اجازه مشاهده کاربر را ندارید.'); }
        return response()->json($user->load('role'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasPermissionTo('edit-user')) { abort(403, 'شما اجازه ویرایش کاربر را ندارید.'); }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required','string','phone:IR','max:20', Rule::unique('users','phone')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ], [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید متن باشد.',
            'name.max' => 'نام نباید بیش از 255 کاراکتر باشد.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.string' => 'شماره موبایل باید متن باشد.',
            'phone.phone' => 'شماره موبایل معتبر نیست.',
            'phone.max' => 'شماره موبایل نباید بیش از 20 کاراکتر باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.string' => 'رمز عبور باید متن باشد.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'role_id.required' => 'نقش کاربر الزامی است.',
            'role_id.exists' => 'نقش انتخاب شده معتبر نیست.',
        ]);

        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user->load('role'), 200);
    }

    // <--- متد جدید برای آپدیت نقش کاربر (برای آینده)
    public function updateRole(Request $request, User $user)
    {
    if (!auth()->user()->hasPermissionTo('edit-user-role')) { abort(403, 'شما اجازه ویرایش نقش کاربر را ندارید.'); }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ], [
            'role_id.required' => 'نقش کاربر الزامی است.',
            'role_id.exists' => 'نقش انتخاب شده معتبر نیست.',
        ]);

        $user->update(['role_id' => $request->role_id]);

        return response()->json($user->load('role'), 200);
    }
}
