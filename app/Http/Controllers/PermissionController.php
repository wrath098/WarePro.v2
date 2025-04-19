<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all()
            ->map(fn($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guardName' => $permission->guard_name,
                'createdAt' => Carbon::parse($permission->created_at)->format('F d, Y'),
            ]);
        return Inertia::render('Users/PermissionsIndex', [
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'permissionName' => 'required|string|unique:permissions,name',
            ]);

            $formatted = Str::slug($request->permissionName);
    
            Permission::create(['name' => $formatted]);
            
            DB::commit();
            return back()->with('message', 'Permission created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Creating a Permission Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create permission. Please try again.');
        }
    }

    public function destroy(Permission $permission)
    {
        DB::beginTransaction();
        try {
            $permission->delete();
            
            DB::commit();
            return back()->with('message', 'Permission deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Deletion of Permission Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Deletion of Permission failed. Please try again!');
        }
    }
}
