<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Constants\Response;

// --- Public Routes ---
Route::post('login', [LoginController::class, 'login']);

// --- Protected Routes (Require Authentication) ---
Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::get('logout', [LoginController::class, 'logout']);

    // --- User Management Group ---
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:view users');
        Route::get('profile', 'profile');
        Route::post('upload-photo', 'uploadPhoto');
        Route::post('change-password', 'changePassword');
        Route::put('update-profile', 'update');
        Route::put('update-user-profile/{userId}', 'update_user');
        Route::delete('{user}', 'destroy')->middleware('role:Admin');
    });

    // --- Shop Management Group ---
    Route::prefix('shops')->controller(ShopController::class)->group(function () {
        // Index handles its own internal role-filtering logic
        Route::get('/', 'index')->middleware('permission:view shops');
        Route::get('{shop}', 'show')->middleware('permission:view shops');
        Route::post('/new', 'store')->middleware('permission:create shops');
        

        // Update/Delete (Ownership logic handled inside the controller)
        Route::post('update/{shop}', 'update')->middleware('permission:edit shops');
        Route::delete('{shop}', 'destroy')->middleware('permission:delete shops');
    });

});

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    // Role CRUD
    Route::apiResource('roles', RoleController::class);
    
    Route::apiResource('permissions', PermissionController::class);
});


// --- Fallback for 404 Errors ---
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Resource does not exist, please check your API.',
        'data' => null
    ], 404);
});
