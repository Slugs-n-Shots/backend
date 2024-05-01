<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GuestAuthController as AuthController;
use App\Http\Controllers\DrinkController;
use App\Http\Controllers\GuestController as GuestController;
use App\Http\Controllers\OrderController;

Route::post('/register', [AuthController::class, 'register']); // +regisztráció
Route::post('/confirm-registration', [AuthController::class, 'confirmRegistration']); // +regisztráció

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // +elfelejtett jelszó emlékeztető levél küldés
Route::post('/reset-password', [AuthController::class, 'resetPassword']); // +elfelejtett jelszó, változtatás change-forgotten-password

Route::post('/verify/resend', [AuthController::class, 'resendEmailVerificationMail']) // email megerősítés újraküldése
    ->middleware(['throttle:6,1']);

Route::get('/refresh', [AuthController::class, 'refresh'])->middleware(['refresh.jwt']); // token frissítés

Route::get('/menu', [DrinkController::class, 'menu']);
Route::get('/menu-tree', [DrinkController::class, 'menuTree']);
Route::get('/drinks/card/{drink}', [DrinkController::class, 'card']);

Route::middleware(['auth:guard_guest'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [GuestController::class, 'me']);
    Route::post('/me', [GuestController::class, 'updateSelf']);
    Route::post('/update-password', [GuestController::class, 'updatePassword']);
    Route::post('/orders', [OrderController::class, 'makeOrder']);
});
