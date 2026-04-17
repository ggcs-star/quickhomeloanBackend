<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ApplyLoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LenderController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\EducationModuleController;

Route::get('/test', function () {
    return ['status' => 'API working'];
});

Route::get('/lenders', [LenderController::class, 'index']);
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::post('/apply-loan/store', [ApplyLoanController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::post('/user/edit-profile', [UserProfileController::class, 'update']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::post('/fcm/save-token', [NotificationController::class, 'saveToken']);
    Route::post('/notify/all', [NotificationController::class, 'notifyAll']);
    Route::post('/loan/submit-form', [LoanController::class, 'store']);
    Route::get('/loan/submit-form', [LoanController::class, 'show']);

    Route::post('/create-subscription', [SubscriptionController::class, 'createSubscription']);
    Route::get('/check-access', [SubscriptionController::class, 'checkAccess']);

    Route::get('/education-modules', [EducationModuleController::class, 'index']);
  
});


Route::post('/razorpay/webhook', [SubscriptionController::class, 'webhook']);



  Route::get('/education/audio', [EducationModuleController::class, 'audioModules']);
    Route::get('/education/video', [EducationModuleController::class, 'videoModules']);