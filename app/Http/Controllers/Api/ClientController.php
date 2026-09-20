<?php

namespace App\Http\Controllers\Api;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Module\Module;
use App\Domain\User\User;
use App\Http\Controllers\Controller;
use App\Repositories\ClientRepository;
use App\Services\AuthServiceApi;
use App\Services\ModelServiceApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $this->validate($request, ['enabled' => 'required|boolean']);

        $this->clients->setModuleEnabled($client, $module, $request->boolean('enabled'));

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
