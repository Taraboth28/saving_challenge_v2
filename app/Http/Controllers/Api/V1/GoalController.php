<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Http\Resources\TransactionResource;
use App\Services\GoalService;
use App\Services\TransactionService;
use App\Support\TransactionFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function __construct(private GoalService $goals) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(GoalStatus::class)],
        ]);

        $status = isset($validated['status']) ? GoalStatus::from($validated['status']) : null;

        return GoalResource::collection($this->goals->all($status));
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        return GoalResource::make($this->goals->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Goal details including its full saving history.
     */
    public function show(int $goal, TransactionService $transactions): GoalResource
    {
        return GoalResource::make($this->goals->findOrFail($goal))->additional([
            'transactions' => TransactionResource::collection(
                $transactions->list(new TransactionFilter(goalId: $goal)),
            ),
        ]);
    }

    public function update(UpdateGoalRequest $request, int $goal): GoalResource
    {
        return GoalResource::make($this->goals->update($goal, $request->validated()));
    }

    public function destroy(int $goal): Response
    {
        $this->goals->delete($goal);

        return response()->noContent();
    }
}
