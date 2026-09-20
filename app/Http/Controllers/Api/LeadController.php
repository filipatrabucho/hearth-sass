<?php

namespace App\Http\Controllers\Api;

use App\Domain\Lead\Lead;
use App\Http\Controllers\Controller;
use App\Services\ModelServiceApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(private readonly ModelServiceApi $modelService) {}

    /**
     * The public "get started" form on the marketing homepage. No auth -
     * this route sits outside the auth:sanctum group in routes/api.php -
     * but still goes through Sanctum's stateful CSRF check.
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, Lead::validationRules());

        $lead = $this->modelService->create(Lead::class, [
            ...$request->only(['name', 'email', 'discord_username', 'server_name', 'plan_interest', 'message']),
            'status' => Lead::STATUS_NEW,
            'source' => 'homepage',
        ]);

        return response()->json($lead, 201);
    }

    /**
     * HearthGG-only: reviewing sign-ups, not a client's own business.
     */
    public function index(Request $request): JsonResponse
    {
        $this->validate($request, ['status' => ['sometimes', Rule::in(Lead::STATUSES)]]);

        return response()->json(
            Lead::query()
                ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
                ->latest()
                ->get()
        );
    }

    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $this->validate($request, ['status' => ['required', Rule::in(Lead::STATUSES)]]);

        $lead->update(['status' => $request->input('status')]);

        return response()->json($lead->refresh());
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $this->modelService->destroy($lead);

        return response()->json(status: 204);
    }
}
