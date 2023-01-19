<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\RolesController;

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

// Route::get('/', function () {
//     return view('welcome');
// });
Route::resource('/', DashboardController::class);
// Route::resource('/departments', DepartmentsController::class)->name('departments.index');
Route::get('/departments', [DepartmentsController::class, 'index'])->name('departments.index');
Route::post('/add/department', [DepartmentsController::class, 'store']);
Route::post('/edit/department', [DepartmentsController::class, 'edit']);
Route::post('/update/department', [DepartmentsController::class, 'update']);
Route::delete('/delete/department', [DepartmentsController::class, 'destroy']);

Route::get('/role', [RolesController::class, 'index'])->name('role.index');
Route::post('/add/role', [RolesController::class, 'store']);
Route::post('/edit/role', [RolesController::class, 'edit']);
Route::post('/update/role', [RolesController::class, 'update']);
Route::delete('/delete/role', [RolesController::class, 'destroy']);


