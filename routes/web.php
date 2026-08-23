<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Le pagine di login sono disponibili soltanto finché non esiste una sessione valida.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

// Tutte le funzioni e i dati del tracker sono protetti dal login configurato nel .env.
Route::middleware('env.auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/fatturazione', BillingController::class)->name('fatturazione.index');
    Route::get('/studenti/{student}/export-excel', [StudentController::class, 'export'])->name('studenti.export');
    Route::resource('studenti', StudentController::class)->except('show');
    Route::resource('lezioni', LessonController::class)->except('show');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
