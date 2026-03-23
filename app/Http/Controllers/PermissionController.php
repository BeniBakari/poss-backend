<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Http\Constants\Response;

class PermissionController extends Controller
{
    /**
     * List all permissions for the Flutter list.
     */
    public function index()
    {
        $permissions = Permission::orderBy('name', 'asc')->get();
        return Response::response('Permissions retrieved', $permissions);
    }

    /**
     * Create a new permission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return Response::getResourceCreatedResponse('Permission', $permission);
    }

    /**
     * Delete a permission.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return Response::getResponseMessage(true, 'Permission deleted', 200);
    }
}
