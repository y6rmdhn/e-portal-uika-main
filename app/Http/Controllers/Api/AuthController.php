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
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;
use Illuminate\Auth\Events\Registered;

use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // ambil data input
        $data = $request->only('name', 'email', 'password', 'role_id');

        // validasi data input
        $validator = Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|max:50',
            'role_id' => 'required'
        ]);

        //Kirim respons gagal jika permintaan tidak valid
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        // db transaction
        DB::beginTransaction();

        // Permintaan valid, buat pengguna baru.
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => $data['role_id']
            ]);

            event(new Registered($user));

            DB::commit();

            // kembalikan response sukses
            return response()->json([
                'status' => 201,
                'success' => true,
                'message' => 'User created successfully. Please check your email to verify your account.',
                'data' => $user
            ], 201);
        } catch (\Exception $th) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'System error: ' . $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function auth(Request $request)
    {

        // validasi input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Email and password must be filled in correctly.',
                'data' => $validator->errors()
            ], 400);
        }

        // mengambil email dan password dari request
        $credentials = $request->only('email', 'password');

        // cek verifikasi email
        $userCheck = User::where('email', $request->email)->first();

        if ($userCheck && !$userCheck->hasVerifiedEmail()) {
            return response()->json([
                'status' => 403,
                'message' => 'Login failed. Please verify your email first.',
                'data' => []
            ], 403);
        }

        try {

            if (! $token = FacadesJWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Incorrect email or password.',
                    'data' => []
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'A system error occurred, unable to create a token.',

                // for debugging purpose, you can uncomment the line below to see the actual error message
                // 'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }

        $user = FacadesJWTAuth::user();

        $cookie = cookie(
            'token',
            $token,
            1440,
            null,
            null,
            false,
            true
        );

        return ResponseBuilder::success(200, "Login successful", [
            'user' => $user,
            'token_portal' => $token
        ])->cookie($cookie);
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

        if ($data['message'] == 'Login Success.') {

            // Request is validated
            // Crean token
            if ($data['data']['role'] == 'Admin') {
                $email = 'su-admin@gmail.com';
                $pass = 'qwe123QWE!@#';
            } else {
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
        } else {
            return response()->json($data, 200);
            // return ResponseBuilder::success(200, "Email tidak terdaftar", null);
        }
    }

    public function logout(Request $request)
    {
        //Request is validated, do logout
        try {
            FacadesJWTAuth::parseToken()->invalidate();

            $cookie = cookie()->forget('token');

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'User logged out successfully',
                'data' => []
            ], 200)->withCookie($cookie);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Session has expired or token is invalid.',
                'data' => []
            ], 400);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred on the server while logging out.',
                'data' => []
            ], 500);
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
        try {
            $user = FacadesJWTAuth::user();

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to retrieve user data.',
                'data' => []
            ], 500);
        }
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        // generate token dan kirim email

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status == Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password reset link sent successfully. Please check your email.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Failed to send password reset link',
                'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred while sending the password reset link : ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'success' => false,
            'message' => $validator->errors()->first(),
            'data' => []
        ], 400);
}

        try {
            $status = Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Password has been reset successfully.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Failed to reset password. Please check your token and email.',
                'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'An error occurred while resetting the password : ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function redirectToGoogle(){
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(){
        try{

            // Ambil data user dari Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Cek apakah email ini sudah ada di database kita
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
            $pendingData = base64_encode(json_encode([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
            ]));
            
            // Redirect ke halaman register React
            return redirect('http://localhost:5173/register?social_data=' . $pendingData);
        }

            // Buat JWT Token
            $token = FacadesJWTAuth::fromUser($user);

            // Masukkan ke dalam HttpOnly Cookie
            $cookie = cookie('token', $token, 1440, null, null, false, true);

            // Bungkus data user jadi Base64 biar aman dikirim lewat URL ke React
            $userData = base64_encode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
            ]));

            // Redirect BALIK ke React (bawa data user di URL)
            return redirect('http://localhost:5173/auth/google/success?data=' . $userData)->withCookie($cookie);
        }catch (\Exception $e) {
            // Kalau batal/error, kembalikan ke halaman login React bawa pesan error
            return redirect('http://localhost:5173/login?error=GoogleLoginFailed');
        }
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
        if ($roleId && $appModuleId && $unitId) {
            $data = $data->where('role_id', $roleId)
                ->where('appModule_id', $appModuleId)
                ->where('unit_id', $unitId);
        } else {
            return ResponseBuilder::success(200, "error", 'Parameter yang di butuhkan tidak sesuai / harus diisi');
        }

        $data = $data->get();

        return ResponseBuilder::success(200, "success", [
            'user' => JWTAuth::user()->only('id', 'name', 'email', 'nidn', 'nip', 'npm', 'phone', 'location', 'is_active', 'image'),
            // 'user_module_permission' => $data,

            'detail' => $data,
            // 'permissions' => $data->pluck('appModul')->flatten()->pluck('permission')->flatten()->unique('id')->values(),

            // 'role_has_permission' => null,
            'token_eportal' => 'Bearer ' . $request->token
        ]);
    }
}
