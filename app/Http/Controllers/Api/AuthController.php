<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use JWTAuth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

use App\Models\User; 
use App\Models\TxUserModulPermission; 

use App\Http\Helper\ResponseBuilder;
use Illuminate\Support\Facades\DB; 

class AuthController extends Controller
{  
    public function register(Request $request)
    {
     //Validate data
        $data = $request->only('name', 'email', 'password', 'role');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50',
            'role' => 'required'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
         'name' => $request->name,
         'email' => $request->email,
         'password' => bcrypt($request->password),
         'role_id' => $request->role
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function auth(Request $request)
    {
        $credentials = $request->only('email', 'password'); 

        //valid credential  
        try {   

            if (! $token = JWTAuth::attempt([ 
                'email' => $request->email,
                'password' => $request->password
            ])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Login credentials are invalid.',
                    'data' => []
                ], 200);
            } 
        } catch (JWTException $e) {
            return $credentials;
            return response()->json([
                'status' => 200,
                'message' => 'Could not create token.',
                'data' => []
                ], 200);
        } 

        //Token created, return with success response and jwt token 
        return ResponseBuilder::success(200, "success", [  
            'token_portal' => $token,
        ]);   
    }

    public function authTias(Request $request)
    {
        $credentials = $request->only('email', 'password'); 

        //valid credential
        $get = Http::post('https://api-tias.ti.ft.uika-bogor.ac.id/auth/login', [
            'email' => $request->email,
            'password' => $request->password
        ]);
        $data = json_decode($get->body(), true);   

        if($data['message'] == 'Login Success.'){

            // Request is validated
            // Crean token
            if($data['data']['role'] == 'Admin'){
                $email = 'su-admin@gmail.com';
                $pass = 'qwe123QWE!@#';
            }else{
                $email = 'gutam.gt@gmail.com';
                $pass = 'qwe123QWE!@#';
            }
            
            try {   

                if (! $token = JWTAuth::attempt([
                    'email' => $email,
                    'password' => $pass
                    // 'email' => $request->email,
                    // 'password' => $request->password
                ])) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Login credentials are invalid.',
                        'data' => []
                    ], 200);
                } 
            } catch (JWTException $e) {
                return $credentials;
                return response()->json([
                    'status' => 200,
                    'message' => 'Could not create token.',
                    'data' => []
                    ], 200);
            } 
    
            //Token created, return with success response and jwt token
            $user = JWTAuth::user(); 
            return ResponseBuilder::success(200, "success", [ 
                'user' => $data['data'], 
                'token_portal' => $token,
            ]); 
        }else{
            return response()->json($data, 200);
            // return ResponseBuilder::success(200, "Email tidak terdaftar", null);
        }  
    }
 
    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) { 
            return ResponseBuilder::success(200, "error", $validator->messages()); 
        }

        //Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token); 
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refresh(Request $request)
    {

        $validator = Validator::make($request->only('token'), []);
        if ($validator->fails()) { 
            return ResponseBuilder::success(200, "error", $validator->messages()); 
        }
        return ResponseBuilder::success(200, "success", [ 
            'user' => JWTAuth::user(),
            'token' => JWTAuth::refresh(), 
        ]);   
    } 
 
    public function get_user(Request $request)
    {
        $validator = Validator::make($request->only('token'), []);
        if ($validator->fails()) { 
            return ResponseBuilder::success(200, "error", $validator->messages()); 
        }
 
        return ResponseBuilder::success(200, "success", JWTAuth::user());  
    }

    public function call_user(Request $request)
    {
        $validator = Validator::make($request->only('token'), []);
        if ($validator->fails()) { 
            return ResponseBuilder::success(200, "error", $validator->messages()); 
        }


        $unitId = $request->input('unit_id');
        $roleId = $request->input('role_id');
        $appModuleId = $request->input('appModule_id');
  
        $data = TxUserModulPermission::select(['appModule_id', 'role_id', 'unit_id'])
        ->where('user_id', JWTAuth::user()->id)
        ->with([
            'unit' => function ($q) {
                $q->select('id', 'name', 'status'); 
                // jangan lupa include 'unit_id' supaya relasi tetap bisa jalan
            },
            'role' => function ($q) {
                $q->select('id', 'name'); 
                // jangan lupa include 'role_id' supaya relasi tetap bisa jalan
            },
            'roleHasPermission',
            'appModul' => function ($q) {
                $q->select('id', 'name', 'url'); 
                // jangan lupa include 'app_modul_id' supaya relasi tetap bisa jalan
            }, 
            'appModul.permission' => function ($q) {
                $q->select('id', 'appModule_id', 'name'); 
                // jangan lupa include 'app_modul_id' supaya relasi tetap bisa jalan
            }
        ]); 
        if($roleId && $appModuleId && $unitId){
            $data = $data->where('role_id', $roleId)
            ->where('appModule_id', $appModuleId)
            ->where('unit_id', $unitId);
        }else{
            return ResponseBuilder::success(200, "error", 'Parameter yang di butuhkan tidak sesuai / harus diisi'); 
        } 

        $data = $data->get();

        return ResponseBuilder::success(200, "success", [
            'user' => JWTAuth::user()->only('id', 'name', 'email', 'nidn', 'nip', 'npm', 'phone', 'location', 'is_active', 'image'),
            // 'user_module_permission' => $data,

            'detail' => $data,
            // 'permissions' => $data->pluck('appModul')->flatten()->pluck('permission')->flatten()->unique('id')->values(),
 
            // 'role_has_permission' => null,
            'token_eportal' => 'Bearer '.$request->token
        ]);  
    }
}
