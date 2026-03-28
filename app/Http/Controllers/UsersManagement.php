<?php

namespace App\Http\Controllers;

use App\Models\ModelHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DataTables;

class UsersManagement extends Controller
{

    public function __construct(){
        $this->middleware(['role:admin']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchQuery = $request->get('query');
            $users = User::select('*')->where('role_id', 2);
            if ($searchQuery) { 
                $users = $users->where(function($query) use ($searchQuery) { 
                    $query->where('name', 'like', '%' . $searchQuery . '%') 
                          ->orWhere('email', 'like', '%' . $searchQuery . '%') 
                          ->orWhere('phone', 'like', '%' . $searchQuery . '%'); 
                });
            }
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('action', function ($user) {
                    return '<span class="editUserBtn mx-3" data-id="' . $user->id . '">
                                <i class="cursor-pointer fas fa-edit text-secondary"></i>
                            </span>
                            <span class="deleteUserBtn" data-id="' . $user->id . '">
                                <i class="cursor-pointer fas fa-trash text-secondary"></i>
                            </span>
                            ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('system.user-management');
    }

    public function getAdmins()
    {
        $admins = User::where('role_id', 1)->get();
        return DataTables::of($admins)
        ->addColumn('action', function ($admin) {
            return '<span class="editAdminBtn mx-3" data-id="' . $admin->id . '">
                        <i class="cursor-pointer fas fa-edit text-secondary"></i>
                    </span>
                    <span class="deleteAdminBtn" data-id="' . $admin->id . '">
                        <i class="cursor-pointer fas fa-trash text-secondary"></i>
                    </span>
                    ';
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function createUser(Request $request)
    {
        try {
            $attributes = $request->validate([
                'name' => ['required', 'max:50'],
                'phone' => ['required', 'max:14'],
                'email' => ['required', 'email', 'max:50', Rule::unique('users', 'email')],
                'password' => ['required', 'min:5', 'max:20'],
                'role_id' => ['required'],
            ]);
    
            $attributes['password'] = bcrypt($attributes['password']);

            $createUser = User::create($attributes);

    
            if ($request->role_id == 1) {
                ModelHasRole::create([
                    'role_id' => 1,
                    'model_type' => 'App\Models\User',
                    'model_id' => $createUser->id 
                ]);
            }
    
            return redirect()->back()->with('success', 'User created successfully');
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        }
    }

    public function updateUser(Request $request, $id){
        try {
            $user = User::findOrFail($id);
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->email = $request->email;

            $user->save();

            return redirect()->back();
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        }
    }
    
    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::find($id);
    
            if (!$user) {
                return response()->json(['message' => "User not found"], 404);
            }
    
            if ($user->role_id === 1) {
                // Menghapus entri di tabel model_has_roles berdasarkan model_id
                ModelHasRole::where('model_id', $user->id)->delete();
            }
    
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
            
        } catch (\Throwable $th) {
            return response()->json(['message' => 'An error occurred', 'error' => $th->getMessage()], 500);
        }
    }
    

    public function showUser($id){
        $user = User::findOrFail($id);
        
        return response()->json($user);
    }
    
}
