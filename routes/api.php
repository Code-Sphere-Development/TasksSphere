<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListItemApiController;
use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\Api\TaskListApiController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::get('/tasks', [TaskApiController::class, 'index']);
    Route::get('/tasks/occurrences', [TaskApiController::class, 'occurrences']);
    Route::get('/tasks/completed', [TaskApiController::class, 'completed']);
    Route::post('/tasks', [TaskApiController::class, 'store']);
    Route::get('/profile', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'update']);
    Route::get('/tasks/{task}', [TaskApiController::class, 'show']);
    Route::put('/tasks/{task}', [TaskApiController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskApiController::class, 'destroy']);
    Route::post('/tasks/{task}/complete', [TaskApiController::class, 'complete']);
    Route::post('/tasks/{task}/skip', [TaskApiController::class, 'skip']);

    // Task Lists
    Route::get('/task-lists', [TaskListApiController::class, 'index']);
    Route::post('/task-lists', [TaskListApiController::class, 'store']);
    Route::get('/task-lists/{taskList}', [TaskListApiController::class, 'show']);
    Route::put('/task-lists/{taskList}', [TaskListApiController::class, 'update']);
    Route::delete('/task-lists/{taskList}', [TaskListApiController::class, 'destroy']);

    // List Items
    Route::get('/task-lists/{taskList}/items', [ListItemApiController::class, 'index']);
    Route::post('/task-lists/{taskList}/items', [ListItemApiController::class, 'store']);
    Route::put('/task-lists/{taskList}/items/{item}', [ListItemApiController::class, 'update']);
    Route::delete('/task-lists/{taskList}/items/{item}', [ListItemApiController::class, 'destroy']);
});
