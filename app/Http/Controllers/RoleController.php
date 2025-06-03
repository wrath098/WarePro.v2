<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all()
        ->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'guardName' => $role->guard_name,
            'createdAt' => Carbon::parse($role->created_at)->format('F d, Y'),
        ]);
        return Inertia::render('Users/RolesIndex', [
            'roles' => $roles
        ]);
    }

    public function showRolePermissions(Role $role)
    {
        $role->load('permissions');

        $role->permissions = $role->permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'guardName' => $permission->guard_name,
                'createdAt' => Carbon::parse($permission->created_at)->format('m-d-Y'),
            ];
        });

        return Inertia::render('Users/PermissionsInRoles', [
            'role' => $role,
            'permissions' => $role->permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'roleName' => 'required|string|unique:roles,name',
        ]);

        DB::beginTransaction();
        try {
            $formatted = Str::of($request->roleName)->lower()->title();
    
            Role::create(['name' => $formatted]);
            
            DB::commit();
            return redirect()->back()->with('message', 'Role created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Creating a Role Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);

            return redirect()->back()->with('error', 'Failed to create Role. Please try again.');
        }
    }

    public function storeRolePermission(Request $request)
    {
        $request->validate([
            'role' => 'required|exists:roles,id',
            'permission' => 'required|integer',
            'permission.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();

        try
        {
            $role = Role::findOrFail($request->input('role'));
        
            $role->givePermissionTo($request->input('permission'));

            DB::commit();
            return redirect()->back()->with('message', 'A permission was assigned to the role successfully!');
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Assigning Permission to a Role Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);

            return redirect()->back()->with('error', 'Failed to assign permission to a Role. Please try again.');
        }
    }

    public function showAvailablePermissionsForRole(Request $request)
    {
        $roleId = $request->input('id');
        $assignedPermissionIds = Role::findOrFail($roleId)->permissions->pluck('id');
        $availablePermissions = Permission::whereNotIn('id', $assignedPermissionIds)->get();

        return response()->json($availablePermissions);
    }

    public function destroy(Role $role)
    {
        DB::beginTransaction();
        try {
            $role->delete();
            
            DB::commit();
            return redirect()->back()->with('message', 'Role deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Deletion of Role Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $role->toArray()
            ]);

            return redirect()->back()->with('error', 'Deletion of role failed. Please try again!');
        }
    }

    public function destroyRolePermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permission_id' => 'required|integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $permission = Permission::findOrFail($request->permission_id);

        $role->revokePermissionTo($permission);

        return back()->with('message', 'Permission removed from role successfully.');
    }
}
