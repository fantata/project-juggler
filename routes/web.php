<?php

use App\Http\Controllers\CalDavController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Dashboard;
use App\Livewire\MyTasks;
use App\Livewire\ProjectDetail;
use App\Livewire\ScreenDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/tasks', MyTasks::class)->name('tasks');
    Route::get('/screen', ScreenDashboard::class)->name('screen');
    Route::get('/projects/{project}', ProjectDetail::class)->name('projects.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// CalDAV endpoint — excluded from CSRF and web middleware
Route::any('/dav/{path?}', [CalDavController::class, 'handle'])
    ->where('path', '.*')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('caldav');

require __DIR__.'/auth.php';
