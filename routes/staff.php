<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\EmployeeAuthController as AuthController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);
Route::get('/reset', [AuthController::class, 'reset']);


Route::get('/refresh', [AuthController::class, 'refresh'])->middleware(['refresh.jwt']);
// Route::get('/refresh', [AuthController::class, 'refresh']);
Route::get('categories/parents', [\App\Http\Controllers\DrinkCategoryController::class, 'parents']);
Route::get('categories', [\App\Http\Controllers\DrinkCategoryController::class, 'index']);
Route::get('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'show']);
Route::get('employees/roles', [\App\Http\Controllers\EmployeeController::class, 'roles']);

Route::middleware(['auth:guard_employee'])->group(function () {
    // Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail']);
    // Route::post('/email/verification-notification', [AuthController::class, 'verificationNotification']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/menu', [\App\Http\Controllers\DrinkController::class, 'menu']);
    Route::get('/menu-tree', [\App\Http\Controllers\DrinkController::class, 'menuTree']);

    Route::get('drinks/scheme', [\App\Http\Controllers\DrinkController::class, 'scheme']);
    Route::apiResource('drinks', \App\Http\Controllers\DrinkController::class);


    Route::post('categories', [\App\Http\Controllers\DrinkCategoryController::class, 'store']);
    Route::put('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'update']);
    Route::delete('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'destroy']);

    Route::apiResource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::apiResource('guests', \App\Http\Controllers\GuestController::class);
    Route::get('categories/{category}/drinks', [\App\Http\Controllers\DrinkCategoryController::class, 'drinks']);
    Route::apiResource('categories', \App\Http\Controllers\DrinkCategoryController::class);
    Route::apiResource('drink-units', \App\Http\Controllers\DrinkUnitController::class);
    Route::get('/me', [\App\Http\Controllers\EmployeeController::class, 'me']);
    Route::get('orders/active', [\App\Http\Controllers\OrderController::class, 'activeOrders']);
    Route::get('orders/active/{status}', [\App\Http\Controllers\OrderController::class, 'activeOrders']);
    Route::get('orders/waiting', [\App\Http\Controllers\OrderController::class, 'waitingOrders']);
    Route::post('orders/assign/{order_id}', [\App\Http\Controllers\OrderController::class, 'assignOrder']);
    Route::get('orders/lastid', [\App\Http\Controllers\OrderController::class, 'lastOrderId']);
});
