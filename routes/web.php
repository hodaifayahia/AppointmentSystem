<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AppointmentAvailableMonthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatus;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExcludedDates;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportanceEnumController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\specializationsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\Route;









// Routes for all authenticated users
Route::middleware(['auth'])->group(function () {

    // User Routes
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/api/users', [UserController::class, 'store']);
    
    Route::delete('/api/users', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/api/users/search', [UserController::class, 'search'])->name('users.search');
    Route::put('/api/users/{userid}', [UserController::class, 'update']);
    Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/api/users/{userid}/change-role', [UserController::class, 'ChangeRole'])->name('users.ChangeRole');
    
    Route::get('/api/loginuser', [UserController::class, 'getCurrentUser']);
    Route::get('/api/role', [UserController::class, 'role']);


    // Doctor Routes
    Route::get('/api/doctors', [DoctorController::class, 'index']);
    Route::post('/api/doctors', [DoctorController::class, 'store'])->name('users.store');
    Route::put('/api/doctors/{doctorid}', [DoctorController::class, 'update'])->name('users.update');
    Route::delete('/api/doctors/{doctorid}', [DoctorController::class, 'destroy'])->name('users.destroy');
    Route::delete('/api/doctors', [DoctorController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::get('/api/doctors/search', [DoctorController::class, 'search'])->name('users.search');
    Route::get('/api/doctors/WorkingDates', [DoctorController::class, 'WorkingDates']);
    Route::get('/api/doctors/{doctorId}', [DoctorController::class, 'getDoctor'])->name('users.update');
    Route::get('/api/doctors/specializations/{specialization_id}', [DoctorController::class, 'GetDoctorsBySpecilaztion']);
    
    // Specializations Routes
    Route::get('/api/specializations', [specializationsController::class, 'index']);
    Route::post('/api/specializations', [specializationsController::class, 'store']);
    Route::put('/api/specializations/{id}', [specializationsController::class, 'update']);
    Route::delete('/api/specializations/{id}', [specializationsController::class, 'destroy']);
    
    // Appointment Routes
    Route::get('/api/appointments/search', [AppointmentController::class, 'search']);
    Route::get('/api/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
    Route::get('/api/appointments/canceledappointments', [AppointmentController::class, 'getAllCanceledAppointments']);
    Route::get('/api/appointments/available', [AppointmentController::class, 'AvailableAppointments']);
    Route::get('/api/appointmentStatus/{doctorid}', [AppointmentStatus::class, 'appointmentStatus']);
    Route::get('/api/appointmentStatus/{patientid}', [AppointmentStatus::class, 'appointmentStatusPatient']);
    Route::get('/api/todaysAppointments/{doctorid}', [AppointmentStatus::class, 'todaysAppointments']);
    Route::get('/api/appointments/ForceSlots', [AppointmentController::class, 'ForceAppointment']);
    Route::get('/api/appointments/{doctorid}', [AppointmentController::class, 'index']);
    Route::get('/api/appointments/{doctorId}/filter-by-date', [AppointmentController::class, 'filterByDate']);
    Route::patch('/api/appointment/{appointmentId}/status', [AppointmentController::class, 'changeAppointmentStatus']);
    Route::post('/api/appointments', [AppointmentController::class, 'store']);
    Route::get('/api/appointments', [AppointmentController::class, 'GetAllAppointments']);
    Route::put('/api/appointments/{appointmentid}', [AppointmentController::class, 'update']);
    Route::get('/api/appointments/{doctorId}/{appointmentId}', [AppointmentController::class, 'getAppointment']);
    Route::delete('/api/appointments/{appointmentid}', [AppointmentController::class, 'destroy']);
    
    // Schedule Routes
    Route::get('/api/schedules/{doctorid}', [ScheduleController::class, 'index']);

    // Patient Routes
    Route::get('/api/patients', [PatientController::class, 'index']);
    Route::post('/api/patients', [PatientController::class, 'store']);
    Route::post('/api/patients/{patientid}', [PatientController::class, 'update']);
    Route::get('/api/patient/{PatientId}', [PatientController::class, 'PatientAppointments']);
    Route::get('/api/patients/search', [PatientController::class, 'search']);
    Route::get('/api/patients/{parentid}', [PatientController::class, 'SpecificPatient']);
    
    
    Route::get('/api/setting/user', [SettingController::class, 'index']);
    Route::put('/api/setting/user', [SettingController::class, 'update']);
    Route::put('/api/setting/password', [SettingController::class, 'updatePassword']);


    // Waitlist Routes
    Route::post('/api/waitlists', [WaitListController::class, 'store']);
    Route::put('/api/waitlists/{waitlist}', [WaitListController::class, 'update']);
    Route::get('/api/waitlists', [WaitListController::class, 'index']);
    Route::get('/api/waitlists/ForDoctor', [WaitListController::class, 'GetForDoctor']);
    Route::get('/api/waitlists/search', [WaitListController::class, 'search']);
    Route::get('/waitlists/filter', [WaitlistController::class, 'filter']);
    Route::post('/api/waitlists/{waitlist}/add-to-appointments', [WaitListController::class, 'AddPaitentToAppointments']);
    Route::post('/api/waitlists/{waitlistid}/move-to-end', [WaitListController::class, 'moveToend']);
    Route::delete('/api/waitlists/{waitlist}', [WaitListController::class, 'destroy']); 
    Route::patch('/api/waitlists/{waitlist}/importance', [WaitlistController::class, 'updateImportance']);
    Route::get('/api/importance-enum', [ImportanceEnumController::class, 'index']);

    // Alternatively, define routes manually
    Route::get('/api/excluded-dates', [ExcludedDates::class, 'index']);
    Route::get('/api/excluded-dates/{doctorId}', [ExcludedDates::class, 'GetExcludedDates']);
    Route::post('/api/excluded-dates', [ExcludedDates::class, 'store']);
    Route::put('/api/excluded-dates/{id}', [ExcludedDates::class, 'update']);
    Route::delete('/api/excluded-dates/{id}', [ExcludedDates::class, 'destroy']);
    Route::get('/api/monthwork/{doctorid}', [AppointmentAvailableMonthController::class, 'index']);
    
    // Logout Route for Importing and Exporting Data to Excel 
    Route::post('/api/import/users', [ImportController::class, 'ImportUsers']);
    Route::get('/api/export/users', [ExportController::class, 'ExportUsers']);

    Route::post('/api/import/Patients', [ImportController::class, 'ImportPatients']);
    Route::get('/api/export/Patients', [ExportController::class, 'ExportPatients']);

    Route::post('/api/import/appointment/{doctorid}', [ImportController::class, 'ImportAppointment']);
    Route::get('/api/import/appointment', [ExportController::class, 'ExportAppointment']);


});

// Catch-all route for views
Route::get('{view}', [ApplicationController::class, '__invoke'])
    ->where('view', '(.*)');
