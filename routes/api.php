<?php

use App\Http\Controllers\Api\ApplicantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebsiteApiController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\InternalTimesheetExtension;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/random', [WebsiteApiController::class, 'getrandomchar']);
Route::post('/contactus', [ContactUsController::class, 'contactUs'])->name('contactUs.add');
Route::post('/add-applicant', [ApplicantController::class, 'store']);
Route::post('/verify-otp', [ApplicantController::class, 'update']);
Route::post('/resend-otp', [ApplicantController::class, 'resentOtp']);
Route::get('/delete/captchas', [WebsiteApiController::class, 'deleteCaptcha']);

Route::post('/authenticate-user',[InternalTimesheetExtension::class,'validateUser']);
Route::post('/add-status-report',[InternalTimesheetExtension::class,'addStatusReport']);
Route::post('/add-start-time',[InternalTimesheetExtension::class,'addStartTime']);
Route::get('/get-start-time',[InternalTimesheetExtension::class,'getStartTime']);

