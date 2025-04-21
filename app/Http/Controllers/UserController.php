<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
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
        $user->load(['roles.permissions', 'permissions']);
        $roleList = Role::where('name', '!=', 'Developer')
               ->select('id', 'name')
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
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
            'roleList' => $roleList,
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

    public function updateUserInformation(Request $request)
    {
        $validated = $request->validate([
            'param.id' => 'required|exists:users,id',
            'param.name' => 'required|string',
            'param.email' => 'required|string|email|unique:users,email,' . $request['param']['id'],
        ]);

        $userId = $validated['param']['id'];
        $userName = $validated['param']['name'];
        $userEmail = $validated['param']['email'];

        $user = User::findOrFail($userId);
        $user->update(['name' => $userName, 'email' => $userEmail]);

        return redirect()->back()->with('message', "User has been successfully updated.");
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
}
