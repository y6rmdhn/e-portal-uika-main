<?php

use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfoUserController;
use App\Http\Controllers\LinkItemsController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UsersManagement;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middlewares\RoleMiddleware;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/  

Route::group(['middleware' => 'auth'], function () {
	Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
	Route::get('/dashboard/getItems', [DashboardController::class, 'getItems'])->name('getItems');
  
	Route::get('/logout', [SessionsController::class, 'destroy']);
	Route::get('/user-profile', [InfoUserController::class, 'create']);
	Route::post('/user-profile', [InfoUserController::class, 'store']);

	Route::middleware(['can:users-management'])->group(function () {
    Route::get('/user-management', [UsersManagement::class, 'index'])->name('user-management');
    Route::get('/admins', [UsersManagement::class, 'getAdmins'])->name('admin-management');
    Route::get("/show-user/{id}", [UsersManagement::class, 'showUser'])->name('show-user');
    Route::post('/create-user', [UsersManagement::class, 'createUser'])->name('create-user');
    Route::post('/update-user/{id}', [UsersManagement::class, 'updateUser'])->name('update-user');
    Route::delete("/delete-user/{id}", [UsersManagement::class, 'deleteUser'])->name("delete-user");

		Route::get("/link-items", [LinkItemsController::class, 'index'])->name('link-items');
    Route::get('/show-link-item/{id}', [LinkItemsController::class, 'showItem'])->name('show-link-item');
		Route::post("/create-link-item", [LinkItemsController::class, 'createItem'])->name('create-link-items');
		Route::delete("/delete-link-item/{id}", [LinkItemsController::class, 'deleteItem'])->name('delete-link-item');
		Route::post("/update-link-item/{id}", [LinkItemsController::class, 'updateItem'])->name("update-link-item");


    Route::get('/admins', [UsersManagement::class, 'getAdmins'])->name('admin-management');
    Route::get('/roles', [SystemController::class, 'roles'])->name('roles');
    Route::post('/create-roles', [SystemController::class, 'createRole'])->name('create-roles');
    Route::delete('/delete-roles/{id}', [SystemController::class, 'deleteRole'])->name('delete-roles');
    Route::get('/show-roles/{id}', [SystemController::class, 'showRole'])->name('show-roles');
    Route::post('/update-roles/{id}', [SystemController::class, 'updateRole'])->name('update-roles');
    Route::get('/permissions', [SystemController::class, 'permissions'])->name('permissions');
    Route::post('/permissions', [SystemController::class, 'createPermission'])->name('create-permissions');
    Route::get('/show-permission/{id}', [SystemController::class, 'showPermission'])->name('show-permission');
    Route::post('/update-permission/{id}', [SystemController::class, 'updatePermission'])->name('update-permission');
    Route::delete('/delete-permission/{id}', [SystemController::class, 'deletePermission'])->name('delete-permission');
    Route::get('/role-permissions', [SystemController::class, 'rolePermission'])->name('role-permissions');
    Route::delete('/delete-role-permission/{roleId}/{permissionId}', [SystemController::class, 'deleteRolePermission'])->name('delete-role-permission');
    Route::get('/data-roles-permissions', [SystemController::class, 'getRolePermission'])->name('data-roles-permissions');
    Route::post('/create-role-permission', [SystemController::class, 'createRoleHasPermission'])->name('create-role-permission');
	});
});

Route::group(['middleware' => 'guest'], function () { 
	Route::get('/about', [HomeController::class, 'about']);
	Route::get('/blogs', [HomeController::class, 'blogs']);

    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']); 
    Route::post('/session', [SessionsController::class, 'store']);
	Route::get('/login/forgot-password', [ResetController::class, 'create']);
	Route::post('/forgot-password', [ResetController::class, 'sendEmail']);
	Route::get('/reset-password/{token}', [ResetController::class, 'resetPass'])->name('password.reset');
	Route::post('/reset-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');

});

Route::get('/', function () {
  return view('login', [  
    'user' => null,
    'token_portal' => null,
    'name' =>  null,
    'password' => null
  ]);
});
Route::get('/login', function () {
    return view('login', [  
    'user' => null,
    'token_portal' => null,
    'name' =>  null,
    'password' => null
  ]);
})->name('login'); 

// Route::resource('permissionsx', PermissionController::class);



// Route::get('/regist', function () {
//   return view('regist');
// })->name('regist');
// Route::get('/forget', function () {
//   return view('forget');
// })->name('forget');

// Untuk redirect ke Google 
Route::get('/auth/google/redirect', [SessionsController::class, 'redirect'])->name('redirect');

// Untuk callback dari Google
Route::get('/auth/google/callback', [SessionsController::class, 'callback'])
    // ->middleware(['guest'])
    ->name('callback'); 