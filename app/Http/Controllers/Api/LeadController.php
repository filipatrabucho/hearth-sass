<?php

namespace App\Http\Controllers\Api;

use App\Domain\Lead\Lead;
use App\Http\Controllers\Controller;
use App\Services\ModelServiceApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Get started" submissions from the public marketing site. store() is
 * the one endpoint here that isn't behind auth:sanctum - anyone can
 * submit a lead; everything else is HearthGG-only (see EnsureSuperAdmin).
 */
class LeadController extends Controller
{
    public function __construct(private readonly ModelServiceApi $modelService) {}

    public function store(Request $request): JsonResponse
    {
        $this->validate($request, Lead::validationRules());

        $lead = $this->modelService->create(Lead::class, [
            ...$request->all(),
            'source' => $request->input('source', 'homepage'),
        ]);

        return response()->json($lead, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->validate($request, ['status' => ['sometimes', Rule::in(Lead::STATUSES)]]);

        return response()->json(
            Lead::query()
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->latest()
                ->get()
        );
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead);
    }

    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $this->validate($request, ['status' => ['required', Rule::in(Lead::STATUSES)]]);

        $lead->update(['status' => $request->input('status')]);

        return response()->json($lead->fresh());
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(status: 204);
    }
}
