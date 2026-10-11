<?php

use App\Http\Controllers\Api\ApplicantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebsiteApiController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\HireUsController;
use App\Http\Controllers\Api\InternalTimesheetExtension;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\Api\UpworkOpportunityController;
use App\Http\Controllers\Api\UpworkAgentController;

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

// Routes Without Auth
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

Route::get('/jobs-data', [JobsController::class, 'fetch_all']);
Route::get('/all-jobs', [JobsController::class, 'fetch_jobs']);
Route::post('/job-by-category', [JobsController::class, 'jobByCategory']);
Route::post('/job-description', [JobsController::class, 'jobDescription']);

//hiring us routes
Route::post('/add-client', [HireUsController::class, 'store']);

// Ends Routes Without Auth

// Upwork agent endpoints (Sanctum token with the upwork:ingest ability). The PMS never calls Upwork.
Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('upwork')->group(function () {
    Route::get('/opportunities', [UpworkOpportunityController::class, 'index'])->name('api.upwork.opportunities.index');
    Route::post('/opportunities', [UpworkOpportunityController::class, 'store'])->name('api.upwork.opportunities.store');
    Route::put('/opportunities/{upworkJobId}/proposal-draft', [UpworkOpportunityController::class, 'storeProposalDraft'])->name('api.upwork.opportunities.proposal-draft');

    // Discovery worker (Mac mini agent): config, run lease, per-query search rows, completion.
    Route::get('/agent/config', [UpworkAgentController::class, 'config'])->name('api.upwork.agent.config');
    Route::post('/agent/runs', [UpworkAgentController::class, 'startRun'])->name('api.upwork.agent.runs.start');
    Route::post('/agent/runs/{run}/heartbeat', [UpworkAgentController::class, 'heartbeat'])->name('api.upwork.agent.runs.heartbeat');
    Route::post('/agent/runs/{run}/queries', [UpworkAgentController::class, 'recordQuery'])->name('api.upwork.agent.runs.queries');
    Route::post('/agent/runs/{run}/complete', [UpworkAgentController::class, 'completeRun'])->name('api.upwork.agent.runs.complete');
});
