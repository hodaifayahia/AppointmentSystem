<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// apis for admins 

Route::get('api/users', [UserController::class, 'index'])->name('users.index');
Route::post('api/users', [UserController::class, 'store'])->name('users.store');
Route::put('/api/users/{userid}', [UserController::class, 'edit'])->name('users.edit');
Route::get('/api/users/search', [UserController::class, 'search'])->name('users.search');
Route::patch('/api/users/{userid}/change-role', [UserController::class, 'ChangeRole'])->name('users.ChangeRole');
Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::delete('/api/users', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');


// apis for doctors
Route::get('api/doctors', [UserController::class, 'index'])->name('users.store');
Route::post('api/doctors', [UserController::class, 'store'])->name('users.store');
Route::get('{view}', [ApplicationController::class, '__invoke'])->where('view', '(.*)');