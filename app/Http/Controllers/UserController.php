<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]);

        return Inertia::render('Users/UserIndex', ['users' => $users]);
    }

    public function userInformation(User $user)
    {
        $authUser = $this->isDeveloper(Auth::id());
        $viewedUser = $this->isDeveloper($user->id);

        if(!$authUser && $viewedUser) {
            return redirect()->route('user');
        }

        $user->load(['roles.permissions', 'permissions']);
        $roleList = Role::where('name', '!=', 'Developer')
               ->select('id', 'name')
               ->get();
        $permissionList = Permission::select('id', 'name')
            ->get();
        
        return Inertia::render('Users/UserInformation', [
            'user' => $user->only([
                'id', 
                'name', 
                'email',
                'created_at',
                'updated_at'
            ]),
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')
                ];
            }),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
            'roleList' => $roleList,
            'permissionList' => $permissionList,
        ]);
    }

    public function assignRole(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',
        ]);
    
        $existingRoles = $this->getUserRoles($request->userId);
    
        $validated = $request->validate([
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::notIn($existingRoles)
            ]
        ]);
    
        $user = User::findOrFail($request->userId);
        $user->assignRole($validated['roleName']);
    
        return redirect()->back()->with('message', "Role '{$validated['roleName']}' assigned successfully");

    }

    public function assignPermission(Request $request)
    {
        $validated = $request->validate([
            'userId' => 'required|exists:users,id',
            'permissionName' => [
                'required',
                'string',
                'exists:permissions,name',
                function ($attribute, $value, $fail) use ($request) {
                    $user = User::find($request->userId);
                    if ($user->hasPermissionTo($value)) {
                        $fail("User already has the '{$value}' permission");
                    }
                },
            ],
        ]);
    
        $user = User::find($validated['userId']);
        $user->givePermissionTo($validated['permissionName']);
    
        return redirect()->back()->with('message', "Permission '{$validated['permissionName']}' assigned successfully");
    }

    public function revokeRole(Request $request)
    {
        $validated = $request->validate([
            'param.userId' => 'required|exists:users,id',
            'param.role' => 'required|array',
            'param.role.id' => 'required|exists:roles,id',
            'param.role.name' => 'required|string',
        ]);

        $userId = $validated['param']['userId'];
        $roleName = $validated['param']['role']['name'];

        $user = User::findOrFail($userId);
        $user->removeRole($roleName);

        return redirect()->back()->with('message', "Role '{$roleName}' has been successfully removed from the user.");
    }

    public function revokePermission(Request $request) {
        $validated = $request->validate([
            'param.userId' => 'required|exists:users,id',
            'param.permission' => 'required|string',
        ]);

        $userId = $validated['param']['userId'];
        $permissionName = $validated['param']['permission'];

        $user = User::findOrFail($userId);
        $user->revokePermissionTo($permissionName);

        return redirect()->back()->with('message', "Permission '{$permissionName}' has been successfully removed from the user.");
    }

    public function updateUserInformation(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email,' . $request['id'],
        ]);

        $user = User::findOrFail($validated['id']);
        $user->update(['name' => $validated['name'], 'email' => $validated['email']]);

        return redirect()->back()->with('message', "User has been successfully updated.");
    }

    public function userNewPassword(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        
        $user = User::findOrFail($validated['id']);
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('message', "User has been changed the password successfully.");
    }

    public function destroy(User $user)
    {   
        DB::transaction(function() use ($user) {
            $user->delete();
        });
        
        return redirect()->back()->with([
            'message' => 'User deleted successfully',
            'status' => 'success'
        ]);
    }

    protected function getUserRoles($userId)
    {
        return User::find($userId)->roles->pluck('name')->toArray();
    }

    protected function isDeveloper($userId)
    {
        return in_array('Developer', $this->getUserRoles($userId));
    }
}
