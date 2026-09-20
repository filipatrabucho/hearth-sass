<?php

use App\Http\Controllers\Api\Auth\DiscordAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => config('app.name'), 'status' => 'ok']));

// Browser-redirect OAuth flow: needs the 'web' middleware group (session
// + cookies) so Socialite's CSRF state and the Sanctum SPA session work.
Route::prefix('auth/discord')->name('auth.discord.')->group(function () {
    Route::get('/redirect', [DiscordAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [DiscordAuthController::class, 'callback'])->name('callback');
});
