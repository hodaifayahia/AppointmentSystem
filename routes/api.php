<?php


// Group API routes under "api"
// Route::get('api/users', [UserController::class, 'index'])->name('users.index');
use App\Http\Controllers\UserController;
use App\Http\Controllers\DoctorController;
use Illuminate\Support\Facades\Route;

// Routes for users
Route::prefix('api')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{userid}', [UserController::class, 'edit'])->name('users.edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Routes for doctors
Route::prefix('api')->group(function () {
    Route::post('doctors', [DoctorController::class, 'store'])->name('doctors.store');
    Route::get('doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::put('doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
    Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');
});
