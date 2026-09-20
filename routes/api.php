<?php

use App\Http\Controllers\Api\Auth\DiscordAuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\Discord\AnalyticsController;
use App\Http\Controllers\Api\Discord\ChannelController;
use App\Http\Controllers\Api\Discord\EventController;
use App\Http\Controllers\Api\Discord\InviteController;
use App\Http\Controllers\Api\Discord\MemberController;
use App\Http\Controllers\Api\Discord\RoleController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarningController;
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
    Route::post('/clients/{client}/bot/install', [ClientController::class, 'recordBotInstall']);

    // Whether a client is paying at all - the account-level gate every
    // module sits behind. HearthGG-only (see App\Http\Middleware\EnsureSuperAdmin).
    Route::middleware('super_admin')->group(function () {
        Route::post('/clients/{client}/activate', [ClientController::class, 'activate']);
        Route::post('/clients/{client}/suspend', [ClientController::class, 'suspend']);
        Route::post('/clients/{client}/cancel', [ClientController::class, 'cancel']);
    });

    // Who on the HearthGG side (owner/admin/staff) can manage this client -
    // not to be confused with the client's own Discord guild members below.
    Route::post('/clients/{client}/team', [ClientController::class, 'addMember']);
    Route::delete('/clients/{client}/team/{user}', [ClientController::class, 'removeMember']);

    // Every route below calls the Discord API (or manages data backed by
    // it) on the client's guild, gated by App\Http\Middleware\EnsureModuleAccess.

    Route::prefix('/clients/{client}/members')->middleware('module:members')->group(function () {
        Route::get('/', [MemberController::class, 'index']);
        Route::post('/sync', [MemberController::class, 'sync']);
        Route::get('/{discordUserId}/history', [MemberController::class, 'history']);
        Route::post('/{discordUserId}/kick', [MemberController::class, 'kick']);
        Route::post('/{discordUserId}/timeout', [MemberController::class, 'timeout']);
        Route::post('/{discordUserId}/warn', [MemberController::class, 'warn']);
    });

    Route::prefix('/clients/{client}/members')->middleware('module:bans')->group(function () {
        Route::get('/bans', [MemberController::class, 'bans']);
        Route::post('/{discordUserId}/ban', [MemberController::class, 'ban']);
        Route::post('/{discordUserId}/unban', [MemberController::class, 'unban']);
    });

    Route::prefix('/clients/{client}/warnings')->middleware('module:members')->group(function () {
        Route::get('/', [WarningController::class, 'index']);
        Route::post('/{warning}/resolve', [WarningController::class, 'resolve']);
    });

    Route::prefix('/clients/{client}/roles')->middleware('module:members')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/staff', [RoleController::class, 'staff']);
        Route::put('/{roleId}/members/{discordUserId}', [RoleController::class, 'addToMember']);
        Route::delete('/{roleId}/members/{discordUserId}', [RoleController::class, 'removeFromMember']);
    });

    Route::get('/clients/{client}/channels', [ChannelController::class, 'index'])->middleware('module:members');

    Route::prefix('/clients/{client}/events')->middleware('module:events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::post('/', [EventController::class, 'store']);
        Route::get('/{discordEventId}', [EventController::class, 'show']);
        Route::put('/{discordEventId}', [EventController::class, 'update']);
        Route::delete('/{discordEventId}', [EventController::class, 'destroy']);
    });

    Route::prefix('/clients/{client}/tickets')->middleware('module:tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::post('/{ticket}/reply', [TicketController::class, 'reply']);
        Route::put('/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::post('/{ticket}/close', [TicketController::class, 'close']);
    });

    Route::prefix('/clients/{client}/posts')->middleware('module:posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/recent', [PostController::class, 'recent']);
        Route::post('/', [PostController::class, 'store']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::post('/{post}/publish', [PostController::class, 'publish']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
    });

    Route::prefix('/clients/{client}/invites')->middleware('module:members')->group(function () {
        Route::get('/', [InviteController::class, 'index']);
        Route::post('/sync', [InviteController::class, 'sync']);
    });

    Route::prefix('/clients/{client}/analytics')->middleware('module:analytics')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary']);
        Route::get('/guild-stats', [AnalyticsController::class, 'guildStats']);
        Route::get('/audit-log', [AnalyticsController::class, 'auditLog']);
    });
});
