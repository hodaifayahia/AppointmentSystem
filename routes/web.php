<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\DoctorController;
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
Route::get('api/doctors', [DoctorController::class, 'index'])->name('users.store');
Route::post('api/doctors', [DoctorController::class, 'store'])->name('users.store');
Route::put('api/doctors/{doctorid}', [DoctorController::class, 'update'])->name('users.update');
Route::delete('/api/doctors/{doctorid}', [DoctorController::class, 'destroy'])->name('users.destroy');
Route::delete('/api/doctors', [DoctorController::class, 'bulkDelete'])->name('users.bulkDelete');
Route::get('api/doctors/search', [DoctorController::class, 'search'])->name('users.search');


Route::get('api/doctors/search', [AppointmentController::class, 'search'])->name('users.search');

Route::get('{view}', [ApplicationController::class, '__invoke'])->where('view', '(.*)');