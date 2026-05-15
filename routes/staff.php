<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmployeeAuthController as AuthController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);
Route::get('/reset', [AuthController::class, 'reset']);

Route::get('/refresh', [AuthController::class, 'refresh'])->middleware(['refresh.jwt']);
// Route::get('/refresh', [AuthController::class, 'refresh']);

// Publikus törzsadat segédlisták
Route::get('categories/parents', [\App\Http\Controllers\DrinkCategoryController::class, 'parents']);
Route::get('categories', [\App\Http\Controllers\DrinkCategoryController::class, 'index']);
Route::get('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'show']);
Route::get('employees/roles', [\App\Http\Controllers\EmployeeController::class, 'roles']);

Route::middleware(['auth:guard_employee'])->group(function () {
    // Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail']);
    // Route::post('/email/verification-notification', [AuthController::class, 'verificationNotification']);

    // Auth és profil
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [\App\Http\Controllers\EmployeeController::class, 'me']);

    // Menü
    Route::get('/menu', [\App\Http\Controllers\DrinkController::class, 'menu']);
    Route::get('/menu-tree', [\App\Http\Controllers\DrinkController::class, 'menuTree']);

    // Italok
    Route::get('drinks/scheme', [\App\Http\Controllers\DrinkController::class, 'scheme']);
    Route::apiResource('drinks', \App\Http\Controllers\DrinkController::class);

    // Kategóriák
    Route::post('categories', [\App\Http\Controllers\DrinkCategoryController::class, 'store']);
    Route::put('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'update']);
    Route::delete('categories/{category}', [\App\Http\Controllers\DrinkCategoryController::class, 'destroy']);
    Route::get('categories/{category}/drinks', [\App\Http\Controllers\DrinkCategoryController::class, 'drinks']);
    Route::apiResource('categories', \App\Http\Controllers\DrinkCategoryController::class);

    // Felhasználók
    Route::apiResource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::apiResource('guests', \App\Http\Controllers\GuestController::class);

    // Asztalok
    Route::post('tables/{table}/regenerate-guid', [\App\Http\Controllers\TableController::class, 'regenerateGuid']);
    Route::apiResource('tables', \App\Http\Controllers\TableController::class);
    Route::post('table-sessions/{tableSession}/spending-limit', [\App\Http\Controllers\TableController::class, 'updateStaffSpendingLimit']);

    // Mértékegységek
    Route::apiResource('drink-units', \App\Http\Controllers\DrinkUnitController::class);

    // Rendelések
    Route::post('orders', [\App\Http\Controllers\OrderController::class, 'staffStore']);
    Route::get('orders/active', [\App\Http\Controllers\OrderController::class, 'activeOrders']);
    Route::get('orders/active/{status}', [\App\Http\Controllers\OrderController::class, 'activeOrders']);
    Route::get('orders/waiting', [\App\Http\Controllers\OrderController::class, 'waitingOrders']);
    Route::post('orders/assign/{order_id}', [\App\Http\Controllers\OrderController::class, 'assignOrder']);
    Route::post('orders/undo-assign/{order_id}', [\App\Http\Controllers\OrderController::class, 'undoAssignOrder']);
    Route::get('orders/lastid', [\App\Http\Controllers\OrderController::class, 'lastOrderId']);
    Route::get('orders/my-tasks', [\App\Http\Controllers\OrderController::class, 'myOpenTasks']);
    Route::post('orders/done/{order_id}', [\App\Http\Controllers\OrderController::class, 'doneOrder']);
    Route::post('order-details/mark-paid', [\App\Http\Controllers\PaymentController::class, 'staffMarkPaid']);
});
