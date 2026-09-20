<?php

namespace App\Http\Controllers\Api;

use App\Domain\Module\Module;
use App\Http\Controllers\Controller;
use App\Services\ModelServiceApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The catalog of modules (events, bans, tickets, ...) that a client can
 * be granted access to. Managing the catalog itself is a HearthGG admin
 * task, not a client one.
 */
class ModuleController extends Controller
{
    public function __construct(private readonly ModelServiceApi $modelService) {}

    public function index(): JsonResponse
    {
        return response()->json(Module::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);

        $this->validate($request, Module::validationRules());

        $module = $this->modelService->create(Module::class, $request->all());

        return response()->json($module, 201);
    }

    public function update(Request $request, Module $module): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);

        $this->validate($request, Module::validationRules($module->id));

        $module = $this->modelService->update($module, $request->all());

        return response()->json($module);
    }

    public function destroy(Request $request, Module $module): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);

        $this->modelService->destroy($module);

        return response()->json(status: 204);
    }
}
