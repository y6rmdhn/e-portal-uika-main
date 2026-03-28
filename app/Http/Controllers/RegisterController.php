<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function create()
    {
        return view('session.register');
    }

    public function store()
    {
        $attributes = request()->validate([
            'name' => ['required', 'max:50'],
            'phone' => ['required', 'max:14'],
            'email' => ['required', 'email', 'max:50', Rule::unique('users', 'email')],
            'password' => ['required', 'min:5', 'max:20'],
            'agreement' => ['accepted']
        ]);
        $attributes['password'] = bcrypt($attributes['password'] );

        // session()->flash('success', 'Your account has been created.');
        $user = User::create($attributes);
        Auth::login($user); 
        return redirect('/dashboard');
    }

    public function registerDosen(Request $request)
    {
        try {
            $attributes = $request->validate([
                'npm_nidn' => ['required'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:6'],
                'password2' => ['required', 'min:6', 'same:password'], 
            ]);
            $url = env('TIAS_API_URL') . '/auth/register';

        
            $response = Http::post($url, $attributes);

            if($response->status() === 200){
                return response()->json(['message' => 'Registration successful', 'code' => 201]);
            } else if($response->status() === 400){
                return response()->json(['message' => $response->body(), 'code' => 400]);
            } else if($response->status() === 500){
                return response()->json(['message' => 'internal server error', 'code' => 500], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 500]);
        }
    }

    public function registerMhs(Request $request)
    {
        try {
            $attributes = $request->validate([
                'npm_nidn' => ['required'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:6'],
                'password2' => ['required', 'min:6', 'same:password'], 
            ]);
            $url = env('TIAS_API_URL') . '/auth/register';

        
            $response = Http::post($url, $attributes);
            if($response->status() === 200){
                return response()->json(['message' => 'Registration successful', 'code' => 201]);
            } else if($response->status() === 400){
                return response()->json(['message' => $response->body(), 'code' => 400]);
            } else if($response->status() === 500){
                return response()->json(['message' => 'internal server error', 'code' => 500], 500);
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 500]);
        }
    }

    public function registerPmm(Request $request)
    {
        try {
            $attributes = $request->validate([
                'npm' => ['required'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:6'],
            ]);
            $url = env('TIAS_API_URL') . '/auth/register-pmm';

        
            $response = Http::post($url, $attributes);
            if($response->status() === 200){
                return response()->json(['message' => 'Registration successful', 'code' => 201]);
            } else if($response->status() === 400){
                return response()->json(['message' => $response->body(), 'code' => 400]);
            } else if($response->status() === 500){
                return response()->json(['message' => 'internal server error', 'code' => 500], 500);
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 500]);
        }
    }

    public function registerDosenExt(Request $request)
    {
        try {
            $attributes = $request->validate([
                'nama_lengkap' => ['required'],
                'jenkel' => ['required'],
                'tanggal_lahir' => ['required'],
                'tempat_lahir' => ['required'],
                'agama' => ['required'],
                'no_hp' => ['required'],
                'instansi' => ['required'],
                'nip' => ['required'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:6'],
                'password2' => ['required', 'min:6'],
            ]);
            $url = env('TIAS_API_URL') . '/auth/register-dosen-ext';

        
            $response = Http::post($url, $attributes);
            if($response->status() === 200){
                return response()->json(['message' => 'Registration successful', 'code' => 201]);
            } else if($response->status() === 400){
                return response()->json(['message' => $response->body(), 'code' => 400]);
            } else if($response->status() === 500){
                return response()->json(['message' => 'internal server error', 'code' => 500], 500);
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 500]);
        }
    }    

    public function registerPegawai(Request $request)
    {
        try {
            $attributes = $request->validate([
                'nip' => ['required'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'min:6'],
                'password2' => ['required', 'min:6'],
            ]);
            $url = env('TIAS_API_URL') . '/auth/register-pegawai';

        
            $response = Http::post($url, $attributes);
            if($response->status() === 200){
                return response()->json(['message' => 'Registration successful', 'code' => 201]);
            } else if($response->status() === 400){
                return response()->json(['message' => $response->body(), 'code' => 400]);
            } else if($response->status() === 500){
                return response()->json(['message' => 'internal server error', 'code' => 500], 500);
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 500]);
        }
    }  
}
