<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Resources\TransactionResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

/**
 * Every report accepts the same filters: from, to (Y-m-d), goal_id and type.
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function history(TransactionFilterRequest $request): JsonResponse
    {
        $filter = $request->filter();
        $history = $this->reports->history($filter);

        return $this->respond([
            'transactions' => TransactionResource::collection($history['transactions']),
            'totals' => $history['totals'],
        ], $filter->toArray());
    }

    public function goals(TransactionFilterRequest $request): JsonResponse
    {
        $filter = $request->filter();

        return $this->respond($this->reports->byGoal($filter), $filter->toArray());
    }

    public function monthly(TransactionFilterRequest $request): JsonResponse
    {
        $filter = $request->filter();

        return $this->respond($this->reports->monthly($filter), $filter->toArray());
    }

    public function status(TransactionFilterRequest $request): JsonResponse
    {
        $filter = $request->filter();

        return $this->respond($this->reports->goalStatus($filter), $filter->toArray());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function respond(mixed $data, array $filters): JsonResponse
    {
        return response()->json(['data' => $data, 'filters' => $filters]);
    }
}
