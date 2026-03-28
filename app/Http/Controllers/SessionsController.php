<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Http;

use App\Http\Helper\ResponseBuilder;


class SessionsController extends Controller
{
    public function create()
    {
        return view('session.login-session');
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt(['name' => $attributes['name'], 'password' => $attributes['password']])) {
            session()->regenerate();
            return redirect('dashboard')->with(['success' => 'You are logged in.']);
        } else {
            return back()->withErrors(['name' => 'Username or password invalid.']);
        }
    }
    
    public function destroy()
    {

        Auth::logout();

        return redirect('/')->with(['success'=> "You've been logged out."]);
    }


    public function redirect()
    {
        return Socialite::driver('google')->redirect();  
    }
    public function callback()
    {
        // Google user object dari google
        $userFromGoogle = Socialite::driver('google')->user(); 

        $get = Http::post('https://api-tias.ti.ft.uika-bogor.ac.id/auth/eportal/google', [
            'email' => $userFromGoogle['email'],
            'token_google' => $userFromGoogle->token
        ]);
        $data = json_decode($get->body(), true);   
 

        if($data['message'] == 'Login Success.'){
            if($data['data']['role'] == 'Admin'){
                $name = 'Portal_Uika2024!';
                $pass = 'Portal_Uika2024!';
            }else{
                $name = 'guido';
                $pass = 'Portal_Uika2024!';
            }

            try {    
                if (! $token = JWTAuth::attempt([
                    'name' => $name,
                    'password' => $pass
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
 
            return view('login', [ 
                'user' => $data['data'],
                'token_portal' => $token,
                'name' =>  $name,
                'password' => $pass
            ]);
            
        }else{
            return response()->json($data, 200);
        } 
    } 
}
