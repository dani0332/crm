<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Constructor for PermissionController
     */
    public function __construct()
    {
        $this->middleware('permission:permission-list', ['only' => ['index', 'show']]);
    }

    /**
     * Display a listing of the permissions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Start with base query
        $query = Permission::select('*');

        // Filter by role if role_id is provided
        if ($request->has('role_id')) {
            $roleId = $request->role_id;
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        // Filter by name if provided
        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%'.$request->name.'%');
        }

        // Get roles for the dropdown filter
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->get();

        $permissions = $query->orderBy('name', 'asc')->simplePaginate();

        return inertia('Admin/Permissions/Index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'filters' => [
                'role_id' => $request->role_id,
                'name' => $request->name,
            ],
        ]);
    }

    /**
     * Show the form for creating a new permission.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Admin/Permissions/Form');
    }

    /**
     * Store a newly created permission in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'name' => $request->input('name'),
            'guard_name' => $request->input('guard_name', 'web'),
        ]);

        return redirect(route('permissions.show', $permission->id))
            ->with('success', 'Permission has been created successfully');
    }

    /**
     * Display the specified permission.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $permission = Permission::findOrFail($id);

        // Get roles that have this permission
        $rolesWithPermission = \Spatie\Permission\Models\Role::with('permissions')
            ->whereHas('permissions', function ($q) use ($id) {
                $q->where('permissions.id', $id);
            })
            ->get();

        return inertia('Admin/Permissions/Show', [
            'permission' => $permission,
            'rolesWithPermission' => $rolesWithPermission,
        ]);
    }

    /**
     * Show the form for editing the specified permission.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return inertia('Admin/Permissions/Form', [
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified permission in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|unique:permissions,name,'.$id,
            'guard_name' => 'nullable|string',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->name = $request->input('name');

        if ($request->has('guard_name')) {
            $permission->guard_name = $request->input('guard_name');
        }

        $permission->save();

        // Clear permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect(route('permissions.show', $permission->id))
            ->with('success', 'Permission has been updated successfully');
    }

    /**
     * Remove the specified permission from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();

            // Clear permission cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect()->route('permissions.index')
                ->with('success', 'Permission has been deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('permissions.index')
                ->with('error', 'This permission cannot be deleted as it is in use');
        }
    }
}
