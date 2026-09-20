<?php

namespace App\Http\Controllers\Api;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Module\ClientModule;
use App\Domain\Module\Module;
use App\Domain\User\User;
use App\Http\Controllers\Controller;
use App\Repositories\ClientRepository;
use App\Services\AuthServiceApi;
use App\Services\ModelServiceApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly AuthServiceApi $authService,
        private readonly ModelServiceApi $modelService,
    ) {}

    /**
     * Clients the current user can switch into.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->clients->forUser($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403, 'Only HearthGG admins can onboard a new client.');

        $this->validate($request, Client::validationRules());

        $client = $this->modelService->create(Client::class, $request->all());

        return response()->json($client, 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_STAFF),
            403
        );

        return response()->json($client->load('owner', 'modules'));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_ADMIN),
            403
        );

        $this->validate($request, Client::validationRules($client->id));

        $client = $this->modelService->update($client, $request->all());

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $request->user()->is_super_admin
                || $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_OWNER),
            403
        );

        $this->modelService->destroy($client);

        return response()->json(status: 204);
    }

    public function modules(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_STAFF),
            403
        );

        return response()->json($client->modules);
    }

    public function toggleModule(Request $request, Client $client, Module $module): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_ADMIN),
            403
        );

        $this->validate($request, [
            'enabled' => 'required|boolean',
            'payment_status' => ['sometimes', 'string', Rule::in(ClientModule::STATUSES)],
            'paid_until' => 'sometimes|nullable|date',
        ]);

        $this->clients->setModuleEnabled(
            $client,
            $module,
            $request->boolean('enabled'),
            $request->input('payment_status'),
            $request->input('paid_until'),
        );

        return response()->json($client->modules()->where('modules.id', $module->id)->first());
    }

    public function addMember(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_ADMIN),
            403
        );

        $this->validate($request, [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:'.implode(',', ClientUser::ROLES),
        ]);

        $user = User::findOrFail($request->input('user_id'));

        $this->clients->addMember($client, $user, $request->input('role'));

        return response()->json($client->users);
    }

    /**
     * Called by the frontend once the admin finishes Discord's "add bot to
     * server" flow: Discord redirects back with `guild_id` and
     * `permissions` in the query string, which the frontend forwards here.
     */
    public function recordBotInstall(Request $request, Client $client): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_ADMIN),
            403
        );

        $this->validate($request, [
            'guild_id' => 'required|string',
            'permissions' => 'required|string',
        ]);

        abort_unless($request->input('guild_id') === $client->discord_guild_id, 422, 'guild_id does not match this client.');

        $client->recordBotInstall($request->input('permissions'));

        return response()->json($client->refresh());
    }

    public function removeMember(Request $request, Client $client, User $user): JsonResponse
    {
        abort_unless(
            $this->authService->userHasPermission($request->user(), $client, ClientUser::ROLE_ADMIN),
            403
        );

        $this->clients->removeMember($client, $user);

        return response()->json(status: 204);
    }
}
