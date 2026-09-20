<?php

use App\Http\Controllers\Api\Auth\DiscordAuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [DiscordAuthController::class, 'me']);
    Route::post('/auth/logout', [DiscordAuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    Route::get('/modules', [ModuleController::class, 'index']);
    Route::post('/modules', [ModuleController::class, 'store']);
    Route::put('/modules/{module}', [ModuleController::class, 'update']);
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    Route::get('/clients/{client}/modules', [ClientController::class, 'modules']);
    Route::post('/clients/{client}/modules/{module}', [ClientController::class, 'toggleModule']);

    Route::post('/clients/{client}/members', [ClientController::class, 'addMember']);
    Route::delete('/clients/{client}/members/{user}', [ClientController::class, 'removeMember']);
});
