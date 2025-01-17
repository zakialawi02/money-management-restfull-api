<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\PDFExportController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::get('/login', function () {
        return response()->json([
            'success' => false,
            'message' => 'Please Login [POST] /api/auth/login'
        ], 401);
    })->name('login');
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register'])->name('register');
    Route::post('/forgot-password', [App\Http\Controllers\Api\AuthController::class, 'passwordResetLink'])->middleware(['throttle:6,1'])->name('password.email');
    Route::get('reset-password/{token}', fn() => [
        'success' => true,
        'message' => 'Please Reset Password [GET] /api/auth/reset-password/{token}'
    ])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Api\AuthController::class, 'newPassword'])->name('password.store');
    Route::get('/verify-email', function () {
        return response()->json([
            'success' => false,
            'message' => 'Please Verify Email [POST] /api/auth/verify-email'
        ], 401);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.notice');
    Route::post('/email/verification-notification', [App\Http\Controllers\Api\AuthController::class, 'emailVerificationNotification'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
    Route::get('/verify-email/{id}/{hash}', [App\Http\Controllers\Api\AuthController::class, 'verifyEmail'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.verify');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->name('logout');
    });
});

Route::prefix('v1')->as('api.')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', function () {
            try {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome to Money Management API',
                ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
                ], 500);
            }
        })->name('home');

        Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
        Route::get('/user', [App\Http\Controllers\Api\UserController::class, 'me'])->name('user');

        Route::apiResource('accounts', App\Http\Controllers\Api\AccountController::class);
        Route::apiResource('transactions_category', App\Http\Controllers\Api\TransactionCategoryController::class)->except(['show', 'update']);
        Route::apiResource('transactions', App\Http\Controllers\Api\TransactionController::class);
        Route::get('/account/{accountId}/report/download', [PDFExportController::class, 'exportPDFReport'])->name('account.report.download');

        Route::post('/get-streams-url', [TransactionController::class, 'generateEncryptedShareUrl'])->name('stream.report.url');
    });

    Route::get('/stream-report/{encryptedUrl}', [TransactionController::class, 'streamReportEncrypted'])->name('stream.report');
    // Route::get('/stream-report/{userId}/{accountId}/{date?}', [TransactionController::class, 'streamReport'])->name('stream.report');
});
