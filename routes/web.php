<?php


use App\Http\Controllers\AssignedDevicesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DevicesController;
use App\Http\Controllers\HolidaysController;
use App\Http\Controllers\LeavesController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PoliciesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\JobCategoriesController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\ApplicantsController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

 Route::get('/', function () {
    return view('welcome');
 });

/**
* Login Routes
*/
Route::get('/', [LoginController::class, 'show'])->name('login');
Route::match(['get', 'post'], '/login', [LoginController::class, 'login'])->name('login.user');
Route::group(['middleware' => ['auth']], function() {
Route::resource('/dashboard', DashboardController::class);
//Permission Routes Starts
Route::middleware(['role_permission'])->group(function () {
	// Routes that require 'admin' or 'superadmin' roles and corresponding permissions
	Route::get('/users', [UsersController::class, 'index'])->name('users.index');
	Route::post('/add/users', [UsersController::class, 'store'])->name('users.add');
	Route::post('/edit/users', [UsersController::class, 'edit'])->name('users.edit');
	Route::delete('/delete/users', [UsersController::class, 'destroy'])->name('users.delete');
	Route::post('/update/users', [UsersController::class, 'update'])->name('users.update');
	Route::get('/users/documents/{user}', [UsersController::class, 'showUsersDocuments'])->name('users.documents.show');


	Route::get('/attendance', [AttendanceController::class,'index'])->name('attendance.index');
	Route::post('/add/attendance', [AttendanceController::class, 'store'])->name('attendance.add');
	Route::post('/edit/attendance', [AttendanceController::class, 'edit'])->name('attendance.edit');
	Route::post('/update/attendance', [AttendanceController::class, 'update'])->name('attendance.update');
	Route::delete('/delete/attendance', [AttendanceController::class, 'delete'])->name('attendance.delete');
	Route::get('/attendance/team', [AttendanceController::class, 'showTeamsAttendance'])->name('attendance.team.index');


	Route::get('/leaves', [LeavesController::class, 'index'])->name('leaves.index');
	Route::post('/add/leaves', [leavesController::class, 'store'])->name('leaves.add');
	Route::post('/update/leaves', [leavesController::class, 'setLeavesApproved'])->name('leaves.team.update');
	Route::get('/leaves/team', [leavesController::class, 'showTeamData'])->name('leaves.team.index');
	Route::post('/leaves/team/add', [leavesController::class, 'addTeamLeaves'])->name('leaves.team.add');


	Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets.index');
	Route::post('/add/tickets', [TicketsController::class, 'store'])->name('tickets.add');
	Route::get('/edit/ticket/{ticketId}', [TicketsController::class, 'editTicket'])->name('tickets.edit');
	Route::post('/update/tickets/{ticketId}', [TicketsController::class, 'updateTicket'])->name('tickets.update');
	Route::delete('/delete/tickets', [TicketsController::class, 'destroy'])->name('tickets.delete');


	// Route::resource('/departments', DepartmentsController::class)->name('departments.index');

	Route::get('/departments', [DepartmentsController::class, 'index'])->name('departments.index');
	Route::post('/add/department', [DepartmentsController::class, 'store'])->name('departments.add');
	Route::post('/edit/department', [DepartmentsController::class, 'edit'])->name('departments.edit');
	Route::post('/update/department', [DepartmentsController::class, 'update'])->name('departments.update');
	Route::delete('/delete/department', [DepartmentsController::class, 'destroy'])->name('departments.delete');

	// Roles Route
	Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
	Route::post('/add/role', [RolesController::class, 'store'])->name('roles.add');
	Route::post('/edit/role', [RolesController::class, 'edit'])->name('roles.edit');
	Route::post('/update/role', [RolesController::class, 'update'])->name('roles.update');
	Route::delete('/delete/role', [RolesController::class, 'destroy'])->name('roles.delete');

	// Pages Routes
	Route::get('/pages', [PagesController::class, 'index'])->name('pages.index');
	Route::post('/add/page', [PagesController::class, 'store'])->name('pages.add');
	Route::post('/edit/page', [PagesController::class, 'edit'])->name('pages.edit');
	Route::post('/update/page', [PagesController::class, 'update'])->name('pages.update');
	Route::delete('/delete/page', [PagesController::class, 'destroy'])->name('pages.delete');

	// Modules Routes
	Route::get('/modules', [ModulesController::class, 'index'])->name('modules.index');
	Route::post('/add/module', [ModulesController::class, 'store'])->name('modules.add');
	Route::post('/edit/module', [ModulesController::class, 'edit'])->name('modules.edit');
	Route::post('/update/module', [ModulesController::class, 'update'])->name('modules.update');
	Route::delete('/delete/module', [ModulesController::class, 'destroy'])->name('modules.delete');


	// Holidays Routes
	Route::get('/holidays', [HolidaysController::class, 'index'])->name('holidays.index');
	Route::post('/add/holiday', [HolidaysController::class, 'store'])->name('holidays.add');
	Route::post('/edit/holiday', [HolidaysController::class, 'edit'])->name('holidays.edit');
	Route::post('/update/holiday', [HolidaysController::class, 'update'])->name('holidays.update');
	Route::delete('/delete/holiday', [HolidaysController::class, 'destroy'])->name('holidays.delete');

	// Devices Routes
	Route::get('/devices', [DevicesController::class, 'index'])->name('devices.index');
	Route::post('/add/device', [DevicesController::class, 'store'])->name('devices.add');
	Route::post('/edit/device', [DevicesController::class, 'edit'])->name('devices.edit');
	Route::post('/update/device', [DevicesController::class, 'update'])->name('devices.update');
	Route::delete('/delete/device', [DevicesController::class, 'destroy'])->name('devices.delete');
	Route::get('/device/{id}', [DevicesController::class, 'show'])->name('devices.show');


	// Assigned Devices Routes
	Route::get('/assigned-devices', [AssignedDevicesController::class, 'index'])->name('devices.assigned.index');
	Route::post('/add/assigned-device', [AssignedDevicesController::class, 'store'])->name('devices.assigned.add');
	// Route::get('/edit/assigned-device/{id}', [AssignedDevicesController::class, 'edit'])->name('devices.assigned.edit');
	Route::post('/update/assigned-device', [AssignedDevicesController::class, 'update'])->name('devices.assigned.update');
	Route::delete('/delete/assigned-device', [AssignedDevicesController::class, 'destroy'])->name('devices.assigned.delete');

	// Projects Routes
	Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
	Route::post('/add/projects', [ProjectsController::class, 'store'])->name('projects.add');
	Route::get('/edit/project/{projectId}', [ProjectsController::class, 'editProject'])->name('projects.edit');
	Route::post('/update/projects/{projectId}', [ProjectsController::class, 'updateProject'])->name('projects.update');
	Route::get('/project/{projectId}', [ProjectsController::class, 'showProject'])->name('projects.show');


	// Policies Routes
	Route::get('/policies', [PoliciesController::class, 'index'])->name('policies.index');
	Route::post('/add/policy', [PoliciesController::class, 'store'])->name('policies.add');
	Route::post('/add/policy-document', [PoliciesController::class, 'storeDocument'])->name('policies.add-document');
	Route::get('/edit/policy/{policyId}', [PoliciesController::class, 'edit'])->name('policies.edit');
	Route::post('/update/policy/{policyId}', [PoliciesController::class, 'update'])->name('policies.update');
	Route::get('/policy/{policyId}', [PoliciesController::class, 'showPolicy'])->name('policies.show');
	Route::delete('/delete/policy', [PoliciesController::class, 'destroy'])->name('policies.delete');


	//Client section routes
	Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
	Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
	Route::post('/clients/store', [ClientController::class, 'store'])->name('clients.store');
	Route::get('/clients/show/{id}', [ClientController::class, 'show'])->name('clients.show');
	Route::get('clients/edit/{id}', [ClientController::class, 'edit'])->name('clients.edit');
	Route::post('clients/update', [ClientController::class, 'update'])->name('clients.update');
	Route::DELETE('/delete/client', [ClientController::class, 'deleteClient'])->name('clients.delete');

    //JOBS ROUTES
	Route::get('/jobs', [JobsController::class, 'index'])->name('jobs.index');
	Route::post('/add/job', [JobsController::class, 'store'])->name('jobs.add');
	Route::delete('/delete/job', [JobsController::class, 'destroy'])->name('jobs.delete');
	Route::post('/edit/job', [JobsController::class, 'edit'])->name('jobs.edit');
	Route::post('/update/job', [JobsController::class, 'update'])->name('jobs.update');
    Route::get('/details/{id}', [JobsController::class, 'show'])->name('jobs.show');

	});

    //Permission Routes Ends

	//JOBS CATEGORIES ROUTES
	Route::get('/job-categories', [JobCategoriesController::class, 'index'])->name('job_categories.index');
	Route::post('/add/job-category', [JobCategoriesController::class, 'store'])->name('job_categories.add');
	Route::delete('/delete/job-category', [JobCategoriesController::class, 'destroy'])->name('job_categories.delete');

	//Applicants CATEGORIES ROUTES
	Route::get('/applicants', [ApplicantsController::class, 'index'])->name('applicants.index');
    Route::post('/applicants/status', [ApplicantsController::class, 'update_application_status'])->name('applicants.status');

	//Commnents Route Without Role Permission Middleware
	Route::post('/add/comments/', [TicketsController::class, 'addComments'])->name('comments.add');
	//Profiles Routes Without Role Permission Middleware
	Route::get('profile', [UsersController::class, 'Userprofile'])->name('profile');
	Route::post('/update/profile', [UsersController::class, 'updateProfile'])->name('update.profile');
	// Route::post('/update/profile/picture', [UsersController::class, 'updateProfilePicture'])->name('update.profile_picture');
	Route::post('/change/profile/password', [UsersController::class, 'changeUserPassword']);
	Route::post('/profile/upload/document', [UsersController::class, 'uploadDocument']);
	Route::delete('/delete/profile/document', [UsersController::class, 'deleteProfileDocument']);
	// Route::get('/profile/documents', [UsersController::class, 'userUploadedDocuments']);

	Route::post('/delete/profile/picture', [UsersController::class, 'deleteProfilePicture'])->name('delete.profile_picture');
	Route::post('/update/profile/croped-picture', [UsersController::class, 'saveCropedProfilePicture'])->name('update.profile_picture');

	// Users Routes Without Role Permission Middleware
	Route::post('/update/users/status', [UsersController::class, 'updateUserStatus'])->name('users.status.update');


	// Tickets Routes Without Permission Middleware
	Route::post('/ticket/assign', [TicketsController::class, 'getTicketAssign']);
	Route::delete('/delete/ticket/', [TicketsController::class, 'deleteTicketAssign']);
	Route::delete('/delete/ticket/file', [TicketsController::class, 'deleteTicketFile']);

	// Projects Routes Without Permission Middleware
	Route::delete('/delete/project/file', [ProjectsController::class, 'deleteProjectFile']);
	Route::delete('/delete/project/assign', [ProjectsController::class, 'deleteProjectAssign']);
	Route::post('/project/assign', [ProjectsController::class, 'getProjectAssign']);

	// Migration run command route
	// Route::get('/migrations', function () {
	// 	Artisan::call('migrate');
	// 	// Dump and die (dd) the migration output
	// 	dd(Artisan::output());
	// });

	Route::delete('/delete/device/document', [DevicesController::class, 'deleteDocument']);




    //For logout
	Route::get('logout', [LoginController::class, 'logOut'])->name('logout');

 });
