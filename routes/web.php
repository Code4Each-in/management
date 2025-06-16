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
use App\Http\Controllers\HiringUsController;
use App\Http\Controllers\VotesController;
use App\Http\Controllers\TodoListController;
use App\Http\Controllers\ScrumdashController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\EmailAll;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\StickyNoteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\BDEController;
use App\Http\Controllers\SearchDataController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\ClientAccessRequestController;
use App\Http\Controllers\TicketLogController;
use Illuminate\Support\Facades\Response;
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
// Inactive client page — no auth middleware so user can see this after logout
    Route::get('/client/inactive', [LoginController::class, 'inactiveClient'])
    ->name('client.inactive');
	Route::get('/client/request-sent', function () {
    return view('auth.inactive-request-sent');
    })->name('client.request-sent');
	 //for request access
     Route::post('/client/request-access', [LoginController::class, 'requestAccess'])
    ->middleware('web') // ensure session middleware is present
    ->name('client.request-access');
    Route::get('/admin/client-access-requests', [ClientAccessRequestController::class, 'index'])->name('client-access-requests.index');
    Route::post('/admin/client-access-requests/{id}/approve', [ClientAccessRequestController::class, 'approve'])->name('client-access-requests.approve');
    Route::delete('/admin/client-access-requests/{id}', [ClientAccessRequestController::class, 'destroy'])->name('client-access-requests.destroy');
    Route::get('/client/inactive/{user}', [LoginController::class, 'showInactiveClient'])->name('client.inactive');

