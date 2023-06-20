<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeavesController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\TicketsController;

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
Route::post('/login', [LoginController::class, 'login'])->name('login.user');
Route::group(['middleware' => ['auth']], function() {
Route::resource('/dashboard', DashboardController::class);
Route::middleware(['role_permission'])->group(function () {
	// Routes that require 'admin' or 'superadmin' roles and corresponding permissions
	Route::get('/users', [UsersController::class, 'index'])->name('users.index');
	Route::post('/add/users', [UsersController::class, 'store'])->name('users.add');
	Route::post('/edit/users', [UsersController::class, 'edit'])->name('users.edit');
	Route::delete('/delete/users', [UsersController::class, 'destroy'])->name('users.delete');
	
	Route::post('/update/users', [UsersController::class, 'update']);
	Route::post('/update/users/status', [UsersController::class, 'updateUserStatus']);
	
	Route::get('/attendance', [AttendanceController::class,'index'])->name('attendance.index');
	Route::post('/add/attendance', [AttendanceController::class, 'store'])->name('attendance.add');
	Route::post('/edit/attendance', [AttendanceController::class, 'edit'])->name('attendance.edit');
	Route::post('/update/attendance', [AttendanceController::class, 'update'])->name('attendance.update');
	Route::delete('/delete/attendance', [AttendanceController::class, 'delete'])->name('attendance.delete');
	Route::get('/attendance/team', [AttendanceController::class, 'showTeamsAttendance'])->name('attendance.team.index');


	Route::get('/leaves', [LeavesController::class, 'index'])->name('leaves.index');
	Route::post('/add/leaves', [leavesController::class, 'store'])->name('leaves.add');
	Route::post('/update/leaves', [leavesController::class, 'setLeavesApproved']);
	Route::get('/leaves/team', [leavesController::class, 'showTeamData'])->name('leaves.team.index');



	Route::get('profile', [UsersController::class, 'Userprofile'])->name('profile');
	Route::post('/update/profile', [UsersController::class, 'updateProfile'])->name('update.profile');
	// Route::post('/update/profile/picture', [UsersController::class, 'updateProfilePicture'])->name('update.profile_picture');
	Route::post('/change/profile/password', [UsersController::class, 'changeUserPassword']);
	Route::post('/delete/profile/picture', [UsersController::class, 'deleteProfilePicture'])->name('delete.profile_picture');
	
	Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets.index');
	Route::post('/add/tickets', [TicketsController::class, 'store'])->name('tickets.add');
	Route::post('/ticket/assign', [TicketsController::class, 'getTicketAssign']);
	Route::get('/edit/ticket/{ticketId}', [TicketsController::class, 'editTicket'])->name('tickets.edit');
	Route::post('/update/tickets/{ticketId}', [TicketsController::class, 'updateTicket'])->name('tickets.update');
	Route::delete('/delete/tickets', [TicketsController::class, 'destroy'])->name('tickets.delete');
	Route::post('/add/comments/', [TicketsController::class, 'addComments'])->name('comments.add');
	Route::delete('/delete/ticket/', [TicketsController::class, 'deleteTicketAssign']);
	Route::delete('/delete/ticket/file', [TicketsController::class, 'deleteTicketFile']);
	Route::post('/update/profile/croped-picture', [UsersController::class, 'saveCropedProfilePicture'])->name('update.profile_picture');

	// Route::resource('/departments', DepartmentsController::class)->name('departments.index');
	Route::get('/departments', [DepartmentsController::class, 'index'])->name('departments.index');
	Route::post('/add/department', [DepartmentsController::class, 'store'])->name('departments.add');
	Route::post('/edit/department', [DepartmentsController::class, 'edit'])->name('departments.edit');
	Route::post('/update/department', [DepartmentsController::class, 'update']);
	Route::delete('/delete/department', [DepartmentsController::class, 'destroy'])->name('departments.delete');
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


	// Projects Routes
	Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
	Route::post('/add/projects', [ProjectsController::class, 'store'])->name('projects.add');
	Route::get('/edit/project/{projectId}', [ProjectsController::class, 'editProject'])->name('projects.edit');
	Route::post('/update/projects/{projectId}', [ProjectsController::class, 'updateProject'])->name('projects.update');
	Route::get('/project/{projectId}', [ProjectsController::class, 'showProject'])->name('projects.show');
	Route::delete('/delete/project/file', [ProjectsController::class, 'deleteProjectFile']);
	Route::delete('/delete/project/assign', [ProjectsController::class, 'deleteProjectAssign']);
	Route::post('/project/assign', [ProjectsController::class, 'getProjectAssign']);

	});
	
	
	Route::get('logout', [LoginController::class, 'logOut'])->name('logout');

 });