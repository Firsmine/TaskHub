<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // PUBLIC
    Route::prefix('auth')->group(function () {
        Route::post('/register', [UserController::class, 'register']);
        Route::post('/login', [UserController::class, 'login']);
    });

    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [UserController::class, 'logout']);
        Route::get('/auth/profile', [UserController::class, 'me']);

        // Project
        Route::get('/projects', [ProjectsController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get(
            '/projects/{project}/tasks',
            [TaskController::class, 'projectTasks']
        );
        Route::post(
            '/projects/{project}/tasks',
            [TaskController::class, 'store']
        );
        Route::get('/projects/{project}', [ProjectsController::class, 'show']);
        Route::put('/projects/{project}', [ProjectsController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectsController::class, 'destroy']);

        // Task
        Route::get('/my-tasks', [TaskController::class, 'myTasks']);
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::patch(
            '/tasks/{task}/status',
            [TaskController::class, 'updateStatus']
        );
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    });
});
