<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\User\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Discord is the only login method HearthGG has: a client's staff sign
 * in with their Discord account, which is how we know which guild(s)
 * they're allowed to manage.
 */
class DiscordAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $discordUser = Socialite::driver('discord')->user();
        $raw = $discordUser->user;

        $attributes = [
            'username' => $raw['username'] ?? $discordUser->getNickname(),
            'discriminator' => $raw['discriminator'] ?? null,
            'global_name' => $raw['global_name'] ?? null,
            'email' => $discordUser->getEmail(),
            'avatar_hash' => $raw['avatar'] ?? null,
            'access_token' => $discordUser->token,
            'refresh_token' => $discordUser->refreshToken,
            'token_expires_at' => $discordUser->expiresIn ? now()->addSeconds($discordUser->expiresIn) : null,
            'last_login_at' => now(),
        ];

        // Only ever promotes, never demotes: leaving this key out entirely
        // when the ID isn't listed means an admin granted another way
        // (or a name later removed from the env list) isn't silently
        // downgraded just by logging back in.
        if (in_array($discordUser->getId(), config('services.discord.super_admin_ids'), true)) {
            $attributes['is_super_admin'] = true;
        }

        $user = User::updateOrCreate(['discord_id' => $discordUser->getId()], $attributes);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/dashboard');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
