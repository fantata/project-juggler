<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\IcsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionAnswerController;
use App\Livewire\Board;
use App\Livewire\Calendar;
use App\Livewire\Dashboard;
use App\Livewire\FeedManager;
use App\Livewire\MyTasks;
use App\Livewire\ProjectDetail;
use App\Livewire\Together;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/calendar', Calendar::class)->name('calendar');
    Route::get('/tasks', MyTasks::class)->name('tasks');
    Route::get('/together', Together::class)->name('together.index');
    Route::get('/feeds', FeedManager::class)->name('feeds.manage');
    // Named projects.detail (not projects.show) to avoid collision with apiResource in api.php
    Route::get('/projects/{project}', ProjectDetail::class)->name('projects.detail');
    Route::get('/projects/{project}/board', Board::class)->name('projects.board');
    Route::get('/attachments/{attachment}', AttachmentController::class)->name('attachments.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/token', [ProfileController::class, 'generateToken'])->name('profile.generate-token');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ICS feed (public, authenticated by unique token in URL)
Route::get('/ics/{token}.ics', IcsController::class)->name('ics.feed');

// Yes/no answer from the emailed link. No login — the signed URL is the auth.
// GET shows a confirmation page (safe for link pre-fetch); POST commits the
// answer (CSRF-protected, human-initiated).
Route::get('/questions/{issue}/answer/{answer}', [QuestionAnswerController::class, 'show'])
    ->name('questions.answer')
    ->middleware('signed');
Route::post('/questions/{issue}/answer/{answer}', [QuestionAnswerController::class, 'store'])
    ->name('questions.answer.commit')
    ->middleware('signed');
/*
|--------------------------------------------------------------------------
| DEV ONLY — mock login
|--------------------------------------------------------------------------
| One-click "log in as the primary user" so we can iterate on the app without
| auth friction while the real FantataID auth is parked. The route only exists
| when mock login is enabled (local, or MOCK_LOGIN=true) — in production it is
| never registered, and the login-screen button is hidden behind the same gate.
| Remove this block, the config('app.mock_login') key, and the login button
| when real auth lands.
*/
if (app()->environment('local') || config('app.mock_login')) {
    Route::post('/dev/login', function (Request $request) {
        $user = User::query()->oldest('id')->first();

        abort_if($user === null, 404, 'No user to mock-login as — run `php artisan db:seed`.');

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    })->name('dev.login');
}

require __DIR__.'/auth.php';
