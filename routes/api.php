<?php

use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\DailyNoteController;
use App\Http\Controllers\Api\IcsFeedController;
use App\Http\Controllers\Api\IcsFeedRuleController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.api')->group(function () {
    // Overview
    Route::get('/status', StatusController::class);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Issues (nested under projects for index/store, top-level for update/delete)
    Route::get('/projects/{project}/issues', [IssueController::class, 'index']);
    Route::post('/projects/{project}/issues', [IssueController::class, 'store']);
    Route::patch('/issues/{issue}', [IssueController::class, 'update']);
    Route::delete('/issues/{issue}', [IssueController::class, 'destroy']);

    // Tasks (cross-project index, nested under issues for store, top-level for update/delete)
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/issues/{issue}/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Logs (nested under projects)
    Route::get('/projects/{project}/logs', [LogController::class, 'index']);
    Route::post('/projects/{project}/logs', [LogController::class, 'store']);

    // Daily notes
    Route::get('/daily-notes', [DailyNoteController::class, 'index']);
    Route::post('/daily-notes', [DailyNoteController::class, 'store']);

    // Calendar events
    Route::apiResource('events', CalendarEventController::class);

    // External ICS feeds
    Route::apiResource('feeds', IcsFeedController::class);
    Route::post('/feeds/{feed}/sync', [IcsFeedController::class, 'sync']);
    Route::get('/feeds/{feed}/events', [IcsFeedController::class, 'events']);

    // Feed rules
    Route::get('/feeds/{feed}/rules', [IcsFeedRuleController::class, 'index']);
    Route::post('/feeds/{feed}/rules', [IcsFeedRuleController::class, 'store']);
    Route::patch('/rules/{rule}', [IcsFeedRuleController::class, 'update']);
    Route::delete('/rules/{rule}', [IcsFeedRuleController::class, 'destroy']);
    Route::post('/feeds/{feed}/rules/reapply', [IcsFeedRuleController::class, 'reapply']);
});
