<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Management\Gen\Animal\AnimalBreedController;
use App\Http\Controllers\Management\Gen\Animal\AnimalCategoryController;
use App\Http\Controllers\Management\Gen\Animal\AnimalController;
use App\Http\Controllers\Management\Gen\Product\AqiqahProductController;
use App\Http\Controllers\Management\Gen\Product\QurbanProductController;
use App\Http\Controllers\Management\Gen\Product\SadaqahProductController;
use App\Http\Controllers\Public\PublicRequestController;
use Illuminate\Support\Facades\Route;

// Login Section
Route::post('/login', [LoginController::class, 'login']);
Route::middleware(['custom.throttle:5,1'])->group(function () {
    Route::post('/send-otp', [ForgotPasswordController::class, 'sendOTP']);
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOTP']);
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum', 'custom.throttle:60,1'])->group(function () {
    Route::get('/logout', [LoginController::class, 'logout'])->middleware('web');
    Route::get('/user-info', [LoginController::class, 'getUserInfo']);

    Route::group(['prefix' => 'piramid'], function () {
        Route::group(['prefix' => 'public-request'], function () {
            Route::get('/get-service-type', [PublicRequestController::class, 'getServiceType']);
            Route::get('/get-payment-status', [PublicRequestController::class, 'getPaymentStatus']);
            Route::get('/get-transaction-status', [PublicRequestController::class, 'getTransactionStatus']);
            Route::get('/get-payment-method', [PublicRequestController::class, 'getPaymentMethod']);
        });

        // Untuk Admin
        Route::group(['prefix' => 'admin', 'middleware' => ['verified.role:admin']], function () {
            Route::group(['prefix' => 'dashboard'], function () {});

            // Route::group(['prefix' => 'product'], function () {
            //     Route::apiResource('/qurban', QurbanProductController::class);
            //     Route::post('/qurban/{id}/restore', [QurbanProductController::class, 'restore']);
            // });

            Route::group(['prefix' => 'master-data'], function () {
                Route::apiResource('/animal-category', AnimalCategoryController::class);
                Route::post('/animal-category/{id}/restore', [AnimalCategoryController::class, 'restore']);

                Route::apiResource('/animal-breed', AnimalBreedController::class);
                Route::post('/animal-breed/{id}/restore', [AnimalBreedController::class, 'restore']);

                Route::apiResource('/animals', AnimalController::class);
                Route::post('/animals/{id}/restore', [AnimalController::class, 'restore']);

                Route::apiResource('/qurban-product', QurbanProductController::class);
                Route::post('/qurban-product/{id}/restore', [QurbanProductController::class, 'restore']);

                Route::apiResource('/aqiqah-product', AqiqahProductController::class);
                Route::post('/aqiqah-product/{id}/restore', [AqiqahProductController::class, 'restore']);

                Route::apiResource('/sadaqah-product', SadaqahProductController::class);
                Route::post('/sadaqah-product/{id}/restore', [SadaqahProductController::class, 'restore']);
            });
        });

        // Untuk Penjualan
        Route::group(['prefix' => 'marketplace', 'middleware' => ['verified.role:marketplace']], function () {
            Route::group(['prefix' => 'dashboard'], function () {});
        });
    });
});
