<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Constants\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with their permissions.
     */
    public function index()
    {
        $roles = Role::orderBy('name')->with('permissions')->get();
        return Response::response('Roles retrieved successfully', $roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array', // Array of permission names
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'guard_name' => 'web'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return Response::getResourceCreatedResponse('Role', $role->load('permissions'));
    }

    /**
     * Update an existing role and its permissions.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role->update($request->only('name', 'description'));

        if ($request->has('permissions')) {
            // syncPermissions replaces all old permissions with the new list
            $role->syncPermissions($request->permissions);
        }

        return Response::response('Role updated successfully', $role->load('permissions'));
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting core system roles
        if (in_array($role->name, ['Admin', 'Worker'])) {
            return Response::response('Core roles cannot be deleted.', null, 403);
        }

        $role->delete();
        return Response::response('Role deleted', null);
    }
}
