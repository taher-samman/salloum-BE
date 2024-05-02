<?php

use App\Http\Controllers\Apis\ApplicationsController;
use App\Http\Controllers\Apis\EmailsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Apis\UserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(ApplicationsController::class)->group(function () {
    Route::post('/application', 'postApplication');
    Route::post('/log', 'postLog');
    Route::post('/feedback', 'postValidateFeedback');
    Route::post('/applications/feedback', 'postFeedback');
});

Route::controller(EmailsController::class)->group(function () {
    Route::post('/emails/contactus', 'postContactUsEmail');
    Route::post('/emails/volunteer', 'postVolunteerEmail');
});

Route::controller(UserController::class)->group(function () {
    Route::post('/register', 'postRegister');
    Route::post('/login', 'postLogin');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::controller(ApplicationsController::class)->group(function () {
        Route::get('/applications', 'getApplications');
        Route::post('/applications', 'postApplications');
        Route::post('/applications/done', 'postDoneApplication');
        Route::post('/applications/download', 'postDownloadMedia');
    });
});
