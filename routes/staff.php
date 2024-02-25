<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\EmployeeAuthController as AuthController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);
Route::get('/reset', [AuthController::class, 'reset'])->name('password.reset');


Route::middleware(['auth:guard_employee', 'verify.jwt'])->group(function () {
    // Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail']);
    // Route::post('/email/verification-notification', [AuthController::class, 'verificationNotification']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('drinks/scheme', [\App\Http\Controllers\DrinkController::class, 'scheme']);
    Route::apiResource('drinks', \App\Http\Controllers\DrinkController::class);
    Route::apiResource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::get('categories/{category}/drinks', [\App\Http\Controllers\DrinkCategoryController::class, 'drinks']);
    Route::apiResource('categories', \App\Http\Controllers\DrinkCategoryController::class);
    Route::apiResource('drink-units', \App\Http\Controllers\DrinkUnitController::class);
    Route::get('/me', function (Request $request) {
        return [$payload = auth()->payload(), Auth::user()];
    });

});