//route for password forgot
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
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
	// Route::post('/add/attendance', [AttendanceController::class, 'store'])->name('attendance.add');
    Route::post('/attendance/team/add', [AttendanceController::class, 'storeTeamAttendance'])->name('attendance.team.add');
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
	Route::get('/view/ticket/{ticketId}', [TicketsController::class, 'viewTicket'])->name('tickets.ticketdetail');
	// Route::get('/view-document/{filename}', [TicketsController::class, 'viewDocument'])->name('document.view');
	Route::get('/tickets/create', [TicketsController::class, 'create'])->name('tickets.create');
	Route::post('/tickets/{id}/update-status', [TicketsController::class, 'updateStatus']);



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
    Route::delete('/delete/projects', [ProjectsController::class, 'deleteproject'])->name('projects.delete');


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

    //JOBS CATEGORIES ROUTES
	Route::get('/job-categories', [JobCategoriesController::class, 'index'])->name('job_categories.index');
	Route::post('/add/job-category', [JobCategoriesController::class, 'store'])->name('job_categories.add');
	Route::delete('/delete/job-category', [JobCategoriesController::class, 'destroy'])->name('job_categories.delete');

	//Applicants CATEGORIES ROUTES
	Route::get('/applicants', [ApplicantsController::class, 'index'])->name('applicants.index');
    Route::post('/applicants/status', [ApplicantsController::class, 'update_application_status'])->name('applicants.status');

    //hire us
    Route::get('/hireus', [HiringUsController::class, 'index'])->name('hireus.index');
    Route::post('/edit/hireus', [HiringUsController::class, 'edit'])->name('hireus.edit');
    Route::post('/update/hireus', [HiringUsController::class, 'update'])->name('hireus.update');
    Route::delete('/delete/hireus', [HiringUsController::class, 'delete'])->name('hireus.delete');


	//Scrum Dashboard
	Route::get('/scrumdash', [ScrumdashController::class, 'index'])->name('scrumdash.index');

	//sprint dashboard
	Route::get('/sprint', [SprintController::class, 'index'])->name('sprint.index');
	Route::post('/add/sprint', [SprintController::class, 'store'])->name('sprint.add');
	Route::delete('/delete/sprint', [SprintController::class, 'destroy'])->name('sprint.delete');
	Route::get('/edit/sprint/{sprintId}', [SprintController::class, 'editSprint'])->name('sprint.edit');
    Route::get('/view/sprint/{sprintId}', [SprintController::class, 'viewSprint'])->name('sprint.view');
	Route::post('/update/sprint/{sprintId}', [SprintController::class, 'updateSprint'])->name('sprint.update');
	Route::get('/get-sprints-by-project/{project_id}', [SprintController::class, 'getSprints']);
	});

    //Permission Routes Ends



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
    Route::get('/users/view/{id}', [UsersController::class, 'singleUserData'])->name('users.view');
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

	//For Vote
	Route::post('/submit-vote', [VotesController::class, 'SubmitVote'])->name('submit.vote');

	// ToDo Route
	Route::get('/todo_list', [TodoListController::class, 'index'])->name('todo_list.index');
	Route::post('/todo_list', [TodoListController::class, 'store'])->name('todo_list.store');
	Route::get('/todo_list/{todoList}/edit', [TodoListController::class, 'edit'])->name('todo_list.edit');
	Route::put('/todo_list/{todoList}', [TodoListController::class, 'update'])->name('todo_list.update');
	Route::delete('/todo_list/{todoList}', [TodoListController::class, 'destroy'])->name('todo_list.destroy');
	Route::put('/todo_list/{todoList}/status', [TodoListController::class, 'updateStatus'])->name('todo_list.updateStatus');
	Route::put('/todo_list/{todoList}/hold', [TodoListController::class, 'holdTask'])->name('todo_list.hold');

	//Scrum Dashboard
	Route::get('/scrumdash', [ScrumdashController::class, 'index'])->name('scrumdash.index');

	//sprint dashboard
	Route::get('/sprint', [SprintController::class, 'index'])->name('sprint.index');
	Route::post('/add/sprint', [SprintController::class, 'store'])->name('sprint.add');
	Route::delete('/delete/sprint', [SprintController::class, 'destroy'])->name('sprint.delete');
	Route::get('/edit/sprint/{sprintId}', [SprintController::class, 'editSprint'])->name('sprint.edit');
    Route::get('/view/sprint/{sprintId}', [SprintController::class, 'viewSprint'])->name('sprint.view');
	Route::post('/update/sprint/{sprintId}', [SprintController::class, 'updateSprint'])->name('sprint.update');
	Route::get('/get-sprints-by-project/{project_id}', [SprintController::class, 'getSprints']);

    //Email to all
    Route::controller(EmailAll::class)->group(function () {
        Route::get('/emailtoall', 'index')->name('emailall.index');
        Route::post('/emailtoall/send', 'sendMail')->name('emailall.send');
    });

    Route::get('/reminder', [ReminderController::class, 'create'])->name('reminder.create');
    Route::post('/reminder/store', [ReminderController::class, 'store'])->name('reminders.store');
    Route::post('/reminder/mark-as-read', [ReminderController::class, 'markAsRead'])->name('reminder.markAsRead');
    Route::get('/reminder/create', [ReminderController::class, 'indexing'])->name('reminder.index');
    Route::get('/reminder/edit/{reminder}', [ReminderController::class, 'edit'])->name('reminders.edit');
    Route::put('/reminder/{reminder}', [ReminderController::class, 'update'])->name('reminders.update'); // Add this line
    Route::delete('/reminder/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

	//developer related listing
    Route::get('/devlisting', [ProjectsController::class, 'devListing'])->name('devlisting');
	Route::post('/submit-feedback', [ProjectsController::class, 'submitFeedback'])->name('feedback.submit');
    Route::delete('/delete/sprint/file', [SprintController::class, 'deleteSprintFile']);
	
	Route::get('/developer/feedback', [ProjectsController::class, 'allfeedback'])->name('developer.feedback');

	//Sticky Note
	Route::get('/sticky-notes', [StickyNoteController::class, 'getNotes'])->name('sticky.notes');
	Route::post('/sticky-notes/create', [StickyNoteController::class, 'createNote'])->name('sticky.notes.create');
	Route::post('/sticky-notes/update/{id}', [StickyNoteController::class, 'updateNote'])->name('sticky.notes.update');
	Route::post('/sticky-notes/delete', [StickyNoteController::class, 'deleteNote']);


	 //for notification functionality added this route in ticket controller
	 Route::get('/notifications', [TicketsController::class, 'notifications'])->name('notifications.all');
	 Route::post('/notifications/mark-as-read/{id}', [TicketsController::class, 'markAsRead'])->name('notifications.markAsRead');
	 Route::post('/notifications/mark-all-read', [TicketsController::class, 'markAllAsRead'])->name('notifications.markAllRead');


	 //For messages
	 Route::get('/messages', [MessageController::class, 'index'])->name('messages');
	 Route::post('/add/message', [MessageController::class, 'addMessage'])->name('message.add'); 
	 Route::get('/get/latest-message/{projectId}', [MessageController::class, 'getLatestMessage']);
	 Route::get('/get/project/messages/{projectId}', [MessageController::class, 'getMessagesByProject'])->name('get.project.messages');
	 Route::delete('/comments/{id}/delete', [MessageController::class, 'destroy']);
	 Route::post('/project-messages/{id}/mark-as-read', [MessageController::class, 'markAsRead'])->name('project-messages.markAsRead');
	 Route::delete('/comments/{id}/delete', [TicketsController::class, 'deleteComment'])->name('comments.delete');
	 Route::get('/comments', [SprintController::class, 'allNotifications'])->name('comments');
	 Route::get('/project/chat/{projectId}', function ($projectId) {
		return redirect()->route('projects.show', ['project' => $projectId, 'chat' => 1]);
	});
	Route::post('/user/heartbeat', [UsersController::class, 'heartbeat'])->middleware('auth');

	//bde section
	Route::get('/bid-sprints', [BDEController::class, 'index'])->name('bdeSprint.index');
	Route::post('/add/bde/sprint', [BDEController::class, 'add'])->name('bdeSprint.add');
	Route::get('/edit/bid-sprint/{id}', [BDEController::class, 'edit'])->name('bdeSprint.edit');
    Route::post('/update/bid-sprint/{id}', [BDEController::class, 'update'])->name('bdeSprint.update');
    Route::delete('/bde-sprint/{id}', [BDEController::class, 'destroy'])->name('bdeSprint.destroy');
    Route::get('/view/bid-sprint/{id}', [BDEController::class, 'view'])->name('bid-sprint.view');
    Route::post('/add-task', [BDEController::class, 'storeTask'])->name('task.store');
    Route::get('/tasks/{id}/edit', [BDEController::class, 'edittask'])->name('tasks.edit');
    Route::post('/tasks/{id}/update', [BDEController::class, 'updateTaskWithPost'])->name('tasks.update');
	Route::delete('/tasks/{id}', [BDEController::class, 'destroytask'])->name('tasks.destroy');
	Route::get('/view/task/{id}', [BDEController::class, 'show'])->name('tasks.show');
    Route::post('/bde/add-comment', [BDEController::class, 'addComment'])->name('bde.comment.add');
    Route::post('/tasks/{task}/update-status', [BDEController::class, 'updateStatus'])->name('tasks.updateStatus');

	//attendance history 
	Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');

	// Routes for search functionality
	Route::match(['get', 'post'], '/search', [SearchDataController::class, 'searchList'])->name('search.index');

	//Routes for Team Messages
	 Route::get('/teamchat', [TeamsController::class, 'index'])->name('teamchat');
	 Route::post('/add/messages', [TeamsController::class, 'addMessages'])->name('message.adds'); 
	 Route::get('/get/latest-message/{projectId}', [TeamsController::class, 'getLatestMessage'])->name('get.latest.message');
     Route::get('/get/project/group-messages/{projectId}', [TeamsController::class, 'getMessagesByProjects'])->name('get.project.group-messages');
     Route::post('/group-messages/{message}/read', [TeamsController::class, 'markAsRead'])->name('group-messages.read');
     Route::get('/project-messages/{projectId}/unread-count', [TeamsController::class, 'getUnreadMessageCount'])->name('project-messages.unreadCount');
     Route::delete('/comments/{comment}', [TeamsController::class, 'destroy'])->name('comments.destroy');

	//ticket logs section
	    Route::get('/ticket-logs', [TicketLogController::class, 'index'])->name('ticket-logs.index');
	    Route::get('/load-more-comments/{ticketId}', [TicketsController::class, 'loadMoreComments'])->name('ticket.comments.load');
    //approve ticket estimation 
	Route::get('/ticket/{id}/approve-estimation', [TicketsController::class, 'approveEstimation'])->name('ticket.approveEstimation');
		Route::get('/download-file/{filename}', function ($filename) {
		$path = public_path('assets/img/ticketAssets/' . $filename);

		if (!file_exists($path)) {
			abort(404, 'File not found');
		}
		return response()->download($path);
	})->name('public.file.download')->where('filename', '.*');
	Route::get('/pending-approvals', [TicketLogController::class, 'pendingApprovals'])->name('client.pending.approvals');

});
