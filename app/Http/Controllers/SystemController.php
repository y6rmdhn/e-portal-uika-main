<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleHasPermission;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    public function roles(Request $request) {
        if ($request->ajax()) {
            $searchQuery = $request->get('query');
            $roles = Role::select('*');
            if ($searchQuery) { 
                $roles = $roles->where(function($query) use ($searchQuery) { 
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                });
            }
    
            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('action', function($role) {
                    return '<span class="editRoleBtn mx-3" data-id="' . $role->id . '">
                                <i class="cursor-pointer fas fa-user-edit text-secondary"></i>
                            </span>
                            <span class="deleteRoleBtn" data-id="' . $role->id . '">
                                <i class="cursor-pointer fas fa-trash text-secondary"></i>
                            </span>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('system.roles');
    }

    public function createRole(Request $request){
        try {
            $validatedData = $request->validate([
                'name' => 'required|unique:roles,name|max:255', 
            ]);
    
            Role::create([
                'name' => $validatedData['name'],
            ]);
            return redirect()->back();

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        }
    }

    public function deleteRole(Request $request, $id){
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
    
        $role->delete();
    
        return response()->json(['message' => 'Role deleted successfully'], 200);
    }
    
    public function showRole($id){
        $role = Role::findOrFail($id);
        
        return response()->json($role);
    }

    public function updateRole(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ], [
            'name.unique' => 'The role name has already been taken.',
        ]);
    
        $role = Role::findOrFail($id);
    
        $role->name = $request->name;
        $role->save();
    
        return redirect()->back(); 
    }
    
    public function permissions(Request $request){
        if ($request->ajax()) {
            $searchQuery = $request->get('query');
            $permission = Permission::select('*');
            if ($searchQuery) { 
                $permission = $permission->where(function($query) use ($searchQuery) { 
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                });
            }
    
            return DataTables::of($permission)
                ->addIndexColumn()
                ->addColumn('action', function($item) {
                    return '<span class="editPermisBtn mx-3" data-id="' . $item->id . '">
                                <i class="cursor-pointer fas fa-user-edit text-secondary"></i>
                            </span>
                            <span class="deletePermisBtn" data-id="' . $item->id . '">
                                <i class="cursor-pointer fas fa-trash text-secondary"></i>
                            </span>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('system.permissions');

    }

    public function createPermission(Request $request){
        try {
            $validatedData = $request->validate([
                'name' => 'required|unique:roles,name|max:255', 
            ]);
    
            Permission::create([
                'name' => $validatedData['name'],
            ]);
            return redirect()->back();

        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        }
    }

    public function showPermission($id){
        $role = Permission::findOrFail($id);
        
        return response()->json($role);
    }

    public function updatePermission(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ], [
            'name.unique' => 'The role name has already been taken.',
        ]);
    
        $role = Permission::findOrFail($id);
    
        $role->name = $request->name;
        $role->save();
    
        return redirect()->back(); 
    }

    public function deletePermission(Request $request, $id){
        $role = Permission::find($id);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }
    
        $role->delete();
    
        return response()->json(['message' => 'Permission deleted successfully'], 200);
    }

    public function rolePermission(Request $request){
        $searchQuery = $request->input('search.value');

        $query = RoleHasPermission::query()->with(['role', 'permission']);
    
        if (!empty($searchQuery)) { 
            $query->whereHas('role', function($roleQuery) use ($searchQuery) {
                $roleQuery->where('name', 'like', '%' . $searchQuery . '%');
            })
            ->orWhereHas('permission', function($permissionQuery) use ($searchQuery) {
                $permissionQuery->where('name', 'like', '%' . $searchQuery . '%');
            });
        }
    
        $rolePermissions = $query->get();
        return DataTables::of($rolePermissions)
            ->addIndexColumn()
            ->addColumn('role_name', function($rolePermission) {
                return $rolePermission->role->name;
            })
            ->addColumn('permission_name', function($rolePermission) {
                return $rolePermission->permission->name;
            })
            ->addColumn('action', function($rolePermission) {
                return '<span class="deleteRolePermisBtn" data-idrole="' . $rolePermission->role_id .'" data-idpermis="' . $rolePermission->permission_id .'">
                            <i class="cursor-pointer fas fa-trash text-secondary"></i>
                        </span>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function deleteRolePermission($roleId, $permissionId){
        try {
            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
    
            return response()->json(['message' => 'Role permission deleted successfully']);
        } catch (\Exception $e) {
            // Tangani kesalahan jika terjadi
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getRolePermission()
    {
        $roles = Role::all();
        $permissions = Permission::all();
    
        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function createRoleHasPermission(Request $request) {

        try {
            $validatedData = $request->validate([
                'roleid' => 'required', 
                'permissionid' => 'required', 
            ]);

            RoleHasPermission::create([
                'permission_id' => $validatedData['permissionid'],
                'role_id' => $validatedData['roleid'],
            ]);
            return redirect()->back();
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        }
            
    }
    
}
