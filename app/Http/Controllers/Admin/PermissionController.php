<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Policy را در متدهای مربوطه اعمال میکنیم
    }

    /**
     * Display a listing of the permissions.
     */
    public function index()
    {
    if (!auth()->user()->hasPermissionTo('view-roles')) { abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.'); }
        return response()->json(Permission::all());
    }
}
