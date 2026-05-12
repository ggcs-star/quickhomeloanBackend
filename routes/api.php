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
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ReelController;
use App\Http\Controllers\EducationContentController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LoanInquiryController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\PodcastController;

Route::get('/test', function () {
    return ['status' => 'API working'];
});

Route::get('/lenders', [LenderController::class, 'index']);
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::post('/apply-loan/store', [ApplyLoanController::class, 'store']);

Route::get('/calculators', [CalculatorController::class, 'index']);
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

Route::get('/payment-history', [SubscriptionController::class, 'paymentHistory']);

    Route::get('/education-modules', [EducationModuleController::class, 'index']);
    Route::get('/courses', [EducationContentController::class, 'courses']);
    Route::get('/modules/{course_id}', [EducationContentController::class, 'modules']);
    Route::get('/contents/{module_id}', [EducationContentController::class, 'contents']);

    Route::prefix('reels')->group(function () {

        Route::get('/', [ReelController::class, 'index']);
        Route::get('/{id}', [ReelController::class, 'show']);
        Route::post('/view/{id}', [ReelController::class, 'addView']);
        Route::post('/like/{id}', [ReelController::class, 'toggleLike']);
        Route::post('/comment/{id}', [ReelController::class, 'addComment']);
        Route::get('/comment/{id}', [ReelController::class, 'getComments']);
    });
      Route::prefix('community')->group(function () {
        Route::get('/posts', [CommunityController::class, 'getPosts']);
        Route::get('/posts/{id}', [CommunityController::class, 'getPost']);
        Route::post('/posts', [CommunityController::class, 'createPost']);
            Route::get('/my-posts', [CommunityController::class, 'myPosts']);
    Route::get('/my-comments', [CommunityController::class, 'myComments']);


        Route::post('/posts/{id}/like', [CommunityController::class, 'toggleLike']);
        Route::post('/posts/{id}/share', [CommunityController::class, 'sharePost']);
        Route::post('/posts/{id}/save', [CommunityController::class, 'toggleSave']);
        Route::get('/saved', [CommunityController::class, 'getSavedPosts']);
        Route::post('/comments', [CommunityController::class, 'storeComment']);
        Route::post('/comments/{id}/like', [CommunityController::class, 'toggleCommentLike']);
        Route::delete('/comments/{id}', [CommunityController::class, 'deleteComment']);
    });

    Route::prefix('podcasts')->group(function () {
        Route::get('/', [PodcastController::class, 'getPodcasts']);
        Route::get('/featured', [PodcastController::class, 'getFeaturedPodcasts']);
        Route::get('/{id}', [PodcastController::class, 'getPodcast']);
    });

    Route::get('/banners', [BannerController::class, 'index']);
    Route::get('/calculator-media/{slug}', [CalculatorController::class, 'getMedia']);



    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::post('/events/{id}/complete', [EventController::class, 'markComplete']);
});


Route::post('/razorpay/webhook', [SubscriptionController::class, 'webhook']);
// Route::get('/education/audio', [EducationModuleController::class, 'audioModules']);
// Route::get('/education/video', [EducationModuleController::class, 'videoModules']);

Route::prefix('loan-inquiries')->group(function () {

    Route::post('/', [LoanInquiryController::class, 'store']);     // create
    Route::get('/', [LoanInquiryController::class, 'index']);      // list
    Route::get('/{id}', [LoanInquiryController::class, 'show']);   // single
    Route::delete('/{id}', [LoanInquiryController::class, 'destroy']); // delete

});


Route::prefix('contact')->group(function () {

    Route::post('/', [ContactInquiryController::class, 'store']);    
    Route::get('/', [ContactInquiryController::class, 'index']);      
    Route::get('/{id}', [ContactInquiryController::class, 'show']);  
    Route::delete('/{id}', [ContactInquiryController::class, 'destroy']); 

});

Route::post('/fcm/save-token', [NotificationController::class, 'saveToken']);
Route::post('/fcm/attach-token-to-user', [NotificationController::class, 'attachTokenToUser']); 
Route::post('/fcm/notify-all', [NotificationController::class, 'notifyAll']);
Route::get('/fcm/history', [NotificationController::class, 'getHistory']);