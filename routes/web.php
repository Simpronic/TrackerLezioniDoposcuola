<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('env.auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('studenti', StudentController::class)->except('show');
    Route::resource('lezioni', LessonController::class)->except('show');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
