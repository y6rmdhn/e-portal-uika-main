<?php

use App\Http\Controllers\Api\SsoController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', 'Api\AuthController@auth');
Route::post('/auth/login/tias', 'Api\AuthController@authTias');
Route::post('/register', 'Api\AuthController@register');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id) {
    // Cari user berdasarkan ID
    $user = User::findOrFail($id);

    // Cek apakah hash-nya valid
    if (! hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kedaluwarsa.'], 400);
    }

    // Jika belum verifikasi, tandai sudah verifikasi
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    // url for development
    return redirect('http://localhost:5173/login?verified=true');
})->middleware(['signed'])->name('verification.verify');

Route::post('/password/email', 'Api\AuthController@sendResetLinkEmail');
Route::post('/password/reset', 'Api\AuthController@resetPassword');

Route::get('/auth/google/redirect', 'Api\AuthController@redirectToGoogle');
Route::get('/auth/google/callback', 'Api\AuthController@handleGoogleCallback');

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('/logout', 'Api\AuthController@logout');
    Route::get('/get_user', 'Api\AuthController@get_user');
    Route::get('/refresh', 'Api\AuthController@refresh');

    Route::get('/app_modul', 'Api\AppModuleController@index');
    Route::get('/tx_user_modul_permission', 'Api\TxUserModulPermissionController@index');
    Route::get('/call_user', 'Api\AuthController@call_user');

    Route::post('/sso/generate-ticket', [SsoController::class, 'generateTicket']);
});

// Endpoint untuk Express
Route::post('/sso/verify-ticket', [SsoController::class, 'verifyTicket']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
