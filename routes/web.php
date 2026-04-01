<?php

use App\Http\Controllers\IcsController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Calendar;
use App\Livewire\Dashboard;
use App\Livewire\FeedManager;
use App\Livewire\MyTasks;
use App\Livewire\ProjectDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/calendar', Calendar::class)->name('calendar');
    Route::get('/tasks', MyTasks::class)->name('tasks');
    Route::get('/feeds', FeedManager::class)->name('feeds.manage');
    Route::get('/projects/{project}', ProjectDetail::class)->name('projects.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/token', [ProfileController::class, 'generateToken'])->name('profile.generate-token');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ICS feed (public, authenticated by unique token in URL)
Route::get('/ics/{token}.ics', IcsController::class)->name('ics.feed');

require __DIR__.'/auth.php';
