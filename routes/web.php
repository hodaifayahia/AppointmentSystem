<?php
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('api/users', [UserController::class, 'index'])->name('users.index');
Route::post('api/users', [UserController::class, 'store'])->name('users.store');
Route::get('{view}', [ApplicationController::class, '__invoke'])->where('view', '(.*)');