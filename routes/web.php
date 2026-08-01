<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Dashboard;
use App\Livewire\Laporan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/laporan', Laporan::class)
    ->middleware(['auth'])
    ->name('laporan');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
