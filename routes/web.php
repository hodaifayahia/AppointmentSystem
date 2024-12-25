<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatus;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\specializationsController;
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
Route::get('/api/doctors', [DoctorController::class, 'index']);
Route::post('/api/doctors', [DoctorController::class, 'store'])->name('users.store');
Route::put('/api/doctors/{doctorid}', [DoctorController::class, 'update'])->name('users.update');
Route::delete('/api/doctors/{doctorid}', [DoctorController::class, 'destroy'])->name('users.destroy');
Route::delete('/api/doctors', [DoctorController::class, 'bulkDelete'])->name('users.bulkDelete');
Route::get('/api/doctors/search', [DoctorController::class, 'search'])->name('users.search');
Route::get('/api/doctors/{doctorid}', [DoctorController::class, 'specificDoctor']);


Route::get('api/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
Route::get('/api/appointments', [AppointmentController::class, 'index'])->name('users.index');
Route::post('/api/appointments', [AppointmentController::class, 'store']);
Route::put('/api/appointment/{appointmentid}', [AppointmentController::class, 'update'])->name('users.index');
Route::delete('/api/appointment/{appointmentid}', [AppointmentController::class, 'destory'])->name('users.index');
Route::get('/api/appointmentStatus', [AppointmentStatus::class, 'appointmentStatus']);
Route::get('api/appointments/time-slots', [AppointmentController::class, 'getTimeSlots']);


Route::get('/api/specializations', [specializationsController::class, 'index']);
Route::post('/api/specializations', [specializationsController::class, 'store']);
Route::put('/api/specializations/{specializationid}', [specializationsController::class, 'update']);
Route::delete('/api/specializations/{specializationid}', [specializationsController::class, 'destroy']);


// for patient

Route::get('/api/patients', [PatientController::class, 'index']);
Route::post('/api/patients', [PatientController::class, 'store']);
Route::post('/api/patients/{patientid}', [PatientController::class, 'update']);
Route::get('/api/patients/search', [PatientController::class, 'search']);

Route::get('{view}', [ApplicationController::class, '__invoke'])
    ->where('view', '(.*)');