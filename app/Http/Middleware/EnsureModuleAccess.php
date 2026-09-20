<?php

namespace App\Http\Middleware;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Services\AuthServiceApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every per-client module route: the user must have (at least
 * staff) access to the client, and the client must have paid, active
 * access to the module itself (App\Domain\Module\ClientModule::isActive()).
 *
 * Usage: ->middleware('module:events') on a route with a {client} param.
 */
class EnsureModuleAccess
{
    public function __construct(private readonly AuthServiceApi $authService) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $client = $request->route('client');

        abort_unless($client instanceof Client, 404);

        $user = $request->user();

        abort_unless($this->authService->userHasPermission($user, $client, ClientUser::ROLE_STAFF), 403);

        // Super admins can still poke around a suspended/unpaid client to
        // sort out the account itself - only its own staff are gated.
        if ($user->is_super_admin) {
            return $next($request);
        }

        abort_unless(
            $client->isActive(),
            402,
            "A conta deste cliente está {$client->status} - os módulos ficam indisponíveis até a HearthGG reativar o acesso.",
        );

        abort_unless(
            $client->hasModuleEnabled($moduleKey),
            402,
            "O módulo '{$moduleKey}' não está ativo (ou o pagamento não está em dia) para este cliente.",
        );

        abort_unless(
            $client->isBotInstalled(),
            409,
            'O bot HearthGG ainda não foi adicionado ao servidor de Discord deste cliente.',
        );

        return $next($request);
    }
}
