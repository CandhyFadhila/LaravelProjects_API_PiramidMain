<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Management\Gen\Animal\AnimalBreedController;
use App\Http\Controllers\Management\Gen\Animal\AnimalCategoryController;
use App\Http\Controllers\Management\Gen\Animal\AnimalController;
use App\Http\Controllers\Management\Gen\CoverageArea\CitiesController;
use App\Http\Controllers\Management\Gen\CoverageArea\ProvinceController;
use App\Http\Controllers\Management\Gen\Mosque\MosqueController;
use App\Http\Controllers\Management\Gen\Product\AqiqahProductController;
use App\Http\Controllers\Management\Gen\Product\QurbanProductController;
use App\Http\Controllers\Management\Gen\Product\SadaqahProductController;
use App\Http\Controllers\Management\Settings\Account\AddressController;
use App\Http\Controllers\Management\Settings\Account\ChangePasswordController;
use App\Http\Controllers\Management\Settings\Account\ChangeProfileController;
use App\Http\Controllers\Public\PublicRequestController;
use App\Http\Controllers\Service\CartController;
use App\Http\Controllers\Service\OrderDetailController;
use App\Http\Controllers\Service\PaymentController;
use Illuminate\Support\Facades\Route;

// Login Section
Route::post('/signup', [RegisterController::class, 'signUp']);
Route::post('/signin', [LoginController::class, 'login']);
Route::middleware(['custom.throttle:5,1'])->group(function () {

    // <!-- Test Throttle pakek ini -->
    // Route::get('/test-throttle', function () {
    //     return response()->json(['message' => 'OK']);
    // });
    // <!-- Test Throttle pakek ini -->

    Route::post('/signup-verify-otp', [RegisterController::class, 'signUpVerifyOTP']);
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
            Route::get('/get-mosque-relation', [PublicRequestController::class, 'getMosqueRelation']);
            Route::get('/get-animal-category', [PublicRequestController::class, 'getAnimalCategory']);
            Route::get('/get-animal-breed', [PublicRequestController::class, 'getAnimalBreed']);
            Route::get('/get-animals', [PublicRequestController::class, 'getAnimal']);
            Route::get('/get-coverage-province', [PublicRequestController::class, 'getCoverageProvince']);
            Route::get('/get-coverage-cities', [PublicRequestController::class, 'getCoverageCities']);
            Route::get('/get-cities-by-province/{provinceId}', [PublicRequestController::class, 'getCitiesByProvince']);
            Route::get('/get-qurban-product', [PublicRequestController::class, 'getQurbanProduct']);
            Route::get('/get-aqiqah-product', [PublicRequestController::class, 'getAqiqahProduct']);
            Route::get('/get-sadaqah-product', [PublicRequestController::class, 'getSadaqahProduct']);
        });

        // Untuk Admin
        Route::group(['prefix' => 'admin', 'middleware' => ['verified.role:admin']], function () {
            Route::group(['prefix' => 'dashboard'], function () {});

            Route::group(['prefix' => 'master-data'], function () {
                // Gen
                Route::apiResource('/animal-category', AnimalCategoryController::class);
                Route::post('/animal-category/{id}/restore', [AnimalCategoryController::class, 'restore']);

                Route::apiResource('/animal-breed', AnimalBreedController::class);
                Route::post('/animal-breed/{id}/restore', [AnimalBreedController::class, 'restore']);

                Route::apiResource('/animals', AnimalController::class);
                Route::post('/animals/{id}/restore', [AnimalController::class, 'restore']);

                Route::apiResource('/mosque-relation', MosqueController::class);
                Route::post('/mosque-relation/{id}/restore', [MosqueController::class, 'restore']);

                Route::apiResource('/province-coverage-area', ProvinceController::class);
                Route::post('/province-coverage-area/{id}/restore', [ProvinceController::class, 'restore']);

                Route::apiResource('/cities-coverage-area', CitiesController::class);
                Route::post('/cities-coverage-area/{id}/restore', [CitiesController::class, 'restore']);

                // Product
                Route::apiResource('/qurban-product', QurbanProductController::class);
                Route::post('/qurban-product/{id}/restore', [QurbanProductController::class, 'restore']);

                Route::apiResource('/aqiqah-product', AqiqahProductController::class);
                Route::post('/aqiqah-product/{id}/restore', [AqiqahProductController::class, 'restore']);

                Route::apiResource('/sadaqah-product', SadaqahProductController::class);
                Route::post('/sadaqah-product/{id}/restore', [SadaqahProductController::class, 'restore']);
            });

            Route::group(['prefix' => 'settings'], function () {
                Route::post('/change-password', [ChangePasswordController::class, 'updatePasswordAdmin']);
                Route::post('/change-profile', [ChangeProfileController::class, 'updateProfileAdmin']);
            });
        });

        // Untuk Penjualan
        Route::group(['prefix' => 'marketplace', 'middleware' => ['verified.role:marketplace']], function () {
            Route::group(['prefix' => 'settings'], function () {
                Route::post('/change-password', [ChangePasswordController::class, 'updatePasswordUsers']);
                Route::post('/change-profile', [ChangeProfileController::class, 'updateProfileUsers']);
                Route::apiResource('/setup-address', AddressController::class);
                Route::post('/setup-address/{id}/restore', [AddressController::class, 'restore']);
            });

            Route::group(['prefix' => 'service'], function () {
                Route::apiResource('/cart', CartController::class);

                Route::apiResource('/order-detail', OrderDetailController::class);
                Route::post('/order-detail/{id}/restore', [OrderDetailController::class, 'restore']);

                Route::group(['prefix' => 'transaction'], function () {
                    Route::post('/payment', [PaymentController::class, 'createPayment']);
                    Route::post('/update-payment', [PaymentController::class, 'updateStatusPayment']);
                });
            });
        });
    });
});
